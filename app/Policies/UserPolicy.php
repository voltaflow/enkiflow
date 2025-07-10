<?php

namespace App\Policies;

use App\Enums\SpacePermission;
use App\Models\SpaceUser;
use App\Models\User;
use App\Policies\Traits\ResolvesCurrentSpace;

class UserPolicy
{
    use ResolvesCurrentSpace;

    /**
     * Get the SpaceUser record for the authenticated user.
     */
    protected function getSpaceUser(User $authUser): ?SpaceUser
    {
        $space = $this->getCurrentSpace();

        // If the user is the owner, create a virtual SpaceUser with the owner role
        if ($space && $authUser->id === $space->owner_id) {
            $spaceUser = app(SpaceUser::class);
            $spaceUser->tenant_id = $space->id;
            $spaceUser->user_id = $authUser->id;
            $spaceUser->role = \App\Enums\SpaceRole::OWNER;

            return $spaceUser;
        }

        // Otherwise, get the actual SpaceUser record
        return $space ? SpaceUser::where('tenant_id', $space->id)->where('user_id', $authUser->id)->first() : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $authUser): bool
    {
        $spaceUser = $this->getSpaceUser($authUser);

        return $spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_TEAM);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $authUser, User $targetUser): bool
    {
        $spaceUser = $this->getSpaceUser($authUser);

        // Users can always view themselves
        if ($authUser->id === $targetUser->id) {
            return true;
        }

        // Check if user has permission to view team members
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_TEAM);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $authUser): bool
    {
        $spaceUser = $this->getSpaceUser($authUser);

        // Only owners and admins can invite/create users
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_TEAM);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $authUser, User $targetUser): bool
    {
        $spaceUser = $this->getSpaceUser($authUser);

        // Users can always update their own profile
        if ($authUser->id === $targetUser->id) {
            return true;
        }

        // Otherwise need manage team permission
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_TEAM);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        $spaceUser = $this->getSpaceUser($authUser);

        // Users cannot delete themselves
        if ($authUser->id === $targetUser->id) {
            return false;
        }

        // Only owners and admins can remove users
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_TEAM);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $authUser, User $targetUser): bool
    {
        $spaceUser = $this->getSpaceUser($authUser);

        return $spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_TEAM);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $authUser, User $targetUser): bool
    {
        // Only space owners can permanently delete users
        $space = $this->getCurrentSpace();
        return $space && $authUser->id === $space->owner_id;
    }

    /**
     * Determine whether the user can view projects assigned to the target user.
     */
    public function viewProjects(User $authUser, User $targetUser): bool
    {
        $spaceUser = $this->getSpaceUser($authUser);

        // Users can view their own projects
        if ($authUser->id === $targetUser->id) {
            return true;
        }

        // Otherwise need appropriate permissions
        return $spaceUser && (
            $spaceUser->hasPermission(SpacePermission::VIEW_ALL_PROJECTS) ||
            $spaceUser->hasPermission(SpacePermission::MANAGE_TEAM)
        );
    }

    /**
     * Determine whether the user can assign projects to the target user.
     */
    public function assignProjects(User $authUser, User $targetUser): bool
    {
        $spaceUser = $this->getSpaceUser($authUser);

        // Check if user has permission to manage team and projects
        return $spaceUser && (
            $spaceUser->hasPermission(SpacePermission::MANAGE_TEAM) ||
            $spaceUser->hasPermission(SpacePermission::MANAGE_ALL_PROJECTS)
        );
    }

    /**
     * Determine whether the user can manage roles for the target user.
     */
    public function manageRoles(User $authUser, User $targetUser): bool
    {
        $spaceUser = $this->getSpaceUser($authUser);

        // Users cannot manage their own roles
        if ($authUser->id === $targetUser->id) {
            return false;
        }

        // Only owners and admins can manage roles
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_TEAM);
    }

    /**
     * Determine whether the user can view time entries for the target user.
     */
    public function viewTimeEntries(User $authUser, User $targetUser): bool
    {
        $spaceUser = $this->getSpaceUser($authUser);

        // Users can view their own time entries
        if ($authUser->id === $targetUser->id) {
            return true;
        }

        // Otherwise need appropriate permissions
        return $spaceUser && (
            $spaceUser->hasPermission(SpacePermission::VIEW_ALL_TIME_ENTRIES) ||
            $spaceUser->hasPermission(SpacePermission::MANAGE_TEAM)
        );
    }
}