<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProjectController extends Controller
{
    /**
     * Get projects assigned to the authenticated user.
     */
    public function assignedProjects(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // First, get the project IDs assigned to this user from the tenant database
        $assignedProjectData = \DB::connection('tenant')
            ->table('project_user')
            ->where('user_id', $user->id)
            ->get(['project_id', 'role']);
        
        // Extract project IDs
        $projectIds = $assignedProjectData->pluck('project_id')->toArray();
        
        // If no projects assigned, return empty
        if (empty($projectIds)) {
            return response()->json([
                'data' => [],
                'count' => 0,
            ]);
        }
        
        // Create a map of project_id => role for quick lookup
        $projectRoles = $assignedProjectData->pluck('role', 'project_id')->toArray();
        
        // Get projects where user is a member
        $projects = Project::query()
            ->with(['client:id,name', 'user:id,name'])
            ->withCount(['tasks', 'tasks as completed_tasks_count' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->whereIn('id', $projectIds)
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($project) use ($projectRoles) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'status' => $project->status,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                    'due_date' => $project->due_date,
                    'tasks_count' => $project->tasks_count,
                    'completed_tasks_count' => $project->completed_tasks_count,
                    'user_role' => $projectRoles[$project->id] ?? null,
                    'client' => $project->client,
                    'user' => $project->user,
                ];
            });

        return response()->json([
            'data' => $projects,
            'count' => $projects->count(),
        ]);
    }
}