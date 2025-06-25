<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTaskRequest;
use App\Http\Requests\Tenant\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use App\Traits\HasSpacePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    use HasSpacePermissions;

    protected TaskService $taskService;

    /**
     * TaskController constructor.
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
        $this->authorizeResource(Task::class, 'task');
    }

    /**
     * Display a listing of the tasks.
     */
    public function index(Request $request)
    {
        // Get tasks with eager loading of relationships
        // Note: 'assignees' removed temporarily due to cross-database join issue
        $query = Task::with(['project', 'user', 'tags', 'parentTask'])
            ->withCount('subtasks')
            ->rootTasks(); // Only get root tasks, not subtasks

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Temporarily disabled due to cross-database join issue
        // if ($request->filled('assignee_id')) {
        //     $query->whereHas('assignees', function ($q) use ($request) {
        //         $q->where('user_id', $request->assignee_id);
        //     });
        // }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->due_date_from);
        }

        if ($request->filled('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->due_date_to);
        }

        // Handle sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        if ($sortField === 'project') {
            $query->join('projects', 'tasks.project_id', '=', 'projects.id')
                ->orderBy('projects.name', $sortDirection)
                ->select('tasks.*');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $tasks = $query->paginate(20)->withQueryString();

        // Get data for filters
        $projects = Project::select('id', 'name')->orderBy('name')->get();
        
        // Get users that belong to the current tenant
        $currentTenant = tenant();
        $users = $currentTenant ? $currentTenant->users()->select('users.id', 'users.name')->orderBy('name')->get() : collect();
        
        $tags = Tag::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'users' => $users,
            'tags' => $tags,
            'filters' => $request->only([
                'status', 'project_id', 'assignee_id', 'search',
                'priority', 'due_date_from', 'due_date_to', 'sort', 'direction',
            ]),
        ]);
    }

    /**
     * Display tasks in Kanban board view.
     */
    public function kanban(Request $request)
    {
        $query = Task::with(['project', 'user', 'tags'])
            ->withCount(['subtasks', 'comments', 'timeEntries'])
            ->selectRaw('tasks.*, (SELECT SUM(duration) / 3600 FROM time_entries WHERE time_entries.task_id = tasks.id) as total_logged_hours')
            ->rootTasks();

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('priority')) {
            $priorityMap = ['high' => [4, 5], 'medium' => [2, 3], 'low' => [0, 1]];
            if (isset($priorityMap[$request->priority])) {
                $range = $priorityMap[$request->priority];
                $query->whereBetween('priority', $range);
            }
        }

        // Get all tasks and ensure board_column is set
        $tasks = $query->get()->map(function ($task) {
            if (! $task->board_column) {
                $task->board_column = $task->status === 'completed' ? 'done' :
                                     ($task->status === 'in_progress' ? 'in_progress' : 'todo');
            }

            return $task;
        });

        // Get data for filters
        $projects = Project::select('id', 'name')->orderBy('name')->get();
        
        // Get users that belong to the current tenant
        $currentTenant = tenant();
        $users = $currentTenant ? $currentTenant->users()->select('users.id', 'users.name')->orderBy('name')->get() : collect();
        
        $tags = Tag::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Tasks/Kanban', [
            'tasks' => $tasks,
            'projects' => $projects,
            'users' => $users,
            'tags' => $tags,
            'filters' => $request->only(['search', 'project_id', 'user_id', 'priority']),
        ]);
    }

    /**
     * Show the form for creating a new task.
     */
    public function create()
    {
        $projects = Project::select('id', 'name')->orderBy('name')->get();
        $users = User::select('id', 'name')->orderBy('name')->get();
        $tags = Tag::select('id', 'name')->orderBy('name')->get();

        // Get parent tasks for subtask creation
        $parentTasks = Task::select('id', 'title', 'project_id')
            ->rootTasks()
            ->orderBy('title')
            ->get();

        return Inertia::render('Tasks/Create', [
            'projects' => $projects,
            'users' => $users,
            'tags' => $tags,
            'parentTasks' => $parentTasks,
            'defaultProjectId' => request('project_id'),
            'defaultParentTaskId' => request('parent_task_id'),
        ]);
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = Auth::id();

        // Set default position for new tasks
        if (! isset($validated['position'])) {
            $maxPosition = Task::where('project_id', $validated['project_id'])
                ->where('board_column', $validated['board_column'] ?? 'todo')
                ->max('position');
            $validated['position'] = $maxPosition + 1;
        }

        $task = $this->taskService->createTask($validated);

        // Handle assignees
        if ($request->has('assignee_ids')) {
            $task->assignees()->sync($request->assignee_ids);
        }

        // Handle tags
        if ($request->has('tag_ids')) {
            $task->tags()->sync($request->tag_ids);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'task' => $task->load(['project', 'assignees', 'tags']),
                'message' => 'Task created successfully',
            ]);
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task created successfully.');
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task)
    {
        $task->load([
            'project',
            'user',
            'creator',
            'assignees',
            'comments.user',
            'tags',
            'parentTask',
            'subtasks.assignees',
            'timeEntries.user',
        ]);

        // Calculate some stats
        $stats = [
            'total_hours' => $task->total_logged_hours,
            'completion_percentage' => $task->completion_percentage,
            'subtasks_count' => $task->subtasks->count(),
            'subtasks_completed' => $task->subtasks->where('status', 'completed')->count(),
            'comments_count' => $task->comments->count(),
        ];

        return Inertia::render('Tasks/Show', [
            'task' => $task,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task)
    {
        $task->load(['tags', 'project', 'assignees', 'parentTask']);

        $projects = Project::select('id', 'name')->orderBy('name')->get();
        $users = User::select('id', 'name')->orderBy('name')->get();
        $tags = Tag::select('id', 'name')->orderBy('name')->get();

        // Get parent tasks (excluding current task and its subtasks)
        $excludeIds = collect([$task->id])->merge($task->subtasks->pluck('id'));
        $parentTasks = Task::select('id', 'title', 'project_id')
            ->whereNotIn('id', $excludeIds)
            ->rootTasks()
            ->orderBy('title')
            ->get();

        return Inertia::render('Tasks/Edit', [
            'task' => $task,
            'projects' => $projects,
            'users' => $users,
            'tags' => $tags,
            'parentTasks' => $parentTasks,
        ]);
    }

    /**
     * Update the specified task in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $validated = $request->validated();

        $this->taskService->updateTask($task->id, $validated);

        // Handle assignees
        if ($request->has('assignee_ids')) {
            $task->assignees()->sync($request->assignee_ids);
        }

        // Handle tags
        if ($request->has('tag_ids')) {
            $task->tags()->sync($request->tag_ids);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'task' => $task->fresh()->load(['project', 'assignees', 'tags']),
                'message' => 'Task updated successfully',
            ]);
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task)
    {
        // Check if task has subtasks
        if ($task->hasSubtasks()) {
            return back()->with('error', 'Cannot delete task with subtasks. Please delete subtasks first.');
        }

        $this->taskService->deleteTask($task->id);

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Task deleted successfully']);
        }

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    /**
     * Mark the task as completed.
     */
    public function complete(Task $task)
    {
        $this->authorize('update', $task);

        $task->markAsCompleted();

        if (request()->wantsJson()) {
            return response()->json([
                'task' => $task->fresh(),
                'message' => 'Task marked as completed',
            ]);
        }

        return back()->with('success', 'Task marked as completed.');
    }

    /**
     * Mark the task as in progress.
     */
    public function inProgress(Task $task)
    {
        $this->authorize('update', $task);

        $task->markAsInProgress();

        if (request()->wantsJson()) {
            return response()->json([
                'task' => $task->fresh(),
                'message' => 'Task marked as in progress',
            ]);
        }

        return back()->with('success', 'Task marked as in progress.');
    }

    /**
     * Add a comment to a task.
     */
    public function addComment(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = $this->taskService->addComment(
            $task->id,
            $validated['content'],
            Auth::id()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'comment' => $comment->load('user'),
                'message' => 'Comment added successfully',
            ]);
        }

        return back()->with('success', 'Comment added successfully.');
    }

    /**
     * Move task to different column in Kanban board.
     */
    public function move(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'column' => 'required|in:todo,in_progress,done',
            'position' => 'required|integer|min:0',
            'tasks_order' => 'required|array',
            'tasks_order.*' => 'exists:tasks,id',
        ]);

        $task = Task::findOrFail($validated['task_id']);
        $this->authorize('update', $task);

        // Update the task's column and position
        $task->moveToColumn($validated['column'], $validated['position']);

        // Update positions for all tasks in the new order
        foreach ($validated['tasks_order'] as $index => $taskId) {
            Task::where('id', $taskId)->update(['position' => $index]);
        }

        return response()->json([
            'message' => 'Task moved successfully',
        ]);
    }

    /**
     * Bulk delete tasks.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        $tasks = Task::whereIn('id', $validated['task_ids'])->get();
        $deletedCount = 0;

        foreach ($tasks as $task) {
            $this->authorize('delete', $task);
            if (! $task->hasSubtasks()) {
                $task->delete();
                $deletedCount++;
            }
        }

        return back()->with('success', "$deletedCount tareas eliminadas exitosamente.");
    }

    /**
     * Bulk mark tasks as completed.
     */
    public function bulkComplete(Request $request)
    {
        $validated = $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        $tasks = Task::whereIn('id', $validated['task_ids'])->get();

        foreach ($tasks as $task) {
            $this->authorize('update', $task);
            $task->markAsCompleted();
        }

        return back()->with('success', 'Tareas marcadas como completadas.');
    }

    /**
     * Bulk mark tasks as in progress.
     */
    public function bulkInProgress(Request $request)
    {
        $validated = $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        $tasks = Task::whereIn('id', $validated['task_ids'])->get();

        foreach ($tasks as $task) {
            $this->authorize('update', $task);
            $task->markAsInProgress();
        }

        return back()->with('success', 'Tareas marcadas como en progreso.');
    }
}

