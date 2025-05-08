<?php

namespace App\Traits;

use App\Enums\SpacePermission;
use App\Enums\SpaceRole;
use App\Models\Space;
use App\Models\SpaceUser;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

trait HasSpacePermissions
{
    /**
     * Get the current space.
     */
    protected function getCurrentSpace(): ?Space
    {
        return tenant();
    }
    
    /**
     * Get the SpaceUser record for the given user and space.
     */
    protected function getSpaceUser(User $user, ?Space $space = null): ?SpaceUser
    {
        $space = $space ?? $this->getCurrentSpace();
        if (!$space) {
            return null;
        }
        
        // If the user is the owner, create a virtual SpaceUser with the owner role
        if ($user->id === $space->owner_id) {
            $spaceUser = app(SpaceUser::class);
            $spaceUser->tenant_id = $space->id;
            $spaceUser->user_id = $user->id;
            $spaceUser->role = SpaceRole::OWNER;
            return $spaceUser;
        }
        
        $cacheKey = "space_user:{$space->id}:{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($space, $user) {
            return $space->users()->where('user_id', $user->id)->first();
        });
    }
    
    /**
     * Check if the user has the given permission in the current space.
     */
    protected function userHasPermission(User $user, SpacePermission $permission): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        if (!$spaceUser) {
            return false;
        }
        
        return $spaceUser->hasPermission($permission);
    }
    
    /**
     * Check if the user has the given role in the current space.
     */
    protected function userHasRole(User $user, SpaceRole $role): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        if (!$spaceUser) {
            return false;
        }
        
        return $spaceUser->hasRole($role);
    }
    
    /**
     * Check if the user has a role equal or higher than the given role in the current space.
     */
    protected function userHasRoleEqualOrHigherThan(User $user, SpaceRole $role): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        if (!$spaceUser) {
            return false;
        }
        
        return $spaceUser->hasRoleEqualOrHigherThan($role);
    }
    
    /**
     * Get all permissions for a user in the current space.
     */
    protected function getUserPermissions(User $user): array
    {
        $spaceUser = $this->getSpaceUser($user);
        if (!$spaceUser) {
            return [];
        }
        
        return $spaceUser->getPermissions();
    }
    
    /**
     * Get the role of a user in the current space.
     */
    protected function getUserRole(User $user): ?SpaceRole
    {
        $spaceUser = $this->getSpaceUser($user);
        if (!$spaceUser) {
            return null;
        }
        
        return $spaceUser->role;
    }
}