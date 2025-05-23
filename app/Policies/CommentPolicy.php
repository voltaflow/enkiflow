<?php

namespace App\Policies;

use App\Enums\SpacePermission;
use App\Models\Comment;
use App\Models\Space;
use App\Models\SpaceUser;
use App\Models\User;

class CommentPolicy
{
    /**
     * Get the SpaceUser record for the given user and comment's space.
     */
    protected function getSpaceUser(User $user, ?Comment $comment = null): ?SpaceUser
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
     * Determine whether the user can view the model.
     */
    public function view(User $user, Comment $comment): bool
    {
        // Comments are visible to anyone who can view the task
        return app(TaskPolicy::class)->view($user, $comment->task);
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

        $spaceUser = $this->getSpaceUser($user);

        return $spaceUser && $spaceUser->hasPermission(SpacePermission::CREATE_COMMENTS);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment): bool
    {
        $spaceUser = $this->getSpaceUser($user, $comment);

        // Check if user has permission to edit any comment
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::EDIT_ANY_COMMENT)) {
            return true;
        }

        // Check if user has permission to edit own comments and is the comment owner
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::EDIT_OWN_COMMENTS) && $user->id === $comment->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        $spaceUser = $this->getSpaceUser($user, $comment);

        // Check if user has permission to delete any comment
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::DELETE_ANY_COMMENT)) {
            return true;
        }

        // Check if user has permission to delete own comments and is the comment owner
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::DELETE_OWN_COMMENTS) && $user->id === $comment->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Comment $comment): bool
    {
        return $this->delete($user, $comment);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        $spaceUser = $this->getSpaceUser($user, $comment);

        return $spaceUser && ($spaceUser->isOwner() || $spaceUser->isAdmin());
    }
}
