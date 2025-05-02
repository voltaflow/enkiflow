<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SpacePolicy
{
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
        return $user->id === $space->owner_id || $space->users()->where('user_id', $user->id)->exists();
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
        // Only the owner or admin can update the space
        return $user->id === $space->owner_id || 
               $space->users()->where('user_id', $user->id)->wherePivot('role', 'admin')->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Space $space): bool
    {
        // Only the owner can delete the space
        return $user->id === $space->owner_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Space $space): bool
    {
        // Only the owner can restore the space
        return $user->id === $space->owner_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Space $space): bool
    {
        // Only the owner can force delete the space
        return $user->id === $space->owner_id;
    }

    /**
     * Determine whether the user can invite others to the space.
     */
    public function invite(User $user, Space $space): bool
    {
        // Owner or admin can invite others
        return $user->id === $space->owner_id || 
               $space->users()->where('user_id', $user->id)->wherePivot('role', 'admin')->exists();
    }

    /**
     * Determine whether the user can remove others from the space.
     */
    public function removeUser(User $user, Space $space): bool
    {
        // Owner or admin can remove users
        return $user->id === $space->owner_id || 
               $space->users()->where('user_id', $user->id)->wherePivot('role', 'admin')->exists();
    }

    /**
     * Determine whether the user can manage billing for the space.
     */
    public function manageBilling(User $user, Space $space): bool
    {
        // Only the owner can manage billing
        return $user->id === $space->owner_id;
    }
}
