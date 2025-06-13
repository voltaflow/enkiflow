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
        if (! $space) {
            return null;
        }
        
        // Ensure we have a Space model instance
        if (is_string($space)) {
            $space = Space::find($space);
            if (!$space) {
                return null;
            }
        }

        // If the user is the owner, create a virtual SpaceUser with the owner role
        if ($user->id === $space->owner_id) {
            $spaceUser = new SpaceUser();
            $spaceUser->tenant_id = (string)$space->getKey();
            $spaceUser->user_id = $user->id;
            $spaceUser->role = SpaceRole::OWNER;

            return $spaceUser;
        }

        $spaceId = (string)$space->getKey();
        $cacheKey = "space_user:{$spaceId}:{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($spaceId, $user) {
            // Use the central database connection to check space membership
            $spaceUser = SpaceUser::where('tenant_id', $spaceId)
                ->where('user_id', $user->id)
                ->first();
            
            return $spaceUser;
        });
    }

    /**
     * Check if the user has the given permission in the current space.
     */
    protected function userHasPermission(User $user, SpacePermission $permission): bool
    {
        $spaceUser = $this->getSpaceUser($user);
        if (! $spaceUser) {
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
        if (! $spaceUser) {
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
        if (! $spaceUser) {
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
        if (! $spaceUser) {
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
        if (! $spaceUser) {
            return null;
        }

        return $spaceUser->role;
    }
}
