<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TaskService
{
    protected TaskRepositoryInterface $taskRepository;

    /**
     * TaskService constructor.
     */
    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Get all tasks.
     */
    public function getAllTasks(): Collection
    {
        return $this->taskRepository->all();
    }

    /**
     * Get paginated tasks.
     */
    public function getPaginatedTasks(int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->paginate($perPage);
    }

    /**
     * Get a task by ID.
     */
    public function getTaskById(int $id): ?Task
    {
        return $this->taskRepository->find($id);
    }

    /**
     * Create a new task.
     */
    public function createTask(array $data): Task
    {
        return $this->taskRepository->create($data);
    }

    /**
     * Update a task.
     */
    public function updateTask(int $id, array $data): Task
    {
        return $this->taskRepository->update($id, $data);
    }

    /**
     * Delete a task.
     */
    public function deleteTask(int $id): bool
    {
        return $this->taskRepository->delete($id);
    }

    /**
     * Get all pending tasks.
     */
    public function getPendingTasks(): Collection
    {
        return $this->taskRepository->pending();
    }

    /**
     * Get all in-progress tasks.
     */
    public function getInProgressTasks(): Collection
    {
        return $this->taskRepository->inProgress();
    }

    /**
     * Get all completed tasks.
     */
    public function getCompletedTasks(): Collection
    {
        return $this->taskRepository->completed();
    }

    /**
     * Get all tasks for a specific project.
     */
    public function getTasksForProject(int $projectId): Collection
    {
        return $this->taskRepository->forProject($projectId);
    }

    /**
     * Get all tasks assigned to a specific user.
     */
    public function getTasksForUser(int $userId): Collection
    {
        return $this->taskRepository->forUser($userId);
    }

    /**
     * Mark a task as completed.
     */
    public function markTaskAsCompleted(int $id): Task
    {
        $task = $this->taskRepository->find($id);
        $task->markAsCompleted();

        return $task;
    }

    /**
     * Mark a task as in progress.
     */
    public function markTaskAsInProgress(int $id): Task
    {
        $task = $this->taskRepository->find($id);
        $task->markAsInProgress();

        return $task;
    }

    /**
     * Mark a task as pending.
     */
    public function markTaskAsPending(int $id): Task
    {
        $task = $this->taskRepository->find($id);
        $task->markAsPending();

        return $task;
    }

    /**
     * Sync tags for a task.
     */
    public function syncTags(int $taskId, array $tagIds): Task
    {
        $task = $this->taskRepository->find($taskId);
        $task->tags()->sync($tagIds);

        return $task;
    }

    /**
     * Add a comment to a task.
     */
    public function addComment(int $taskId, string $content, int $userId): Task
    {
        $task = $this->taskRepository->find($taskId);
        $task->comments()->create([
            'content' => $content,
            'user_id' => $userId,
        ]);

        return $task;
    }
}
