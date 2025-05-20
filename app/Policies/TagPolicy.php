<?php

namespace App\Policies;

use App\Enums\SpacePermission;
use App\Models\Space;
use App\Models\SpaceUser;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TagPolicy
{
    /**
     * Get the SpaceUser record for the given user and space.
     */
    protected function getSpaceUser(User $user): ?SpaceUser
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
        if (!$space) {
            return false;
        }
        
        // Tags are visible to anyone who can view the space
        $spaceUser = $this->getSpaceUser($user);
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_SPACE);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tag $tag): bool
    {
        // Tags are visible to anyone who can view the space
        $spaceUser = $this->getSpaceUser($user);
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_SPACE);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_TAGS);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tag $tag): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_TAGS);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tag $tag): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_TAGS);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tag $tag): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_TAGS);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tag $tag): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        return $spaceUser && ($spaceUser->isOwner() || $spaceUser->isAdmin());
    }
    
    /**
     * Determine whether the user can apply tags to models.
     */
    public function applyTag(User $user): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        return $spaceUser && $spaceUser->hasPermission(SpacePermission::MANAGE_TAGS);
    }
}