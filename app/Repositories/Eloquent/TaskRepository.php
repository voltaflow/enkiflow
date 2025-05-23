<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Get all tasks.
     */
    public function all(): Collection
    {
        return Task::all();
    }

    /**
     * Get paginated tasks.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Task::paginate($perPage);
    }

    /**
     * Get a task by ID.
     */
    public function find(int $id): ?Task
    {
        return Task::find($id);
    }

    /**
     * Create a new task.
     */
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    /**
     * Update a task.
     */
    public function update(int $id, array $data): Task
    {
        $task = $this->find($id);
        $task->update($data);

        return $task;
    }

    /**
     * Delete a task.
     */
    public function delete(int $id): bool
    {
        return (bool) Task::destroy($id);
    }

    /**
     * Get all pending tasks.
     */
    public function pending(): Collection
    {
        return Task::pending()->get();
    }

    /**
     * Get all in-progress tasks.
     */
    public function inProgress(): Collection
    {
        return Task::inProgress()->get();
    }

    /**
     * Get all completed tasks.
     */
    public function completed(): Collection
    {
        return Task::completed()->get();
    }

    /**
     * Get all tasks for a specific project.
     */
    public function forProject(int $projectId): Collection
    {
        return Task::where('project_id', $projectId)->get();
    }

    /**
     * Get all tasks assigned to a specific user.
     */
    public function forUser(int $userId): Collection
    {
        return Task::where('user_id', $userId)->get();
    }
}
