<?php

namespace App\Models;

use App\Enums\SpacePermission;
use App\Enums\SpaceRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SpaceUser extends Pivot
{
    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'central';

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
        'custom_permissions',
        'additional_permissions',
        'revoked_permissions',
        'capacity_hours',
        'cost_rate',
        'billable_rate',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'role' => SpaceRole::class,
        'custom_permissions' => 'array',
        'additional_permissions' => 'array',
        'revoked_permissions' => 'array',
        'capacity_hours' => 'integer',
        'cost_rate' => 'decimal:2',
        'billable_rate' => 'decimal:2',
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
        // Si el usuario tiene permisos personalizados, verificar solo esos
        if (!empty($this->custom_permissions)) {
            return in_array($permission->value, $this->custom_permissions);
        }
        
        // Verificar si el permiso está revocado para este usuario
        if (!empty($this->revoked_permissions) && in_array($permission->value, $this->revoked_permissions)) {
            return false;
        }
        
        // Verificar si el permiso está en los permisos adicionales
        if (!empty($this->additional_permissions) && in_array($permission->value, $this->additional_permissions)) {
            return true;
        }
        
        // Verificar si el rol tiene el permiso
        return SpacePermission::roleHasPermission($this->role, $permission);
    }

    /**
     * Get all permissions for this user in this space.
     */
    public function getPermissions(): array
    {
        // Si hay permisos personalizados, devolver solo esos
        if (!empty($this->custom_permissions)) {
            return array_map(function($permissionValue) {
                return SpacePermission::from($permissionValue);
            }, $this->custom_permissions);
        }
        
        // Obtener permisos del rol
        $rolePermissions = SpacePermission::permissionsForRole($this->role);
        
        // Añadir permisos adicionales
        if (!empty($this->additional_permissions)) {
            foreach ($this->additional_permissions as $permissionValue) {
                $permission = SpacePermission::from($permissionValue);
                if (!in_array($permission, $rolePermissions)) {
                    $rolePermissions[] = $permission;
                }
            }
        }
        
        // Eliminar permisos revocados
        if (!empty($this->revoked_permissions)) {
            $rolePermissions = array_filter($rolePermissions, function($permission) {
                return !in_array($permission->value, $this->revoked_permissions);
            });
        }
        
        return $rolePermissions;
    }
}
