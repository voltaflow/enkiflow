<?php

namespace App\Models;

use App\Enums\SpacePermission;
use App\Enums\SpaceRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SpaceUser extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'role',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'role' => SpaceRole::class,
    ];

    /**
     * The user that belongs to the space.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The space that the user belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class, 'tenant_id', 'id');
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'space_users';

    /**
     * Check if the user has the given role in this space.
     */
    public function hasRole(SpaceRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if the user has a role with equal or higher permissions than the given role.
     */
    public function hasRoleEqualOrHigherThan(SpaceRole $role): bool
    {
        return $this->role->equalOrHigherThan($role);
    }

    /**
     * Check if the user has a role with higher permissions than the given role.
     */
    public function hasRoleHigherThan(SpaceRole $role): bool
    {
        return $this->role->higherThan($role);
    }

    /**
     * Check if the user is an owner of this space.
     */
    public function isOwner(): bool
    {
        return $this->hasRole(SpaceRole::OWNER);
    }

    /**
     * Check if the user is an admin in this space.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(SpaceRole::ADMIN);
    }

    /**
     * Check if the user is a manager in this space.
     */
    public function isManager(): bool
    {
        return $this->hasRole(SpaceRole::MANAGER);
    }

    /**
     * Check if the user is a member in this space.
     */
    public function isMember(): bool
    {
        return $this->hasRole(SpaceRole::MEMBER);
    }

    /**
     * Check if the user is a guest in this space.
     */
    public function isGuest(): bool
    {
        return $this->hasRole(SpaceRole::GUEST);
    }

    /**
     * Check if the user has the given permission in this space.
     */
    public function hasPermission(SpacePermission $permission): bool
    {
        return SpacePermission::roleHasPermission($this->role, $permission);
    }

    /**
     * Get all permissions for this user in this space.
     */
    public function getPermissions(): array
    {
        return SpacePermission::permissionsForRole($this->role);
    }
}
