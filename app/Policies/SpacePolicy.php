<?php

namespace App\Policies;

use App\Enums\SpacePermission;
use App\Enums\SpaceRole;
use App\Models\Space;
use App\Models\SpaceUser;
use App\Models\User;
use App\Policies\Traits\ResolvesCurrentSpace;

class SpacePolicy
{
    use ResolvesCurrentSpace;
    /**
     * Get the SpaceUser record for the given user and space.
     */
    protected function getSpaceUser(User $user, Space $space): ?SpaceUser
    {
        // If the user is the owner, create a virtual SpaceUser with the owner role
        if ($user->id === $space->owner_id) {
            $spaceUser = new SpaceUser;
            $spaceUser->tenant_id = $space->id;
            $spaceUser->user_id = $user->id;
            $spaceUser->role = SpaceRole::OWNER;

            return $spaceUser;
        }

        // Otherwise, get the actual SpaceUser record
        return $space->users()->where('user_id', $user->id)->first();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Any authenticated user can view spaces index
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Space $space): bool
    {
        // Users can view spaces they own or belong to
        $spaceUser = $this->getSpaceUser($user, $space);

        return $spaceUser !== null && $spaceUser->hasPermission(SpacePermission::VIEW_SPACE);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create spaces
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Space $space): bool
    {
        $spaceUser = $this->getSpaceUser($user, $space);

        return $spaceUser !== null && $spaceUser->hasPermission(SpacePermission::MANAGE_SPACE);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Space $space): bool
    {
        $spaceUser = $this->getSpaceUser($user, $space);

        return $spaceUser !== null && $spaceUser->hasPermission(SpacePermission::DELETE_SPACE);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Space $space): bool
    {
        $spaceUser = $this->getSpaceUser($user, $space);

        return $spaceUser !== null && $spaceUser->hasPermission(SpacePermission::DELETE_SPACE);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Space $space): bool
    {
        $spaceUser = $this->getSpaceUser($user, $space);

        return $spaceUser !== null && $spaceUser->hasPermission(SpacePermission::DELETE_SPACE);
    }

    /**
     * Determine whether the user can invite others to the space.
     */
    public function invite(User $user, Space $space): bool
    {
        $spaceUser = $this->getSpaceUser($user, $space);

        return $spaceUser !== null && $spaceUser->hasPermission(SpacePermission::INVITE_USERS);
    }

    /**
     * Determine whether the user can remove others from the space.
     */
    public function removeUser(User $user, Space $space, User $targetUser): bool
    {
        $spaceUser = $this->getSpaceUser($user, $space);
        $targetSpaceUser = $this->getSpaceUser($targetUser, $space);

        // User must have REMOVE_USERS permission
        if (! $spaceUser || ! $spaceUser->hasPermission(SpacePermission::REMOVE_USERS)) {
            return false;
        }

        // Cannot remove the space owner
        if ($targetUser->id === $space->owner_id) {
            return false;
        }

        // Cannot remove users with higher roles
        if ($targetSpaceUser && $targetSpaceUser->hasRoleHigherThan($spaceUser->role)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage another user's role.
     */
    public function manageUserRole(User $user, Space $space, User $targetUser, ?SpaceRole $newRole = null): bool
    {
        $spaceUser = $this->getSpaceUser($user, $space);
        $targetSpaceUser = $this->getSpaceUser($targetUser, $space);

        // User must have MANAGE_USER_ROLES permission
        if (! $spaceUser || ! $spaceUser->hasPermission(SpacePermission::MANAGE_USER_ROLES)) {
            return false;
        }

        // Cannot change the space owner's role
        if ($targetUser->id === $space->owner_id) {
            return false;
        }

        // Cannot manage users with higher roles
        if ($targetSpaceUser && $targetSpaceUser->hasRoleHigherThan($spaceUser->role)) {
            return false;
        }

        // Cannot assign roles higher than your own (except owner)
        if ($newRole !== null && $newRole !== SpaceRole::OWNER && $spaceUser->role !== SpaceRole::OWNER) {
            if ($newRole->higherThan($spaceUser->role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can manage billing for the space.
     */
    public function manageBilling(User $user, Space $space): bool
    {
        $spaceUser = $this->getSpaceUser($user, $space);

        return $spaceUser !== null && $spaceUser->hasPermission(SpacePermission::MANAGE_BILLING);
    }

    /**
     * Determine whether the user can view the invoices for the space.
     */
    public function viewInvoices(User $user, Space $space): bool
    {
        $spaceUser = $this->getSpaceUser($user, $space);

        return $spaceUser !== null && $spaceUser->hasPermission(SpacePermission::VIEW_INVOICES);
    }

    /**
     * Determine whether the user can view the statistics for the space.
     */
    public function viewStatistics(User $user, Space $space): bool
    {
        $spaceUser = $this->getSpaceUser($user, $space);

        return $spaceUser !== null && $spaceUser->hasPermission(SpacePermission::VIEW_STATISTICS);
    }
}
