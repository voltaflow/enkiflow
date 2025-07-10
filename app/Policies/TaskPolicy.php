<?php

namespace App\Policies;

use App\Enums\SpacePermission;
use App\Models\Space;
use App\Models\SpaceUser;
use App\Models\Task;
use App\Models\User;
use App\Policies\Traits\ResolvesCurrentSpace;

class TaskPolicy
{
    use ResolvesCurrentSpace;
    /**
     * Get the SpaceUser record for the given user and task's space.
     */
    protected function getSpaceUser(User $user, ?Task $task = null): ?SpaceUser
    {
        $space = $this->getCurrentSpace();
        
        \Log::debug('TaskPolicy::getSpaceUser', [
            'user_id' => $user->id,
            'space_found' => $space ? 'yes' : 'no',
            'space_id' => $space ? $space->id : null,
            'space_owner_id' => $space ? $space->owner_id : null,
            'is_owner' => $space ? ($user->id === $space->owner_id) : false,
        ]);

        // If the user is the owner, create a virtual SpaceUser with the owner role
        if ($space && $user->id === $space->owner_id) {
            $spaceUser = app(SpaceUser::class);
            $spaceUser->tenant_id = $space->id;
            $spaceUser->user_id = $user->id;
            $spaceUser->role = \App\Enums\SpaceRole::OWNER;

            return $spaceUser;
        }

        // Otherwise, get the actual SpaceUser record
        return $space ? SpaceUser::where('tenant_id', $space->id)
            ->where('user_id', $user->id)
            ->first() : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        
        \Log::debug('TaskPolicy::viewAny', [
            'user_id' => $user->id,
            'space_user_found' => $spaceUser ? 'yes' : 'no',
            'space_user_role' => $spaceUser ? $spaceUser->role->value : null,
            'has_permission' => $spaceUser ? $spaceUser->hasPermission(SpacePermission::VIEW_ALL_TASKS) : false,
            'space_id' => $spaceUser ? $spaceUser->tenant_id : null,
        ]);

        return $spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_ALL_TASKS);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        $spaceUser = $this->getSpaceUser($user, $task);

        // Check if user has permission to view all tasks
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_ALL_TASKS)) {
            return true;
        }

        // Check if user is assigned to this task
        return $user->id === $task->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $spaceUser = $this->getSpaceUser($user);

        return $spaceUser && $spaceUser->hasPermission(SpacePermission::CREATE_TASKS);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        $spaceUser = $this->getSpaceUser($user, $task);

        // Check if user has permission to edit any task
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::EDIT_ANY_TASK)) {
            return true;
        }

        // Check if user has permission to edit own tasks and is the task owner
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::EDIT_OWN_TASKS) && $user->id === $task->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        $spaceUser = $this->getSpaceUser($user, $task);

        // Check if user has permission to delete any task
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::DELETE_ANY_TASK)) {
            return true;
        }

        // Check if user has permission to delete own tasks and is the task owner
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::DELETE_OWN_TASKS) && $user->id === $task->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return $this->delete($user, $task);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        $spaceUser = $this->getSpaceUser($user, $task);

        return $spaceUser && ($spaceUser->isOwner() || $spaceUser->isAdmin());
    }

    /**
     * Determine whether the user can mark the task as completed.
     */
    public function complete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Determine whether the user can mark the task as in progress.
     */
    public function markAsInProgress(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Determine whether the user can add a comment to the task.
     */
    public function addComment(User $user, Task $task): bool
    {
        $spaceUser = $this->getSpaceUser($user, $task);

        // Check if user has permission to create comments
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::CREATE_COMMENTS);
    }
}
