<?php

namespace App\Policies;

use App\Enums\SpacePermission;
use App\Models\Project;
use App\Models\Space;
use App\Models\SpaceUser;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Get the SpaceUser record for the given user and project's space.
     */
    protected function getSpaceUser(User $user, Project $project): ?SpaceUser
    {
        $space = Space::find(tenant('id'));

        // If the user is the owner, create a virtual SpaceUser with the owner role
        if ($space && $user->id === $space->owner_id) {
            $spaceUser = app(SpaceUser::class);
            $spaceUser->tenant_id = $space->id;
            $spaceUser->user_id = $user->id;
            $spaceUser->role = \App\Enums\SpaceRole::OWNER;

            return $spaceUser;
        }

        // Otherwise, get the actual SpaceUser record
        return $space ? $space->users()->where('user_id', $user->id)->first() : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $space = Space::find(tenant('id'));
        if (! $space) {
            return false;
        }

        $spaceUser = $this->getSpaceUser($user, null);

        return $spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_ALL_PROJECTS);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        $spaceUser = $this->getSpaceUser($user, $project);

        // Check if user has permission to view all projects
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_ALL_PROJECTS)) {
            return true;
        }

        // Check if user is assigned to this project
        return $user->id === $project->user_id || $project->tasks()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $space = Space::find(tenant('id'));
        if (! $space) {
            return false;
        }

        $spaceUser = $this->getSpaceUser($user, null);

        return $spaceUser && $spaceUser->hasPermission(SpacePermission::CREATE_PROJECTS);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        $spaceUser = $this->getSpaceUser($user, $project);

        // Check if user has permission to edit any project
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::EDIT_PROJECTS)) {
            return true;
        }

        // Check if user is the project owner
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        $spaceUser = $this->getSpaceUser($user, $project);

        // Check if user has permission to delete projects
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::DELETE_PROJECTS)) {
            return true;
        }

        // Check if user is the project owner
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        $spaceUser = $this->getSpaceUser($user, $project);

        return $spaceUser && $spaceUser->isOwner();
    }

    /**
     * Determine whether the user can mark the project as completed.
     */
    public function complete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Determine whether the user can reactivate the project.
     */
    public function reactivate(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }
}
