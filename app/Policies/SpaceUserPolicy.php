<?php

namespace App\Policies;

use App\Enums\SpacePermission;
use App\Models\User;
use App\Traits\HasSpacePermissions;
use Illuminate\Auth\Access\Response;

class SpaceUserPolicy
{
    use HasSpacePermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Todos los usuarios pueden ver la lista de usuarios del espacio
        return $this->userHasPermission($user, SpacePermission::VIEW_SPACE);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $targetUser): bool
    {
        // Todos los usuarios pueden ver detalles de otros usuarios del espacio
        return $this->userHasPermission($user, SpacePermission::VIEW_SPACE);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Invitar usuarios requiere el permiso INVITE_USERS
        return $this->userHasPermission($user, SpacePermission::INVITE_USERS);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $targetUser): bool
    {
        // Editar usuarios requiere MANAGE_USER_ROLES
        return $this->userHasPermission($user, SpacePermission::MANAGE_USER_ROLES);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $targetUser): bool
    {
        // No se puede eliminar a uno mismo
        if ($user->id === $targetUser->id) {
            return false;
        }

        // No se puede eliminar al owner del espacio
        $space = $this->getCurrentSpace();
        if ($space && $targetUser->id === $space->owner_id) {
            return false;
        }

        // Eliminar usuarios requiere REMOVE_USERS
        return $this->userHasPermission($user, SpacePermission::REMOVE_USERS);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $targetUser): bool
    {
        // Restaurar usuarios archivados requiere MANAGE_USER_ROLES
        return $this->userHasPermission($user, SpacePermission::MANAGE_USER_ROLES);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $targetUser): bool
    {
        // No permitimos eliminación permanente por seguridad
        return false;
    }

    /**
     * Determine whether the user can reset another user's password.
     */
    public function resetPassword(User $user, User $targetUser): bool
    {
        // Reset de contraseña requiere MANAGE_USER_ROLES
        return $this->userHasPermission($user, SpacePermission::MANAGE_USER_ROLES);
    }

    /**
     * Determine whether the user can manage another user's role.
     */
    public function manageRole(User $user, User $targetUser): bool
    {
        // No se puede cambiar el rol propio
        if ($user->id === $targetUser->id) {
            return false;
        }

        // No se puede cambiar el rol del owner
        $space = $this->getCurrentSpace();
        if ($space && $targetUser->id === $space->owner_id) {
            return false;
        }

        // Gestionar roles requiere MANAGE_USER_ROLES
        return $this->userHasPermission($user, SpacePermission::MANAGE_USER_ROLES);
    }

    /**
     * Determine whether the user can manage permissions.
     */
    public function managePermissions(User $user, User $targetUser): bool
    {
        // Solo OWNER y ADMIN pueden gestionar permisos personalizados
        return $this->userHasPermission($user, SpacePermission::MANAGE_USER_ROLES);
    }
}