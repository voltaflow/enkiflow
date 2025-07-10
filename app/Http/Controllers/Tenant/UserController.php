<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\SpacePermission;
use App\Enums\SpaceRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateUserRequest;
use App\Models\User;
use App\Models\SpaceUser;
use App\Models\Space;
use App\Traits\HasSpacePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    use HasSpacePermissions;

    /**
     * Display a listing of users in the space.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $space = $this->getCurrentSpace();
        
        // Query con relaciones y filtros
        $query = SpaceUser::where('tenant_id', $space->id)
            ->with('user');

        // Filtro por búsqueda
        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro por rol
        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        // Filtro por estado
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $users = $query->paginate(20)->through(function ($spaceUser) {
            return [
                'id' => $spaceUser->user->id,
                'name' => $spaceUser->user->name,
                'email' => $spaceUser->user->email,
                'role' => $spaceUser->role,
                'status' => $spaceUser->status,
                'capacity_hours' => $spaceUser->capacity_hours,
                'cost_rate' => $spaceUser->cost_rate,
                'billable_rate' => $spaceUser->billable_rate,
                'joined_at' => $spaceUser->created_at,
            ];
        });

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'availableRoles' => collect(SpaceRole::assignableRoles())->map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
            'canInviteUsers' => $this->userHasPermission($request->user(), SpacePermission::INVITE_USERS),
            'canEditUsers' => $this->userHasPermission($request->user(), SpacePermission::MANAGE_USER_ROLES),
            'canDeleteUsers' => $this->userHasPermission($request->user(), SpacePermission::REMOVE_USERS),
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user)
    {
        $this->authorize('view', $user);

        $space = $this->getCurrentSpace();
        $spaceUser = $this->getSpaceUser($user, $space);

        if (!$spaceUser) {
            abort(404, 'Usuario no encontrado en este espacio');
        }

        return Inertia::render('Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $spaceUser->role,
                'status' => $spaceUser->status,
                'capacity_hours' => $spaceUser->capacity_hours,
                'cost_rate' => $spaceUser->cost_rate,
                'billable_rate' => $spaceUser->billable_rate,
                'joined_at' => $spaceUser->created_at,
                'effective_permissions' => $spaceUser->getPermissions(),
                'custom_permissions' => $spaceUser->custom_permissions,
                'additional_permissions' => $spaceUser->additional_permissions,
                'revoked_permissions' => $spaceUser->revoked_permissions,
            ],
            'availableRoles' => collect(SpaceRole::assignableRoles())->map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
            ]),
            'canEditUsers' => $this->userHasPermission($request->user(), SpacePermission::MANAGE_USER_ROLES),
            'canDeleteUsers' => $this->userHasPermission($request->user(), SpacePermission::REMOVE_USERS),
            'canManageRoles' => $this->userHasPermission($request->user(), SpacePermission::MANAGE_USER_ROLES),
            'canResetPasswords' => $this->userHasPermission($request->user(), SpacePermission::MANAGE_USER_ROLES),
            'canAssignProjects' => $request->user()->can('assignProjects', $user),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $space = $this->getCurrentSpace();
        $spaceUser = $this->getSpaceUser($user, $space);

        if (!$spaceUser) {
            abort(404, 'Usuario no encontrado en este espacio');
        }

        // Actualizar rol si se proporciona y el usuario tiene permisos
        if ($request->has('role') && $this->userHasPermission($request->user(), SpacePermission::MANAGE_USER_ROLES)) {
            $spaceUser->role = SpaceRole::from($request->role);
        }

        // Actualizar otros campos solo si se proporcionan
        if ($request->has('capacity_hours')) {
            $spaceUser->capacity_hours = $request->capacity_hours;
        }
        if ($request->has('cost_rate')) {
            $spaceUser->cost_rate = $request->cost_rate;
        }
        if ($request->has('billable_rate')) {
            $spaceUser->billable_rate = $request->billable_rate;
        }
        if ($request->has('status')) {
            $spaceUser->status = $request->status;
        }

        // Actualizar permisos personalizados si se proporcionan
        if ($request->has('custom_permissions')) {
            $spaceUser->custom_permissions = $request->custom_permissions;
        }
        if ($request->has('additional_permissions')) {
            $spaceUser->additional_permissions = $request->additional_permissions;
        }
        if ($request->has('revoked_permissions')) {
            $spaceUser->revoked_permissions = $request->revoked_permissions;
        }

        $spaceUser->save();

        // Limpiar caché del usuario
        $cacheKey = "space_user:{$space->id}:{$user->id}";
        \Cache::forget($cacheKey);

        return redirect()->back()->with('success', 'Usuario actualizado correctamente');
    }

    /**
     * Remove the specified user from the space.
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $space = $this->getCurrentSpace();
        $spaceUser = $this->getSpaceUser($user, $space);

        if (!$spaceUser) {
            abort(404, 'Usuario no encontrado en este espacio');
        }

        // No permitir eliminar al owner del espacio
        if ($user->id === $space->owner_id) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar al propietario del espacio']);
        }

        // No permitir auto-eliminación
        if ($user->id === $request->user()->id) {
            return redirect()->back()->withErrors(['error' => 'No puedes eliminarte a ti mismo del espacio']);
        }

        $spaceUser->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado del espacio');
    }

    /**
     * Send password reset link to the user.
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorize('resetPassword', $user);

        $space = $this->getCurrentSpace();
        $spaceUser = $this->getSpaceUser($user, $space);

        if (!$spaceUser) {
            abort(404, 'Usuario no encontrado en este espacio');
        }

        try {
            // Intentar enviar el enlace de restablecimiento
            $status = Password::sendResetLink(['email' => $user->email]);

            if ($status === Password::RESET_LINK_SENT) {
                return redirect()->back()->with('success', 'Se ha enviado un enlace de restablecimiento de contraseña al correo: ' . $user->email);
            }

            // Manejar diferentes estados de error
            $errorMessage = match ($status) {
                Password::INVALID_USER => 'Usuario no encontrado',
                Password::RESET_THROTTLED => 'Por favor espera antes de solicitar otro enlace',
                default => 'No se pudo enviar el enlace de restablecimiento: ' . $status
            };

            return redirect()->back()->withErrors(['error' => $errorMessage]);
        } catch (\Exception $e) {
            \Log::error('Error al enviar enlace de restablecimiento de contraseña', [
                'user_id' => $user->id,
                'space_id' => $space->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->withErrors(['error' => 'Error al enviar el correo: ' . $e->getMessage()]);
        }
    }
}