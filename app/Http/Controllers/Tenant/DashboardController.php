<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\SpacePermission;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Traits\HasSpacePermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use HasSpacePermissions;
    
    /**
     * Display the dashboard.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $spaceUser = $this->getSpaceUser($user);
        
        // Basic statistics for all users
        $basicStats = $this->getBasicStats($user);
        
        // Extended statistics for users with VIEW_STATISTICS permission
        $extendedStats = null;
        if ($spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_STATISTICS)) {
            $extendedStats = $this->getExtendedStats();
        }
        
        // Project statistics
        $projectStats = $this->getProjectStats();
        
        // Task Statistics
        $taskStats = $this->getTaskStats($user);
        
        // User activity for admins
        $userActivity = null;
        if ($spaceUser && ($spaceUser->isAdmin() || $spaceUser->isOwner())) {
            $userActivity = $this->getUserActivity();
        }
        
        return Inertia::render('Tenant/Dashboard', [
            'basicStats' => $basicStats,
            'extendedStats' => $extendedStats,
            'projectStats' => $projectStats,
            'taskStats' => $taskStats,
            'userActivity' => $userActivity,
            'canViewStats' => $spaceUser && $spaceUser->hasPermission(SpacePermission::VIEW_STATISTICS),
        ]);
    }
    
    /**
     * Get basic statistics for the dashboard.
     */
    private function getBasicStats(User $user): array
    {
        // Tasks assigned to the user
        $userTasksCount = Task::where('user_id', $user->id)->count();
        $userPendingTasksCount = Task::where('user_id', $user->id)->where('status', 'pending')->count();
        $userInProgressTasksCount = Task::where('user_id', $user->id)->where('status', 'in_progress')->count();
        $userCompletedTasksCount = Task::where('user_id', $user->id)->where('status', 'completed')->count();
        $userOverdueTasksCount = Task::where('user_id', $user->id)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();
            
        // User's projects
        $userProjectsCount = Project::where('user_id', $user->id)->count();
        $userActiveProjectsCount = Project::where('user_id', $user->id)->where('status', 'active')->count();
        $userCompletedProjectsCount = Project::where('user_id', $user->id)->where('status', 'completed')->count();
        
        // Recent tasks for user
        $recentTasks = Task::with(['project'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                    'priority' => $task->priority,
                    'project' => [
                        'id' => $task->project->id,
                        'name' => $task->project->name,
                    ],
                ];
            });
        
        return [
            'userTasksCount' => $userTasksCount,
            'userPendingTasksCount' => $userPendingTasksCount,
            'userInProgressTasksCount' => $userInProgressTasksCount,
            'userCompletedTasksCount' => $userCompletedTasksCount,
            'userOverdueTasksCount' => $userOverdueTasksCount,
            'userProjectsCount' => $userProjectsCount,
            'userActiveProjectsCount' => $userActiveProjectsCount,
            'userCompletedProjectsCount' => $userCompletedProjectsCount,
            'recentTasks' => $recentTasks,
        ];
    }
    
    /**
     * Get extended statistics for the dashboard.
     */
    private function getExtendedStats(): array
    {
        // All tasks in the tenant
        $allTasksCount = Task::count();
        $allPendingTasksCount = Task::where('status', 'pending')->count();
        $allInProgressTasksCount = Task::where('status', 'in_progress')->count();
        $allCompletedTasksCount = Task::where('status', 'completed')->count();
        
        // All projects in the tenant
        $allProjectsCount = Project::count();
        $allActiveProjectsCount = Project::where('status', 'active')->count();
        $allCompletedProjectsCount = Project::where('status', 'completed')->count();
        
        // Task completion trend (last 30 days)
        $taskCompletionByDay = Task::where('completed_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return $item->count;
            });
            
        // Fill in missing days
        $dateRange = collect(range(0, 29))->map(function ($day) {
            return now()->subDays($day)->format('Y-m-d');
        })->reverse();
        
        $completionTrend = $dateRange->mapWithKeys(function ($date) use ($taskCompletionByDay) {
            return [$date => $taskCompletionByDay[$date] ?? 0];
        });
        
        // Tasks created trend (last 30 days)
        $taskCreationByDay = Task::where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return $item->count;
            });
            
        $creationTrend = $dateRange->mapWithKeys(function ($date) use ($taskCreationByDay) {
            return [$date => $taskCreationByDay[$date] ?? 0];
        });
        
        // Top projects by tasks
        $topProjects = Project::withCount('tasks')
            ->orderBy('tasks_count', 'desc')
            ->take(5)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'tasks_count' => $project->tasks_count,
                ];
            });
        
        return [
            'allTasksCount' => $allTasksCount,
            'allPendingTasksCount' => $allPendingTasksCount,
            'allInProgressTasksCount' => $allInProgressTasksCount,
            'allCompletedTasksCount' => $allCompletedTasksCount,
            'allProjectsCount' => $allProjectsCount,
            'allActiveProjectsCount' => $allActiveProjectsCount,
            'allCompletedProjectsCount' => $allCompletedProjectsCount,
            'taskCompletionTrend' => $completionTrend,
            'taskCreationTrend' => $creationTrend,
            'topProjects' => $topProjects,
        ];
    }
    
    /**
     * Get project statistics for the dashboard.
     */
    private function getProjectStats(): array
    {
        // Project status distribution
        $projectStatuses = Project::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
            
        // Projects by creation date (last 30 days)
        $projectsByDate = Project::where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return $item->count;
            });
            
        // Fill in missing days
        $dateRange = collect(range(0, 29))->map(function ($day) {
            return now()->subDays($day)->format('Y-m-d');
        })->reverse();
        
        $projectTrend = $dateRange->mapWithKeys(function ($date) use ($projectsByDate) {
            return [$date => $projectsByDate[$date] ?? 0];
        });
        
        return [
            'statusDistribution' => $projectStatuses,
            'creationTrend' => $projectTrend,
        ];
    }
    
    /**
     * Get task statistics for the dashboard.
     */
    private function getTaskStats(User $user): array
    {
        // Task priority distribution
        $taskPriorities = Task::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->pluck('count', 'priority')
            ->toArray();
            
        // User's tasks by status
        $userTasksByStatus = Task::where('user_id', $user->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
            
        // Overdue tasks
        $overdueTasks = Task::with(['project', 'user'])
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                    'project' => [
                        'id' => $task->project->id,
                        'name' => $task->project->name,
                    ],
                    'user' => [
                        'id' => $task->user->id,
                        'name' => $task->user->name,
                    ],
                ];
            });
            
        // Tasks due soon (next 7 days)
        $dueSoonTasks = Task::with(['project', 'user'])
            ->where('status', '!=', 'completed')
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                    'project' => [
                        'id' => $task->project->id,
                        'name' => $task->project->name,
                    ],
                    'user' => [
                        'id' => $task->user->id,
                        'name' => $task->user->name,
                    ],
                ];
            });
            
        return [
            'priorityDistribution' => $taskPriorities,
            'userTasksByStatus' => $userTasksByStatus,
            'overdueTasks' => $overdueTasks,
            'dueSoonTasks' => $dueSoonTasks,
        ];
    }
    
    /**
     * Get user activity for the dashboard.
     */
    private function getUserActivity(): array
    {
        // Top users by tasks completed
        $topUsersByCompletedTasks = Task::where('status', 'completed')
            ->select('user_id', DB::raw('count(*) as count'))
            ->with('user:id,name')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get()
            ->map(function ($task) {
                return [
                    'user_id' => $task->user_id,
                    'user_name' => $task->user->name,
                    'count' => $task->count,
                ];
            });
            
        // Top users by open tasks
        $topUsersByOpenTasks = Task::whereIn('status', ['pending', 'in_progress'])
            ->select('user_id', DB::raw('count(*) as count'))
            ->with('user:id,name')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get()
            ->map(function ($task) {
                return [
                    'user_id' => $task->user_id,
                    'user_name' => $task->user->name,
                    'count' => $task->count,
                ];
            });
            
        // Recent user activity (task/project created, task status changed)
        // This would typically come from an activity log table, but we'll simulate it
        $recentActivity = collect();
        
        // Recently created tasks
        $recentTasks = Task::with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($task) {
                return [
                    'type' => 'task_created',
                    'user_id' => $task->user_id,
                    'user_name' => $task->user->name,
                    'entity_id' => $task->id,
                    'entity_name' => $task->title,
                    'date' => $task->created_at->format('Y-m-d H:i:s'),
                ];
            });
            
        // Recently completed tasks
        $recentCompletedTasks = Task::with(['user:id,name'])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($task) {
                return [
                    'type' => 'task_completed',
                    'user_id' => $task->user_id,
                    'user_name' => $task->user->name,
                    'entity_id' => $task->id,
                    'entity_name' => $task->title,
                    'date' => $task->completed_at->format('Y-m-d H:i:s'),
                ];
            });
            
        // Recently created projects
        $recentProjects = Project::with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($project) {
                return [
                    'type' => 'project_created',
                    'user_id' => $project->user_id,
                    'user_name' => $project->user->name,
                    'entity_id' => $project->id,
                    'entity_name' => $project->name,
                    'date' => $project->created_at->format('Y-m-d H:i:s'),
                ];
            });
            
        // Combine and sort by date
        $recentActivity = $recentTasks->concat($recentCompletedTasks)->concat($recentProjects)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->all();
            
        return [
            'topUsersByCompletedTasks' => $topUsersByCompletedTasks,
            'topUsersByOpenTasks' => $topUsersByOpenTasks,
            'recentActivity' => $recentActivity,
        ];
    }
}