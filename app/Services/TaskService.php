<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TaskService
{
    /**
     * @var TaskRepositoryInterface
     */
    protected TaskRepositoryInterface $taskRepository;

    /**
     * TaskService constructor.
     *
     * @param TaskRepositoryInterface $taskRepository
     */
    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Get all tasks.
     *
     * @return Collection
     */
    public function getAllTasks(): Collection
    {
        return $this->taskRepository->all();
    }

    /**
     * Get paginated tasks.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedTasks(int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->paginate($perPage);
    }

    /**
     * Get a task by ID.
     *
     * @param int $id
     * @return Task|null
     */
    public function getTaskById(int $id): ?Task
    {
        return $this->taskRepository->find($id);
    }

    /**
     * Create a new task.
     *
     * @param array $data
     * @return Task
     */
    public function createTask(array $data): Task
    {
        return $this->taskRepository->create($data);
    }

    /**
     * Update a task.
     *
     * @param int $id
     * @param array $data
     * @return Task
     */
    public function updateTask(int $id, array $data): Task
    {
        return $this->taskRepository->update($id, $data);
    }

    /**
     * Delete a task.
     *
     * @param int $id
     * @return bool
     */
    public function deleteTask(int $id): bool
    {
        return $this->taskRepository->delete($id);
    }

    /**
     * Get all pending tasks.
     *
     * @return Collection
     */
    public function getPendingTasks(): Collection
    {
        return $this->taskRepository->pending();
    }

    /**
     * Get all in-progress tasks.
     *
     * @return Collection
     */
    public function getInProgressTasks(): Collection
    {
        return $this->taskRepository->inProgress();
    }

    /**
     * Get all completed tasks.
     *
     * @return Collection
     */
    public function getCompletedTasks(): Collection
    {
        return $this->taskRepository->completed();
    }

    /**
     * Get all tasks for a specific project.
     *
     * @param int $projectId
     * @return Collection
     */
    public function getTasksForProject(int $projectId): Collection
    {
        return $this->taskRepository->forProject($projectId);
    }

    /**
     * Get all tasks assigned to a specific user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getTasksForUser(int $userId): Collection
    {
        return $this->taskRepository->forUser($userId);
    }

    /**
     * Mark a task as completed.
     *
     * @param int $id
     * @return Task
     */
    public function markTaskAsCompleted(int $id): Task
    {
        $task = $this->taskRepository->find($id);
        $task->markAsCompleted();
        return $task;
    }

    /**
     * Mark a task as in progress.
     *
     * @param int $id
     * @return Task
     */
    public function markTaskAsInProgress(int $id): Task
    {
        $task = $this->taskRepository->find($id);
        $task->markAsInProgress();
        return $task;
    }

    /**
     * Mark a task as pending.
     *
     * @param int $id
     * @return Task
     */
    public function markTaskAsPending(int $id): Task
    {
        $task = $this->taskRepository->find($id);
        $task->markAsPending();
        return $task;
    }

    /**
     * Sync tags for a task.
     *
     * @param int $taskId
     * @param array $tagIds
     * @return Task
     */
    public function syncTags(int $taskId, array $tagIds): Task
    {
        $task = $this->taskRepository->find($taskId);
        $task->tags()->sync($tagIds);
        return $task;
    }

    /**
     * Add a comment to a task.
     *
     * @param int $taskId
     * @param string $content
     * @param int $userId
     * @return Task
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