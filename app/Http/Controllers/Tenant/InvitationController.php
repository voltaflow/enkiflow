<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\SpacePermission;
use App\Enums\SpaceRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\Space;
use App\Services\InvitationService;
use App\Traits\HasSpacePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvitationController extends Controller
{
    use HasSpacePermissions;

    protected $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Get the current space from domain.
     */
    protected function getCurrentSpaceFromDomain(): Space
    {
        $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', request()->getHost())->first();
        if (!$domain) {
            abort(404);
        }
        $space = Space::find($domain->tenant_id);
        if (!$space) {
            abort(404);
        }
        return $space;
    }

    /**
     * Display a listing of pending invitations.
     */
    public function index()
    {
        $space = $this->getCurrentSpaceFromDomain();
        $this->authorize('viewInvitations', $space);
        
        $pendingInvitations = $this->invitationService->getPendingInvitations($space);
        
        return Inertia::render('Tenant/Invitations/Index', [
            'invitations' => $pendingInvitations->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'role_label' => SpaceRole::tryFrom($invitation->role)?->label() ?? $invitation->role,
                    'expires_at' => $invitation->expires_at->format('Y-m-d H:i'),
                    'invited_by' => $invitation->inviter ? [
                        'id' => $invitation->inviter->id,
                        'name' => $invitation->inviter->name,
                    ] : null,
                    'created_at' => $invitation->created_at->format('Y-m-d H:i'),
                ];
            }),
            'canInviteUsers' => $this->getSpaceUser(Auth::user(), $space)?->hasPermission(SpacePermission::INVITE_USERS) ?? false,
        ]);
    }

    /**
     * Show the form for creating a new invitation.
     */
    public function create()
    {
        $space = $this->getCurrentSpaceFromDomain();
        $this->authorize('invite', $space);

        $currentUser = $this->getSpaceUser(Auth::user(), $space);

        return Inertia::render('Tenant/Invitations/Create', [
            'availableRoles' => collect(SpaceRole::assignableRoles())->map(function ($role) {
                return [
                    'value' => $role->value,
                    'label' => $role->label(),
                    'description' => $role->description(),
                ];
            }),
            'canManageRoles' => $currentUser?->hasPermission(SpacePermission::MANAGE_USER_ROLES) ?? false,
        ]);
    }

    /**
     * Store a newly created invitation.
     */
    public function store(StoreInvitationRequest $request)
    {
        $space = $this->getCurrentSpaceFromDomain();
        
        $this->authorize('invite', $space);

        try {
            $this->invitationService->invite(
                $space,
                $request->email,
                $request->role,
                Auth::user()
            );

            return redirect()->route('tenant.invitations.index')
                ->with('success', 'Invitación enviada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Resend an invitation.
     */
    public function resend(Invitation $invitation)
    {
        $space = $this->getCurrentSpaceFromDomain();
        $this->authorize('invite', $space);

        // Verify the invitation belongs to this tenant
        if ($invitation->tenant_id !== $space->id) {
            abort(404);
        }

        try {
            $this->invitationService->resendInvitation($invitation, Auth::user());

            return redirect()->route('tenant.invitations.index')
                ->with('success', 'Invitación reenviada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Revoke an invitation.
     */
    public function destroy(Invitation $invitation)
    {
        $space = $this->getCurrentSpaceFromDomain();
        $this->authorize('invite', $space);

        // Verify the invitation belongs to this tenant
        if ($invitation->tenant_id !== $space->id) {
            abort(404);
        }

        try {
            $this->invitationService->revokeInvitation($invitation, Auth::user());

            return redirect()->route('tenant.invitations.index')
                ->with('success', 'Invitación revocada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}