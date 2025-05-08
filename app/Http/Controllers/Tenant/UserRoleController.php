<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\SpacePermission;
use App\Enums\SpaceRole;
use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\User;
use App\Traits\HasSpacePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class UserRoleController extends Controller
{
    use HasSpacePermissions;
    
    /**
     * Display a listing of users in the current space.
     */
    public function index()
    {
        $this->authorize('invite', tenant());
        
        $users = tenant()->users()
            ->with('pivot')
            ->get()
            ->map(function ($user) {
                $spaceUser = $this->getSpaceUser($user);
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $spaceUser ? $spaceUser->role->value : null,
                    'role_label' => $spaceUser && $spaceUser->role ? $spaceUser->role->label() : null,
                    'is_owner' => $user->id === tenant()->owner_id,
                    'joined_at' => $user->pivot ? $user->pivot->created_at->format('Y-m-d') : null,
                ];
            });
            
        $currentUser = $this->getSpaceUser(Auth::user());
        $canManageRoles = $currentUser && $currentUser->hasPermission(SpacePermission::MANAGE_USER_ROLES);
        
        return Inertia::render('Tenant/Users/Index', [
            'users' => $users,
            'canManageRoles' => $canManageRoles,
            'canInviteUsers' => $currentUser && $currentUser->hasPermission(SpacePermission::INVITE_USERS),
            'availableRoles' => $canManageRoles ? collect(SpaceRole::assignableRoles())->map(function ($role) {
                return [
                    'value' => $role->value,
                    'label' => $role->label(),
                    'description' => $role->description(),
                ];
            }) : [],
        ]);
    }
    
    /**
     * Show the form for inviting a user to the space.
     */
    public function create()
    {
        $this->authorize('invite', tenant());
        
        $currentUser = $this->getSpaceUser(Auth::user());
        
        return Inertia::render('Tenant/Users/Invite', [
            'availableRoles' => collect(SpaceRole::assignableRoles())->map(function ($role) {
                return [
                    'value' => $role->value,
                    'label' => $role->label(),
                    'description' => $role->description(),
                ];
            }),
            'canManageRoles' => $currentUser && $currentUser->hasPermission(SpacePermission::MANAGE_USER_ROLES),
        ]);
    }
    
    /**
     * Invite a user to the space.
     */
    public function store(Request $request)
    {
        $this->authorize('invite', tenant());
        
        $validated = $request->validate([
            'email' => 'required|email',
            'role' => ['required', new Enum(SpaceRole::class), Rule::notIn([SpaceRole::OWNER->value])],
        ]);
        
        // Check if user exists
        $user = User::where('email', $validated['email'])->first();
        
        if (!$user) {
            // In a real application, you would likely send an invitation email here
            return redirect()->back()->with('error', 'El usuario no existe. Se debe registrar primero.');
        }
        
        // Check if user is already in the space
        if (tenant()->users()->where('user_id', $user->id)->exists()) {
            return redirect()->back()->with('error', 'El usuario ya está en este espacio.');
        }
        
        // Add user to space with the selected role
        tenant()->users()->attach($user->id, [
            'role' => $validated['role'],
        ]);
        
        // Update subscription quantity if needed
        tenant()->syncMemberCount();
        
        return redirect()->route('tenant.users.index')->with('success', 'Usuario invitado correctamente.');
    }
    
    /**
     * Update a user's role in the space.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('manageUserRole', [tenant(), $user]);
        
        $validated = $request->validate([
            'role' => ['required', new Enum(SpaceRole::class), Rule::notIn([SpaceRole::OWNER->value])],
        ]);
        
        // Check if trying to modify the owner
        if ($user->id === tenant()->owner_id) {
            return redirect()->back()->with('error', 'No puedes cambiar el rol del propietario del espacio.');
        }
        
        // Get the current SpaceUser record
        $spaceUser = tenant()->users()->where('user_id', $user->id)->first();
        
        if (!$spaceUser) {
            return redirect()->back()->with('error', 'El usuario no pertenece a este espacio.');
        }
        
        // Update the role
        tenant()->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);
        
        return redirect()->route('tenant.users.index')->with('success', 'Rol actualizado correctamente.');
    }
    
    /**
     * Remove a user from the space.
     */
    public function destroy(User $user)
    {
        $this->authorize('removeUser', [tenant(), $user]);
        
        // Check if trying to remove the owner
        if ($user->id === tenant()->owner_id) {
            return redirect()->back()->with('error', 'No puedes eliminar al propietario del espacio.');
        }
        
        // Remove user from space
        tenant()->users()->detach($user->id);
        
        // Update subscription quantity if needed
        tenant()->syncMemberCount();
        
        return redirect()->route('tenant.users.index')->with('success', 'Usuario eliminado del espacio correctamente.');
    }
}