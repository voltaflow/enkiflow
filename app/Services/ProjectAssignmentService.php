<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;

class ProjectAssignmentService
{
    /**
     * Sync project assignments for a user.
     */
    public function syncUserProjects(
        User $user,
        array $projectIds,
        bool $allCurrentProjects = false,
        bool $allFutureProjects = false,
        ?float $defaultRate = null
    ): void {
        DB::transaction(function () use ($user, $projectIds, $allCurrentProjects, $allFutureProjects, $defaultRate) {
            // Prepare sync data
            $syncData = [];
            foreach ($projectIds as $projectId => $settings) {
                // Handle both simple array of IDs and associative array with settings
                if (is_numeric($settings)) {
                    $projectId = $settings;
                    $settings = [];
                }

                $syncData[$projectId] = [
                    'role' => $settings['role'] ?? 'member',
                    'custom_rate' => $settings['custom_rate'] ?? $defaultRate,
                    'all_current_projects' => $projectId === 'all' ? $allCurrentProjects : false,
                    'all_future_projects' => $projectId === 'all' ? $allFutureProjects : false,
                ];
            }

            // Handle the special "all projects" assignment
            if ($allCurrentProjects || $allFutureProjects) {
                // First, remove any existing individual project assignments
                $user->assignedProjects()->detach();
                
                // Create a special entry to indicate all projects access
                $user->assignedProjects()->attach(null, [
                    'all_current_projects' => $allCurrentProjects,
                    'all_future_projects' => $allFutureProjects,
                    'custom_rate' => $defaultRate,
                    'role' => 'member',
                ]);
            } else {
                // Sync specific projects
                $user->assignedProjects()->sync($syncData);
            }

            // Clear relevant caches
            $this->clearUserProjectCache($user);
        });
    }

    /**
     * Assign projects to a user with specific settings.
     */
    public function assignProjects(User $user, array $assignments): void
    {
        DB::transaction(function () use ($user, $assignments) {
            foreach ($assignments as $assignment) {
                if (isset($assignment['all_current_projects']) && $assignment['all_current_projects']) {
                    // Handle all current projects assignment
                    $this->assignAllCurrentProjects($user, $assignment);
                } elseif (isset($assignment['all_future_projects']) && $assignment['all_future_projects']) {
                    // Handle all future projects assignment
                    $this->assignAllFutureProjects($user, $assignment);
                } elseif (isset($assignment['project_ids'])) {
                    // Handle specific projects
                    $this->assignSpecificProjects($user, $assignment);
                }
            }

            $this->clearUserProjectCache($user);
        });
    }

    /**
     * Assign all current projects to a user.
     */
    protected function assignAllCurrentProjects(User $user, array $settings): void
    {
        // Remove existing individual assignments
        $user->assignedProjects()
            ->wherePivot('all_current_projects', false)
            ->wherePivot('all_future_projects', false)
            ->detach();

        // Check if user already has all current projects
        $existing = $user->assignedProjects()
            ->wherePivot('all_current_projects', true)
            ->first();

        if ($existing) {
            // Update existing
            $user->assignedProjects()->updateExistingPivot($existing->id, [
                'role' => $settings['role'] ?? 'member',
                'custom_rate' => $settings['custom_rate'] ?? null,
            ]);
        } else {
            // Create new
            $user->assignedProjects()->attach(null, [
                'all_current_projects' => true,
                'all_future_projects' => false,
                'role' => $settings['role'] ?? 'member',
                'custom_rate' => $settings['custom_rate'] ?? null,
            ]);
        }
    }

    /**
     * Assign all future projects to a user.
     */
    protected function assignAllFutureProjects(User $user, array $settings): void
    {
        // Check if user already has all future projects
        $existing = $user->assignedProjects()
            ->wherePivot('all_future_projects', true)
            ->first();

        if ($existing) {
            // Update existing
            $user->assignedProjects()->updateExistingPivot($existing->id, [
                'role' => $settings['role'] ?? 'member',
                'custom_rate' => $settings['custom_rate'] ?? null,
            ]);
        } else {
            // Create new
            $user->assignedProjects()->attach(null, [
                'all_current_projects' => false,
                'all_future_projects' => true,
                'role' => $settings['role'] ?? 'member',
                'custom_rate' => $settings['custom_rate'] ?? null,
            ]);
        }
    }

