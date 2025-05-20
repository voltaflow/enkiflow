<?php

namespace App\Repositories\Interfaces;

use App\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TaskRepositoryInterface
{
    /**
     * Get all tasks.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Get paginated tasks.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a task by ID.
     *
     * @param int $id
     * @return Task|null
     */
    public function find(int $id): ?Task;

    /**
     * Create a new task.
     *
     * @param array $data
     * @return Task
     */
    public function create(array $data): Task;

    /**
     * Update a task.
     *
     * @param int $id
     * @param array $data
     * @return Task
     */
    public function update(int $id, array $data): Task;

    /**
     * Delete a task.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get all pending tasks.
     *
     * @return Collection
     */
    public function pending(): Collection;

    /**
     * Get all in-progress tasks.
     *
     * @return Collection
     */
    public function inProgress(): Collection;

    /**
     * Get all completed tasks.
     *
     * @return Collection
     */
    public function completed(): Collection;

    /**
     * Get all tasks for a specific project.
     *
     * @param int $projectId
     * @return Collection
     */
    public function forProject(int $projectId): Collection;

    /**
     * Get all tasks assigned to a specific user.
     *
     * @param int $userId
     * @return Collection
     */
    public function forUser(int $userId): Collection;
}