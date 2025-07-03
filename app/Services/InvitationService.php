<?php

namespace App\Services;

use App\Events\InvitationAccepted;
use App\Events\InvitationExpired;
use App\Events\InvitationRevoked;
use App\Events\InvitationSent;
use App\Models\Invitation;
use App\Models\InvitationLog;
use App\Models\Space;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitationService
{
    /**
     * Create and send an invitation to join a space.
     *
     * @param Space $space The space to invite to
     * @param string $email The email to invite
     * @param string $role The role to assign
     * @param User $invitedBy The user sending the invitation
     * @return Invitation
     * @throws \Exception If the invitation cannot be created
     */
    public function invite(Space $space, string $email, string $role, User $invitedBy): Invitation
    {
        // Check if user is already a member
        if ($space->hasMemberWithEmail($email)) {
            throw new \Exception('El usuario ya es miembro de este espacio.');
        }

        // Check if there's already a pending invitation - return it if exists (idempotent)
        $existingInvitation = $space->invitations()
            ->where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();
            
        if ($existingInvitation) {
            return $existingInvitation;
        }

        // Create invitation
        $invitation = DB::transaction(function () use ($space, $email, $role, $invitedBy) {
            // Expire any previous invitations for this email
            $space->invitations()
                ->where('email', $email)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            // Create new invitation
            $invitation = new Invitation([
                'tenant_id' => $space->id,
                'email' => $email,
                'role' => $role,
                'token' => $this->generateUniqueToken([
                    'email' => $email,
                    'tenant_id' => $space->id,
                    'role' => $role
                ]),
                'status' => 'pending',
                'expires_at' => now()->addDays(7), // 7 days expiration
                'invited_by' => $invitedBy->id,
            ]);

            $invitation->save();
            
            // Log the action
            InvitationLog::create([
                'invitation_id' => $invitation->id,
                'actor_id' => $invitedBy->id,
                'action' => 'created',
                'ip_address' => request()->ip(),
            ]);
            
            return $invitation;
        });

        // Dispatch event to send email
        event(new InvitationSent($invitation));

        return $invitation;
    }

    /**
     * Accept an invitation and add the user to the space.
     *
     * @param string $token The invitation token
     * @param User|null $user The user accepting the invitation (null if new registration)
     * @return array Contains the space, user, and whether the user was created
     * @throws \Exception If the invitation cannot be accepted
     */
    public function acceptInvitation(string $token, ?User $user = null): array
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation) {
            throw new \Exception('Invitación no encontrada.');
        }

        if (!$invitation->isPending()) {
            if ($invitation->isExpired()) {
                throw new \Exception('Esta invitación ha expirado.');
            } elseif ($invitation->isAccepted()) {
                throw new \Exception('Esta invitación ya ha sido aceptada.');
            } elseif ($invitation->isRevoked()) {
                throw new \Exception('Esta invitación ha sido revocada.');
            }
        }

        $space = $invitation->space;
        $userCreated = false;

        // If no user is provided, check if one exists with the invitation email
        if (!$user) {
            $user = User::where('email', $invitation->email)->first();
        } elseif ($user->email !== $invitation->email) {
            throw new \Exception('El correo electrónico del usuario no coincide con la invitación.');
        }

        // Process the invitation acceptance
        return DB::transaction(function () use ($invitation, $space, $user, $userCreated) {
            // Mark invitation as accepted
            $invitation->markAsAccepted();
            
            // Log the action
            InvitationLog::create([
                'invitation_id' => $invitation->id,
                'actor_id' => $user ? $user->id : null,
                'action' => 'accepted',
                'ip_address' => request()->ip(),
            ]);

            // Add user to space with the specified role
            $space->users()->syncWithoutDetaching([
                $user->id => ['role' => $invitation->role]
            ]);

            // Update subscription count if needed
            $space->syncMemberCount();

            // Dispatch event
            event(new InvitationAccepted($invitation, $user, $userCreated));

            return [
                'space' => $space,
                'user' => $user,
                'userCreated' => $userCreated
            ];
        });
    }

    /**
     * Revoke an invitation.
     *
     * @param Invitation $invitation The invitation to revoke
     * @param User $revokedBy The user revoking the invitation
     * @return Invitation
     */
    public function revokeInvitation(Invitation $invitation, User $revokedBy): Invitation
    {
        if ($invitation->isAccepted()) {
            throw new \Exception('No se puede revocar una invitación ya aceptada.');
        }

        DB::transaction(function () use ($invitation, $revokedBy) {
            $invitation->markAsRevoked();
            
            // Log the action
            InvitationLog::create([
                'invitation_id' => $invitation->id,
                'actor_id' => $revokedBy->id,
                'action' => 'revoked',
                'ip_address' => request()->ip(),
            ]);
        });
        
        // Dispatch event
        event(new InvitationRevoked($invitation));

        return $invitation;
    }

    /**
     * Resend an invitation.
     *
     * @param Invitation $invitation The invitation to resend
     * @param User $resentBy The user resending the invitation
     * @return Invitation
     */
    public function resendInvitation(Invitation $invitation, User $resentBy): Invitation
    {
        if (!$invitation->isPending() && !$invitation->isExpired()) {
            throw new \Exception('Solo se pueden reenviar invitaciones pendientes o expiradas.');
        }

        DB::transaction(function () use ($invitation, $resentBy) {
            // Update invitation
            $invitation->status = 'pending';
            $invitation->expires_at = now()->addDays(7);
            $invitation->save();
            
            // Log the action
            InvitationLog::create([
                'invitation_id' => $invitation->id,
                'actor_id' => $resentBy->id,
                'action' => 'resent',
                'ip_address' => request()->ip(),
            ]);
        });

        // Dispatch event to send email
        event(new InvitationSent($invitation));

        return $invitation;
    }

    /**
     * Generate a unique JWT token for invitations.
     *
     * @param array $payload Additional payload data
     * @return string
     */
    protected function generateUniqueToken(array $payload = []): string
    {
        // Asegurar que tenemos los datos mínimos necesarios
        if (empty($payload['email'])) {
            throw new \InvalidArgumentException('Email is required in payload');
        }
        
        if (empty($payload['tenant_id'])) {
            throw new \InvalidArgumentException('Tenant ID is required in payload');
        }
        
        if (empty($payload['role'])) {
            $payload['role'] = 'member';
        }
        
        return JWT::encode([
            'sub' => $payload['email'],
            'tid' => $payload['tenant_id'],
            'rol' => $payload['role'],
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60), // 7 días
            'jti' => Str::uuid()->toString(),
        ], config('app.key'), 'HS256');
    }

    /**
     * Validate a JWT invitation token.
     *
     * @param string $token The JWT token to validate
     * @return array The decoded token data
     * @throws \Exception If the token is invalid
     */
    public function validateToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key(config('app.key'), 'HS256'));
            
            // Verificar si la invitación existe y es válida
            $invitation = Invitation::where('token', $token)
                ->where('email', $decoded->sub)
                ->where('tenant_id', $decoded->tid)
                ->first();
                
            if (!$invitation) {
                throw new \Exception('Invitación no encontrada');
            }
            
            // Verificar si está expirada pero dentro del grace period (30 min)
            if ($invitation->isExpired() && 
                $invitation->expires_at->diffInMinutes(now()) <= 30) {
                // Renovar automáticamente
                $invitation->expires_at = now()->addDays(7);
                $invitation->status = 'pending';
                $invitation->save();
            } elseif (!$invitation->isPending()) {
                $status = $invitation->isExpired() ? 'expirada' : 
                         ($invitation->isAccepted() ? 'aceptada' : 'revocada');
                throw new \Exception("Esta invitación ha sido {$status}");
            }
            
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \Exception('Token inválido o manipulado: ' . $e->getMessage());
        }
    }

    /**
     * Expire invitations that have passed their expiration date.
     *
     * @return int Number of invitations expired
     */
    public function expireOldInvitations(): int
    {
        $expiredCount = 0;
        
        $invitations = Invitation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->get();
            
        foreach ($invitations as $invitation) {
            DB::transaction(function () use ($invitation) {
                $invitation->markAsExpired();
                
                // Emit event
                event(new InvitationExpired($invitation));
            });
            
            $expiredCount++;
        }
        
        return $expiredCount;
    }

    /**
     * Prune old invitations (for GDPR compliance).
     *
     * @return int Number of invitations pruned
     */
    public function pruneOldInvitations(): int
    {
        return Invitation::where(function ($query) {
                $query->where('status', 'expired')
                      ->orWhere('status', 'revoked');
            })
            ->where('updated_at', '<', now()->subDays(30))
            ->delete();
    }

    /**
     * Get all pending invitations for a space.
     *
     * @param Space $space The space to get invitations for
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingInvitations(Space $space)
    {
        return $space->invitations()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('inviter')
            ->get();
    }
}