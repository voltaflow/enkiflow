<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ProjectPermission;
use App\Enums\ProjectRole;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectPermissionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProjectPermissionController extends Controller
{
    public function __construct(
        protected ProjectPermissionResolver $permissionResolver
    ) {}

    /**
     * Get project permissions for a user.
     */
    public function show(Project $project, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $permissions = DB::connection('tenant')
            ->table('project_permissions')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$permissions) {
            return response()->json([
                'message' => 'User has no permissions for this project'
            ], 404);
        }

        // Get effective permissions
        $effectivePermissions = $this->permissionResolver->getUserPermissions($user, $project);

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'project_id' => $project->id,
                'project_name' => $project->name,
                'role' => $permissions->role,
                'is_active' => $permissions->is_active,
                'expires_at' => $permissions->expires_at,
                'explicit_permissions' => [
                    'can_manage_project' => $permissions->can_manage_project,
                    'can_manage_members' => $permissions->can_manage_members,
                    'can_edit_content' => $permissions->can_edit_content,
                    'can_delete_content' => $permissions->can_delete_content,
                    'can_view_reports' => $permissions->can_view_reports,
                    'can_view_budget' => $permissions->can_view_budget,
                    'can_export_data' => $permissions->can_export_data,
                    'can_track_time' => $permissions->can_track_time,
                    'can_view_all_time_entries' => $permissions->can_view_all_time_entries,
                    'can_manage_integrations' => $permissions->can_manage_integrations,
                ],
                'effective_permissions' => $effectivePermissions->map(fn($p) => $p->value)->values(),
                'created_at' => $permissions->created_at,
                'updated_at' => $permissions->updated_at,
            ]
        ]);
    }

    /**
     * Update user's role in project.
     */
    public function updateRole(Request $request, Project $project, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(ProjectRole::values())],
        ]);

        try {
            $role = ProjectRole::from($validated['role']);
            
            $success = $this->permissionResolver->updateUserRole(
                $user,
                $project,
                $role,
                $request->user()
            );

            if (!$success) {
                return response()->json([
                    'message' => 'Failed to update user role'
                ], 500);
            }

            Log::info('Project user role updated', [
                'project_id' => $project->id,
                'user_id' => $user->id,
                'new_role' => $role->value,
                'updated_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'User role updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'role' => $role->value,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update project user role', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Failed to update user role'
            ], 500);
        }
    }

    /**
     * Update specific permissions for a user.
     */
    public function updatePermissions(Request $request, Project $project, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => ['required', 'string', Rule::in(array_column(ProjectPermission::cases(), 'value'))],
            'action' => 'required|in:grant,revoke,reset',
        ]);

        $results = [];
        $errors = [];

        foreach ($validated['permissions'] as $permissionValue) {
            try {
                $permission = ProjectPermission::from($permissionValue);
                
                $success = match($validated['action']) {
                    'grant' => $this->permissionResolver->grantPermission($user, $project, $permission, $request->user()),
                    'revoke' => $this->permissionResolver->revokePermission($user, $project, $permission, $request->user()),
                    'reset' => $this->permissionResolver->resetPermission($user, $project, $permission, $request->user()),
                };

                if ($success) {
                    $results[] = [
                        'permission' => $permissionValue,
                        'action' => $validated['action'],
                        'status' => 'success',
                    ];
                } else {
                    $errors[] = [
                        'permission' => $permissionValue,
                        'action' => $validated['action'],
                        'status' => 'failed',
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'permission' => $permissionValue,
                    'action' => $validated['action'],
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        Log::info('Project permissions updated', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'action' => $validated['action'],
            'permissions' => $validated['permissions'],
            'updated_by' => $request->user()->id,
            'results' => $results,
            'errors' => $errors,
        ]);

        $statusCode = empty($errors) ? 200 : 207; // 207 Multi-Status if partial success

        return response()->json([
            'message' => empty($errors) ? 'Permissions updated successfully' : 'Some permissions could not be updated',
            'data' => [
                'successful' => $results,
                'failed' => $errors,
            ]
        ], $statusCode);
    }

    /**
     * Get permission options for UI.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'data' => [
                'roles' => ProjectRole::options(),
                'permissions' => ProjectPermission::grouped(),
                'permission_details' => collect(ProjectPermission::cases())->map(fn($p) => [
                    'value' => $p->value,
                    'label' => $p->label(),
                    'description' => $p->description(),
                ])->values(),
            ]
        ]);
    }

    /**
     * Add user to project with specific role.
     */
    public function addUser(Request $request, Project $project): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:central.users,id',
            'role' => ['required', 'string', Rule::in(ProjectRole::values())],
            'expires_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if user already has permissions
        $exists = DB::connection('tenant')
            ->table('project_permissions')
            ->where('project_id', $project->id)
            ->where('user_id', $validated['user_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'User already has permissions for this project'
            ], 422);
        }

        // Add user with role
        DB::connection('tenant')
            ->table('project_permissions')
            ->insert([
                'project_id' => $project->id,
                'user_id' => $validated['user_id'],
                'role' => $validated['role'],
                'is_active' => true,
                'expires_at' => $validated['expires_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // Clear cache
        $this->permissionResolver->clearCache(User::find($validated['user_id']), $project);

        Log::info('User added to project', [
            'project_id' => $project->id,
            'user_id' => $validated['user_id'],
            'role' => $validated['role'],
            'added_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'User added to project successfully',
            'data' => [
                'user_id' => $validated['user_id'],
                'project_id' => $project->id,
                'role' => $validated['role'],
            ]
        ], 201);
    }

    /**
     * Remove user from project.
     */
    public function removeUser(Project $project, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $deleted = DB::connection('tenant')
            ->table('project_permissions')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'message' => 'User has no permissions for this project'
            ], 404);
        }

        // Clear cache
        $this->permissionResolver->clearCache($user, $project);

        Log::info('User removed from project', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'removed_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'User removed from project successfully'
        ]);
    }
}