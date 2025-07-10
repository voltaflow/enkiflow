<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProjectPermission;
use App\Enums\SpacePermission;
use App\Models\Project;
use App\Models\User;
use App\Policies\Traits\ResolvesCurrentSpace;
use App\Services\ProjectPermissionResolver;

class ProjectPolicy
{
    use ResolvesCurrentSpace;

    public function __construct(
        protected ProjectPermissionResolver $permissionResolver
    ) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        
        // Use space-level permission for listing projects
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_ALL_PROJECTS);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            // Check if user has any role in the project
            $role = $this->permissionResolver->getUserRole($user, $project);
            return $role !== null;
        }
        
        // Legacy behavior
        $spaceUser = $this->getSpaceUser($user, $project);
        
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_ALL_PROJECTS)) {
            return true;
        }
        
        return $user->id === $project->user_id || 
               $project->tasks()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        
        // Creating projects is still a space-level permission
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::CREATE_PROJECTS);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::MANAGE_PROJECT
            );
        }
        
        // Legacy behavior
        $spaceUser = $this->getSpaceUser($user, $project);
        
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::EDIT_PROJECTS)) {
            return true;
        }
        
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::MANAGE_PROJECT
            );
        }
        
        // Legacy behavior
        $spaceUser = $this->getSpaceUser($user, $project);
        
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::DELETE_PROJECTS)) {
            return true;
        }
        
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
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            // Only project admins can force delete
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::MANAGE_PROJECT
            );
        }
        
        // Legacy behavior - only space owners
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

    /**
     * Determine whether the user can manage project members.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::MANAGE_MEMBERS
            );
        }
        
        // Legacy behavior
        $spaceUser = $this->getSpaceUser($user, $project);
        
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_ALL_PROJECTS)) {
            return true;
        }
        
        if ($user->id === $project->user_id) {
            return true;
        }
        
        return $project->userHasRole($user, 'manager');
    }

    /**
     * Determine whether the user can view project members.
     */
    public function viewMembers(User $user, Project $project): bool
    {
        // If user can view the project, they can see the members
        return $this->view($user, $project);
    }

    /**
     * Determine whether the user can edit content in the project.
     */
    public function editContent(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::EDIT_CONTENT
            );
        }
        
        // Legacy behavior - anyone who can view can edit
        return $this->view($user, $project);
    }

    /**
     * Determine whether the user can delete content in the project.
     */
    public function deleteContent(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::DELETE_CONTENT
            );
        }
        
        // Legacy behavior
        return $this->update($user, $project);
    }

    /**
     * Determine whether the user can view reports for the project.
     */
    public function viewReports(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::VIEW_REPORTS
            );
        }
        
        // Legacy behavior
        return $this->view($user, $project);
    }

    /**
     * Determine whether the user can view budget for the project.
     */
    public function viewBudget(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::VIEW_BUDGET
            );
        }
        
        // Legacy behavior - only managers and owners
        return $this->manageMembers($user, $project);
    }

    /**
     * Determine whether the user can export data from the project.
     */
    public function exportData(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::EXPORT_DATA
            );
        }
        
        // Legacy behavior
        return $this->view($user, $project);
    }

    /**
     * Determine whether the user can track time in the project.
     */
    public function trackTime(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::TRACK_TIME
            );
        }
        
        // Legacy behavior
        return $this->view($user, $project);
    }

    /**
     * Determine whether the user can view all time entries in the project.
     */
    public function viewAllTimeEntries(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::VIEW_ALL_TIME_ENTRIES
            );
        }
        
        // Legacy behavior - only managers
        return $this->manageMembers($user, $project);
    }

    /**
     * Determine whether the user can manage integrations for the project.
     */
    public function manageIntegrations(User $user, Project $project): bool
    {
        // If using new permission system
        if ($this->shouldUseNewPermissions()) {
            return $this->permissionResolver->userHasPermission(
                $user,
                $project,
                ProjectPermission::MANAGE_INTEGRATIONS
            );
        }
        
        // Legacy behavior - only project admins
        return $this->update($user, $project);
    }

    /**
     * Check if we should use the new permission system.
     */
    protected function shouldUseNewPermissions(): bool
    {
        return config('features.project_permissions', false);
    }

    /**
     * Get the SpaceUser record for the given user and project's space.
     */
    protected function getSpaceUser(User $user, ?Project $project = null): ?\App\Models\SpaceUser
    {
        $space = $this->getCurrentSpace();

        // If the user is the owner, create a virtual SpaceUser with the owner role
        if ($space && $user->id === $space->owner_id) {
            $spaceUser = app(\App\Models\SpaceUser::class);
            $spaceUser->tenant_id = $space->id;
            $spaceUser->user_id = $user->id;
            $spaceUser->role = \App\Enums\SpaceRole::OWNER;

            return $spaceUser;
        }

        // Otherwise, get the actual SpaceUser record
        if (!$space) {
            return null;
        }
        
        return \App\Models\SpaceUser::where('tenant_id', $space->id)
            ->where('user_id', $user->id)
            ->first();
    }
}