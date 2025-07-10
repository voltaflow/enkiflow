<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProjectPermission;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectPermissionResolver
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_TAG = 'project-permissions';
    
    /**
     * Check if user has specific permission in project.
     */
    public function userHasPermission(
        User $user,
        Project $project,
        ProjectPermission $permission
    ): bool {
        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }
        
        // Space owner bypass for their own space
        if ($this->isSpaceOwner($user)) {
            return true;
        }
        
        // Get user's project permissions
        $permissions = $this->getUserProjectPermissions($user, $project);
        
        if (!$permissions) {
            return false;
        }
        
        // Check if permission is explicitly set
        $column = $permission->column();
        if ($permissions->{$column} !== null) {
            return (bool) $permissions->{$column};
        }
        
        // Fall back to role defaults
        try {
            $role = ProjectRole::from($permissions->role);
            return $permission->isDefaultForRole($role);
        } catch (\ValueError $e) {
            Log::warning('Invalid project role', [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'role' => $permissions->role,
            ]);
            return false;
        }
    }
    
    /**
     * Check if user has any of the specified permissions.
     */
    public function userHasAnyPermission(
        User $user,
        Project $project,
        array $permissions
    ): bool {
        foreach ($permissions as $permission) {
            if ($this->userHasPermission($user, $project, $permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has all of the specified permissions.
     */
    public function userHasAllPermissions(
        User $user,
        Project $project,
        array $permissions
    ): bool {
        foreach ($permissions as $permission) {
            if (!$this->userHasPermission($user, $project, $permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get user's role in project.
     */
    public function getUserRole(User $user, Project $project): ?ProjectRole
    {
        if ($this->isSuperAdmin($user) || $this->isSpaceOwner($user)) {
            return ProjectRole::ADMIN;
        }
        
        $permissions = $this->getUserProjectPermissions($user, $project);
        
        if (!$permissions) {
            return null;
        }
        
        try {
            return ProjectRole::from($permissions->role);
        } catch (\ValueError $e) {
            return null;
        }
    }
    
    /**
     * Get all permissions for user in project.
     */
    public function getUserPermissions(User $user, Project $project): Collection
    {
        $effectivePermissions = collect();
        
        // Super admin or space owner gets all permissions
        if ($this->isSuperAdmin($user) || $this->isSpaceOwner($user)) {
            return collect(ProjectPermission::cases());
        }
        
        $permissions = $this->getUserProjectPermissions($user, $project);
        
        if (!$permissions) {
            return $effectivePermissions;
        }
        
        // Get role defaults
        try {
            $role = ProjectRole::from($permissions->role);
            $defaultPermissions = ProjectPermission::defaultsForRole($role);
        } catch (\ValueError $e) {
            $defaultPermissions = [];
        }
        
        // Check each permission
        foreach (ProjectPermission::cases() as $permission) {
            $column = $permission->column();
            
            // Explicit permission overrides role default
            if ($permissions->{$column} !== null) {
                if ($permissions->{$column}) {
                    $effectivePermissions->push($permission);
                }
            } elseif (in_array($permission, $defaultPermissions, true)) {
                $effectivePermissions->push($permission);
            }
        }
        
        return $effectivePermissions;
    }
    
    /**
     * Grant permission to user for project.
     */
    public function grantPermission(
        User $user,
        Project $project,
        ProjectPermission $permission,
        ?User $grantedBy = null
    ): bool {
        $permissions = $this->getUserProjectPermissions($user, $project);
        
        if (!$permissions) {
            return false;
        }
        
        DB::connection('tenant')
            ->table('project_permissions')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->update([
                $permission->column() => true,
                'updated_by' => $grantedBy?->id,
                'updated_at' => now(),
            ]);
            
        $this->clearCache($user, $project);
        
        return true;
    }
    
    /**
     * Revoke permission from user for project.
     */
    public function revokePermission(
        User $user,
        Project $project,
        ProjectPermission $permission,
        ?User $revokedBy = null
    ): bool {
        $permissions = $this->getUserProjectPermissions($user, $project);
        
        if (!$permissions) {
            return false;
        }
        
        DB::connection('tenant')
            ->table('project_permissions')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->update([
                $permission->column() => false,
                'updated_by' => $revokedBy?->id,
                'updated_at' => now(),
            ]);
            
        $this->clearCache($user, $project);
        
        return true;
    }
    
    /**
     * Reset permission to role default.
     */
    public function resetPermission(
        User $user,
        Project $project,
        ProjectPermission $permission,
        ?User $resetBy = null
    ): bool {
        $permissions = $this->getUserProjectPermissions($user, $project);
        
        if (!$permissions) {
            return false;
        }
        
        DB::connection('tenant')
            ->table('project_permissions')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->update([
                $permission->column() => null,
                'updated_by' => $resetBy?->id,
                'updated_at' => now(),
            ]);
            
        $this->clearCache($user, $project);
        
        return true;
    }
    
    /**
     * Update user's role in project.
     */
    public function updateUserRole(
        User $user,
        Project $project,
        ProjectRole $role,
        ?User $updatedBy = null
    ): bool {
        $exists = DB::connection('tenant')
            ->table('project_permissions')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();
            
        if ($exists) {
            DB::connection('tenant')
                ->table('project_permissions')
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->update([
                    'role' => $role->value,
                    'updated_by' => $updatedBy?->id,
                    'updated_at' => now(),
                ]);
        } else {
            DB::connection('tenant')
                ->table('project_permissions')
                ->insert([
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'role' => $role->value,
                    'created_by' => $updatedBy?->id,
                    'updated_by' => $updatedBy?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }
        
        $this->clearCache($user, $project);
        
        return true;
    }
    
    /**
     * Get user's project permissions from database or cache.
     */
    private function getUserProjectPermissions(User $user, Project $project): ?object
    {
        $cacheKey = $this->getCacheKey($user, $project);
        
        return Cache::tags([self::CACHE_TAG, "user-{$user->id}", "project-{$project->id}"])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($user, $project) {
                return DB::connection('tenant')
                    ->table('project_permissions')
                    ->where('project_id', $project->id)
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    })
                    ->first();
            });
    }
    
    /**
     * Check if user is super admin.
     */
    private function isSuperAdmin(User $user): bool
    {
        return config('features.superadmin_bypass') && $user->is_super_admin;
    }
    
    /**
     * Check if user is space owner.
     */
    private function isSpaceOwner(User $user): bool
    {
        $space = tenant();
        return $space && $space->owner_id === $user->id;
    }
    
    /**
     * Get cache key for user-project permissions.
     */
    private function getCacheKey(User $user, Project $project): string
    {
        return "project_permissions:{$project->id}:user:{$user->id}";
    }
    
    /**
     * Clear cache for user-project permissions.
     */
    private function clearCache(User $user, Project $project): void
    {
        Cache::tags([
            self::CACHE_TAG,
            "user-{$user->id}",
            "project-{$project->id}",
        ])->flush();
    }
    
    /**
     * Clear all project permissions cache.
     */
    public function clearAllCache(): void
    {
        Cache::tags([self::CACHE_TAG])->flush();
    }
}