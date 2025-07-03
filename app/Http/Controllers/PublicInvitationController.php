<?php

namespace App\Http\Controllers;

use App\Events\InvitationViewed;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class PublicInvitationController extends Controller
{
    protected $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Show the invitation details and acceptance form.
     */
    public function show(string $token)
    {
        try {
            // Validate JWT token
            $decoded = $this->invitationService->validateToken($token);
            
            $invitation = Invitation::where('token', $token)->first();

            if (!$invitation) {
                return Inertia::render('Invitations/Invalid', [
                    'error' => 'Invitación no encontrada.'
                ]);
            }

            // Emit InvitationViewed event
            event(new InvitationViewed($invitation, request()->ip()));

        } catch (\Exception $e) {
            return Inertia::render('Invitations/Invalid', [
                'error' => $e->getMessage()
            ]);
        }

        $space = $invitation->space;
        $userExists = Auth::check() || \App\Models\User::where('email', $invitation->email)->exists();

        return Inertia::render('Invitations/Show', [
            'invitation' => [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'role_label' => \App\Enums\SpaceRole::tryFrom($invitation->role)?->label() ?? $invitation->role,
                'expires_at' => $invitation->expires_at->format('Y-m-d H:i'),
            ],
            'space' => [
                'name' => $space->name,
                'owner' => $space->owner ? $space->owner->name : 'Desconocido',
            ],
            'userExists' => $userExists,
            'isAuthenticated' => Auth::check(),
            'matchesCurrentUser' => Auth::check() && Auth::user()->email === $invitation->email,
        ]);
    }

    /**
     * Accept an invitation.
     */
    public function accept(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation || !$invitation->isPending()) {
            return redirect()->route('invitation.show', $token)
                ->with('error', 'Invitación inválida o expirada.');
        }

        // Si el usuario está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            
            // Verificar si el usuario autenticado coincide con el email de la invitación
            if ($user->email !== $invitation->email) {
                return redirect()->route('invitation.show', $token)
                    ->with('error', 'Esta invitación es para otro correo electrónico.');
            }
            
            try {
                $result = $this->invitationService->acceptInvitation($token, $user);
                $space = $result['space'];
                
                // Redirigir al espacio
                return redirect()->away($this->getSpaceUrl($space))
                    ->with('success', "Has sido añadido a {$space->name}.");
            } catch (\Exception $e) {
                return redirect()->route('invitation.show', $token)
                    ->with('error', $e->getMessage());
            }
        } else {
            // Usuario no autenticado, verificar si existe
            $user = \App\Models\User::where('email', $invitation->email)->first();
            
            if ($user) {
                // Usuario existe pero no está autenticado, redirigir a login
                return redirect()->route('login', ['invitation' => $token])
                    ->with('info', 'Inicia sesión para aceptar la invitación.');
            } else {
                // Usuario no existe, redirigir al formulario simplificado
                return redirect()->route('invitation.register.form', ['token' => $token]);
            }
        }
    }

    /**
     * Get the URL for a space.
     */
    protected function getSpaceUrl($space)
    {
        $domain = $space->domains()->first();
        
        if ($domain) {
            return 'https://' . $domain->domain;
        }
        
        // Fallback to subdomain if no custom domain
        return 'https://' . $space->id . '.' . config('tenancy.central_domains')[0];
    }
}