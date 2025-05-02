<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTaskRequest;
use App\Http\Requests\Tenant\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
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
    }

    /**
     * Display a listing of the tasks.
     */
    public function index(Request $request)
    {
        $tasks = $this->taskService->getPaginatedTasks(10);
        
        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
        ]);
    }

    /**
     * Show the form for creating a new task.
     */
    public function create()
    {
        return Inertia::render('Tasks/Create');
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
        $task->load(['tags']);
        
        return Inertia::render('Tasks/Edit', [
            'task' => $task,
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
        $this->taskService->markTaskAsCompleted($task->id);
        
        return redirect()->back()->with('success', 'Task marked as completed.');
    }

    /**
     * Mark the task as in progress.
     */
    public function inProgress(Task $task)
    {
        $this->taskService->markTaskAsInProgress($task->id);
        
        return redirect()->back()->with('success', 'Task marked as in progress.');
    }

    /**
     * Add a comment to a task.
     */
    public function addComment(Request $request, Task $task)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);
        
        $this->taskService->addComment(
            $task->id,
            $validated['content'],
            $request->user()->id
        );
        
        return redirect()->back()->with('success', 'Comment added successfully.');
    }
}
