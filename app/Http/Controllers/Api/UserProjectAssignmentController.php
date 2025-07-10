<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Project;
use App\Services\ProjectAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserProjectAssignmentController extends Controller
{
    public function __construct(
        protected ProjectAssignmentService $assignmentService
    ) {}

    /**
     * Get user's assigned projects.
     */
    public function index(User $user): JsonResponse
    {
        // Get assigned project IDs from tenant database
        $assignedProjectIds = DB::connection('tenant')
            ->table('project_user')
            ->where('user_id', $user->id)
            ->get(['project_id', 'role', 'custom_rate', 'all_current_projects', 'all_future_projects']);

        // Get project details
        $projects = collect();
        if ($assignedProjectIds->count() > 0) {
            $projectIds = $assignedProjectIds->pluck('project_id')->toArray();
            $projectsData = Project::whereIn('id', $projectIds)->get();
            
            $projects = $projectsData->map(function ($project) use ($assignedProjectIds) {
                $pivot = $assignedProjectIds->firstWhere('project_id', $project->id);
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'client' => $project->client ? $project->client->name : null,
                    'role' => $pivot->role ?? 'member',
                    'custom_rate' => $pivot->custom_rate,
                    'all_current_projects' => $pivot->all_current_projects ?? false,
                    'all_future_projects' => $pivot->all_future_projects ?? false,
                ];
            });
        }

        // Check for special access flags
        $hasAllCurrent = $assignedProjectIds->where('all_current_projects', true)->count() > 0;
        $hasAllFuture = $assignedProjectIds->where('all_future_projects', true)->count() > 0;

        return response()->json([
            'data' => [
                'projects' => $projects,
                'has_all_current_projects' => $hasAllCurrent,
                'has_all_future_projects' => $hasAllFuture,
                'total_assigned' => $projects->count(),
            ]
        ]);
    }

    /**
     * Get projects available to assign to the user.
     */
    public function available(User $user): JsonResponse
    {
        // Get assigned project IDs
        $assignedProjectIds = DB::connection('tenant')
            ->table('project_user')
            ->where('user_id', $user->id)
            ->pluck('project_id')
            ->toArray();

        // Get all active projects not yet assigned
        $availableProjects = Project::active()
            ->whereNotIn('id', $assignedProjectIds)
            ->get(['id', 'name', 'status'])
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client' => $project->client ? $project->client->name : null,
                    'status' => $project->status,
                ];
            });

        return response()->json([
            'data' => $availableProjects
        ]);
    }

    /**
     * Assign projects to user.
     */
    public function store(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'assignments' => 'required|array',
            'assignments.*.project_ids' => 'sometimes|array',
            'assignments.*.project_ids.*' => 'integer|exists:projects,id',
            'assignments.*.all_current_projects' => 'sometimes|boolean',
            'assignments.*.all_future_projects' => 'sometimes|boolean',
            'assignments.*.role' => 'required|string|in:member,manager,viewer',
            'assignments.*.custom_rate' => 'nullable|numeric|min:0',
        ]);

        $totalAssigned = 0;

        foreach ($validated['assignments'] as $assignment) {
            $role = $assignment['role'];
            $customRate = $assignment['custom_rate'] ?? null;

            if (!empty($assignment['project_ids'])) {
                // Assign specific projects
                $insertData = [];
                foreach ($assignment['project_ids'] as $projectId) {
                    $insertData[] = [
                        'user_id' => $user->id,
                        'project_id' => $projectId,
                        'role' => $role,
                        'custom_rate' => $customRate,
                        'all_current_projects' => false,
                        'all_future_projects' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                DB::connection('tenant')
                    ->table('project_user')
                    ->insertOrIgnore($insertData);
                    
                $totalAssigned += count($assignment['project_ids']);
            } elseif (!empty($assignment['all_current_projects'])) {
                // Assign all current projects
                $projects = Project::active()->pluck('id');
                foreach ($projects as $projectId) {
                    DB::connection('tenant')
                        ->table('project_user')
                        ->updateOrInsert(
                            ['user_id' => $user->id, 'project_id' => $projectId],
                            [
                                'role' => $role,
                                'custom_rate' => $customRate,
                                'all_current_projects' => true,
                                'all_future_projects' => false,
                                'updated_at' => now(),
                            ]
                        );
                }
                $totalAssigned = $projects->count();
            } elseif (!empty($assignment['all_future_projects'])) {
                // Set flag for all future projects
                DB::connection('tenant')
                    ->table('project_user')
                    ->updateOrInsert(
                        ['user_id' => $user->id, 'project_id' => 0], // Use 0 as placeholder for "all future"
                        [
                            'role' => $role,
                            'custom_rate' => $customRate,
                            'all_current_projects' => false,
                            'all_future_projects' => true,
                            'updated_at' => now(),
                        ]
                    );
                $totalAssigned = 1;
            }
        }

        // Clear cache
        // $this->assignmentService->clearUserProjectCache($user);

        return response()->json([
            'message' => 'Projects assigned successfully',
            'data' => [
                'assigned_count' => $totalAssigned,
            ]
        ], 201);
    }

    /**
     * Update user's role or rate in a project.
     */
    public function update(Request $request, User $user, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'sometimes|string|in:member,manager,viewer',
            'custom_rate' => 'sometimes|nullable|numeric|min:0',
        ]);

        // Check if user is assigned to project
        $exists = DB::connection('tenant')
            ->table('project_user')
            ->where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->exists();

        if (!$exists) {
            return response()->json([
                'message' => 'User is not assigned to this project'
            ], 404);
        }

        // Update
        $updateData = $validated;
        $updateData['updated_at'] = now();
        
        DB::connection('tenant')
            ->table('project_user')
            ->where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->update($updateData);

        // Clear cache
        // $this->assignmentService->clearUserProjectCache($user);

        return response()->json([
            'message' => 'Project assignment updated successfully',
            'data' => array_merge(['project_id' => $project->id], $validated)
        ]);
    }

    /**
     * Remove project from user.
     */
    public function destroy(User $user, Project $project): JsonResponse
    {
        // Check if user is assigned to project
        $exists = DB::connection('tenant')
            ->table('project_user')
            ->where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->exists();

        if (!$exists) {
            return response()->json([
                'message' => 'User is not assigned to this project'
            ], 404);
        }

        // Delete
        DB::connection('tenant')
            ->table('project_user')
            ->where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->delete();

        // Clear cache
        // $this->assignmentService->clearUserProjectCache($user);

        return response()->json([
            'message' => 'Project removed from user successfully'
        ]);
    }
}