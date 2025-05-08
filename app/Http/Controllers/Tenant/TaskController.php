<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTaskRequest;
use App\Http\Requests\Tenant\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\HasSpacePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    use HasSpacePermissions;
    /**
     * @var TaskService
     */
    protected TaskService $taskService;

    /**
     * TaskController constructor.
     *
     * @param TaskService $taskService
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
        $tasks = Task::with(['project', 'user'])
            ->when($request->input('status'), function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->input('project_id'), function ($query, $projectId) {
                return $query->where('project_id', $projectId);
            })
            ->when($request->input('user_id'), function ($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'))
            ->paginate(10)
            ->withQueryString();
        
        // Get filters for dropdowns
        $projects = \App\Models\Project::select('id', 'name')->get();
        
        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'filters' => $request->only(['status', 'project_id', 'user_id', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new task.
     */
    public function create()
    {
        // Get projects for dropdown
        $projects = \App\Models\Project::select('id', 'name')->get();
        
        // Get users for dropdown (members of the current space)
        $users = \App\Models\User::select('id', 'name')->get();
        
        return Inertia::render('Tasks/Create', [
            'projects' => $projects,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $validated = $request->validated();
        
        $task = $this->taskService->createTask($validated);
        
        if ($request->has('tags')) {
            $this->taskService->syncTags($task->id, $request->tags);
        }
        
        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task created successfully.');
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task)
    {
        $task->load(['project', 'user', 'comments.user', 'tags']);
        
        return Inertia::render('Tasks/Show', [
            'task' => $task,
        ]);
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task)
    {
        $task->load(['tags', 'project', 'user']);
        
        // Get projects for dropdown
        $projects = \App\Models\Project::select('id', 'name')->get();
        
        // Get users for dropdown (members of the current space)
        $users = \App\Models\User::select('id', 'name')->get();
        
        // Get available tags
        $availableTags = \App\Models\Tag::select('id', 'name')->get();
        
        return Inertia::render('Tasks/Edit', [
            'task' => $task,
            'projects' => $projects,
            'users' => $users,
            'availableTags' => $availableTags,
        ]);
    }

    /**
     * Update the specified task in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $validated = $request->validated();
        
        $this->taskService->updateTask($task->id, $validated);
        
        if ($request->has('tags')) {
            $this->taskService->syncTags($task->id, $request->tags);
        }
        
        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task)
    {
        $this->taskService->deleteTask($task->id);
        
        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    /**
     * Mark the task as completed.
     */
    public function complete(Task $task)
    {
        $this->authorize('complete', $task);
        
        $this->taskService->markTaskAsCompleted($task->id);
        
        return redirect()->back()->with('success', 'Tarea marcada como completada.');
    }

    /**
     * Mark the task as in progress.
     */
    public function inProgress(Task $task)
    {
        $this->authorize('markAsInProgress', $task);
        
        $this->taskService->markTaskAsInProgress($task->id);
        
        return redirect()->back()->with('success', 'Tarea marcada como en progreso.');
    }

    /**
     * Add a comment to a task.
     */
    public function addComment(Request $request, Task $task)
    {
        $this->authorize('addComment', $task);
        
        $validated = $request->validate([
            'content' => 'required|string',
        ]);
        
        $this->taskService->addComment(
            $task->id,
            $validated['content'],
            $request->user()->id
        );
        
        return redirect()->back()->with('success', 'Comentario añadido correctamente.');
    }
}
