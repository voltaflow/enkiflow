<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectUserAssignmentController extends Controller
{
    public function __construct(
        protected ProjectAssignmentService $assignmentService
    ) {}

    /**
     * Get project's assigned users.
     */
    public function index(Project $project): JsonResponse
    {
        // $this->authorize('viewMembers', $project);

        // Use raw query to get assigned users from tenant database
        $assignedUserIds = DB::connection('tenant')
            ->table('project_user')
            ->where('project_id', $project->id)
            ->pluck('user_id', 'user_id')
            ->toArray();

        // Get user details from central database
        $members = collect();
        if (!empty($assignedUserIds)) {
            $users = User::whereIn('id', array_keys($assignedUserIds))->get();
            
            // Get pivot data from tenant database
            $pivotData = DB::connection('tenant')
                ->table('project_user')
                ->where('project_id', $project->id)
                ->whereIn('user_id', array_keys($assignedUserIds))
                ->get()
                ->keyBy('user_id');
            
            $members = $users->map(function ($user) use ($pivotData) {
                $pivot = $pivotData[$user->id] ?? null;
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $pivot->role ?? 'member',
                    'custom_rate' => $pivot->custom_rate ?? null,
                ];
            });
        }

        // Get available users (not yet assigned to this project)
        $availableUsers = $this->getAvailableUsers($project);

        return response()->json([
            'data' => [
                'members' => $members,
                'available_users' => $availableUsers,
                'total_members' => $members->count(),
            ]
        ]);
    }

    /**
     * Get users available to be assigned to the project.
     */
    public function available(Project $project): JsonResponse
    {
        // $this->authorize('manageMembers', $project);

        $availableUsers = $this->getAvailableUsers($project);

        return response()->json([
            'data' => $availableUsers
        ]);
    }

    /**
     * Assign users to project.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        // $this->authorize('manageMembers', $project);

        try {
            Log::info('ProjectUserAssignment::store - Starting', [
                'project_id' => $project->id,
                'request_data' => $request->all(),
                'tenant' => tenant()->id ?? 'no-tenant'
            ]);

            $validated = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'required|integer|exists:central.users,id',
                'role' => 'sometimes|string|in:member,manager,viewer',
                'custom_rate' => 'sometimes|nullable|numeric|min:0',
            ]);

            $role = $validated['role'] ?? 'member';
            $customRate = $validated['custom_rate'] ?? null;

        // Insert directly into tenant database
        $insertData = [];
        foreach ($validated['user_ids'] as $userId) {
            $insertData[] = [
                'project_id' => $project->id,
                'user_id' => $userId,
                'role' => $role,
                'custom_rate' => $customRate,
                'all_current_projects' => false,
                'all_future_projects' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert ignoring duplicates
        DB::connection('tenant')
            ->table('project_user')
            ->insertOrIgnore($insertData);

        // Clear cache
        $this->assignmentService->clearProjectUserCache($project);

            Log::info('ProjectUserAssignment::store - Success', [
                'assigned_count' => count($validated['user_ids'])
            ]);

            return response()->json([
                'message' => 'Users assigned successfully',
                'data' => [
                    'assigned_count' => count($validated['user_ids']),
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('ProjectUserAssignment::store - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update user's role or rate in the project.
     */
    public function update(Request $request, Project $project, User $user): JsonResponse
    {
        // $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'role' => 'sometimes|string|in:member,manager,viewer',
            'custom_rate' => 'sometimes|nullable|numeric|min:0',
        ]);

        // Check if user is assigned to project using tenant connection
        $exists = DB::connection('tenant')
            ->table('project_user')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$exists) {
            return response()->json([
                'message' => 'User is not assigned to this project'
            ], 404);
        }

        // Update in tenant database
        $updateData = $validated;
        $updateData['updated_at'] = now();
        
        DB::connection('tenant')
            ->table('project_user')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->update($updateData);

        // Clear cache
        $this->assignmentService->clearProjectUserCache($project);

        return response()->json([
            'message' => 'User assignment updated successfully',
            'data' => array_merge(['user_id' => $user->id], $validated)
        ]);
    }

    /**
     * Remove user from project.
     */
    public function destroy(Project $project, User $user): JsonResponse
    {
        // $this->authorize('manageMembers', $project);

        // Check if user is assigned to project using tenant connection
        $exists = DB::connection('tenant')
            ->table('project_user')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$exists) {
            return response()->json([
                'message' => 'User is not assigned to this project'
            ], 404);
        }

        // Delete from tenant database
        DB::connection('tenant')
            ->table('project_user')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->delete();

        // Clear cache
        $this->assignmentService->clearProjectUserCache($project);

        return response()->json([
            'message' => 'User removed from project successfully'
        ]);
    }

    /**
     * Get available users for the project.
     */
    protected function getAvailableUsers(Project $project): \Illuminate\Support\Collection
    {
        // Get all users in the current space
        $space = tenant();
        
        // Get assigned user IDs from tenant database
        $assignedUserIds = DB::connection('tenant')
            ->table('project_user')
            ->where('project_id', $project->id)
            ->pluck('user_id')
            ->toArray();

        // Get all space users that are not already assigned to this project
        return $space->users()
            ->whereNotIn('users.id', $assignedUserIds)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role, // Role in the space
                ];
            });
    }
}