<?php

namespace App\Models;

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
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if the user is an admin in this space.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user is a member in this space.
     *
     * @return bool
     */
    public function isMember(): bool
    {
        return $this->hasRole('member');
    }
}