    /**
     * Assign specific projects to a user.
     */
    protected function assignSpecificProjects(User $user, array $settings): void
    {
        $projectIds = $settings['project_ids'];
        $syncData = [];

        foreach ($projectIds as $projectId) {
            $syncData[$projectId] = [
                'role' => $settings['role'] ?? 'member',
                'custom_rate' => $settings['custom_rate'] ?? null,
                'all_current_projects' => false,
                'all_future_projects' => false,
            ];
        }

        // Use syncWithoutDetaching to preserve other assignments
        $user->assignedProjects()->syncWithoutDetaching($syncData);
    }

    /**
     * Remove project assignments from a user.
     */
    public function removeProjects(User $user, array $projectIds): void
    {
        DB::transaction(function () use ($user, $projectIds) {
            $user->assignedProjects()->detach($projectIds);
            $this->clearUserProjectCache($user);
        });
    }

    /**
     * Remove all project assignments from a user.
     */
    public function removeAllProjects(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->assignedProjects()->detach();
            $this->clearUserProjectCache($user);
        });
    }

    /**
     * Update user's role for a specific project.
     */
    public function updateProjectRole(User $user, Project $project, string $role): void
    {
        $user->assignedProjects()->updateExistingPivot($project->id, [
            'role' => $role,
        ]);

        $this->clearUserProjectCache($user);
        $this->clearProjectUserCache($project);
    }

    /**
     * Update user's custom rate for a specific project.
     */
    public function updateProjectRate(User $user, Project $project, ?float $rate): void
    {
        $user->assignedProjects()->updateExistingPivot($project->id, [
            'custom_rate' => $rate,
        ]);

        $this->clearUserProjectCache($user);
    }

    /**
     * Get users with access to a project (including those with all projects access).
     */
    public function getProjectUsers(Project $project): Collection
    {
        // Get directly assigned users
        $directUsers = $project->assignedUsers;

        // Get users with all current projects access
        $allCurrentUsers = User::whereHas('assignedProjects', function ($query) {
            $query->wherePivot('all_current_projects', true);
        })->get();

        // Merge and return unique users
        return $directUsers->merge($allCurrentUsers)->unique('id');
    }

    /**
     * Get available projects for a user to be assigned to.
     */
    public function getAvailableProjects(User $user): Collection
    {
        // Get all projects
        $allProjects = Project::active()->get();

        // Get already assigned project IDs
        $assignedIds = $user->assignedProjects()->pluck('projects.id')->toArray();

        // Return projects not yet assigned (unless user has all projects access)
        if ($user->hasAccessToAllCurrentProjects()) {
            return collect(); // No projects available if user has all access
        }

        return $allProjects->whereNotIn('id', $assignedIds);
    }

    /**
     * Check if a user has access to a project.
     */
    public function userHasProjectAccess(User $user, Project $project): bool
    {
        // Check if user has all current projects access
        if ($user->hasAccessToAllCurrentProjects()) {
            return true;
        }

        // Check if user is directly assigned to the project
        return $user->assignedProjects()->where('projects.id', $project->id)->exists();
    }

    /**
     * Clear user project cache.
     */
    protected function clearUserProjectCache(User $user): void
    {
        $tenantId = tenant('id');
        Cache::tags([
            'tenant:' . $tenantId,
            'user:' . $user->id,
            'projects'
        ])->flush();
    }

    /**
     * Clear project user cache.
     */
    public function clearProjectUserCache(Project $project): void
    {
        $tenantId = tenant('id');
        Cache::tags([
            'tenant:' . $tenantId,
            'project:' . $project->id,
            'users'
        ])->flush();
    }

    /**
     * Automatically assign user to new projects if they have all_future_projects enabled.
     */
    public function handleNewProject(Project $project): void
    {
        // Find all users with all_future_projects enabled
        $usersWithFutureAccess = User::whereHas('assignedProjects', function ($query) {
            $query->wherePivot('all_future_projects', true);
        })->get();

        foreach ($usersWithFutureAccess as $user) {
            // Get the settings from their all_future_projects assignment
            $settings = $user->assignedProjects()
                ->wherePivot('all_future_projects', true)
                ->first();

            if ($settings) {
                $project->assignedUsers()->attach($user->id, [
                    'role' => $settings->pivot->role,
                    'custom_rate' => $settings->pivot->custom_rate,
                    'all_current_projects' => false,
                    'all_future_projects' => false,
                ]);
            }
        }
    }
}