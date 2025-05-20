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
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return Task::all();
    }

    /**
     * Get paginated tasks.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Task::paginate($perPage);
    }

    /**
     * Get a task by ID.
     *
     * @param int $id
     * @return Task|null
     */
    public function find(int $id): ?Task
    {
        return Task::find($id);
    }

    /**
     * Create a new task.
     *
     * @param array $data
     * @return Task
     */
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    /**
     * Update a task.
     *
     * @param int $id
     * @param array $data
     * @return Task
     */
    public function update(int $id, array $data): Task
    {
        $task = $this->find($id);
        $task->update($data);
        return $task;
    }

    /**
     * Delete a task.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return (bool) Task::destroy($id);
    }

    /**
     * Get all pending tasks.
     *
     * @return Collection
     */
    public function pending(): Collection
    {
        return Task::pending()->get();
    }

    /**
     * Get all in-progress tasks.
     *
     * @return Collection
     */
    public function inProgress(): Collection
    {
        return Task::inProgress()->get();
    }

    /**
     * Get all completed tasks.
     *
     * @return Collection
     */
    public function completed(): Collection
    {
        return Task::completed()->get();
    }

    /**
     * Get all tasks for a specific project.
     *
     * @param int $projectId
     * @return Collection
     */
    public function forProject(int $projectId): Collection
    {
        return Task::where('project_id', $projectId)->get();
    }

    /**
     * Get all tasks assigned to a specific user.
     *
     * @param int $userId
     * @return Collection
     */
    public function forUser(int $userId): Collection
    {
        return Task::where('user_id', $userId)->get();
    }
}