<?php

namespace App\Repositories\Interfaces;

use App\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TaskRepositoryInterface
{
    /**
     * Get all tasks.
     */
    public function all(): Collection;

    /**
     * Get paginated tasks.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a task by ID.
     */
    public function find(int $id): ?Task;

    /**
     * Create a new task.
     */
    public function create(array $data): Task;

    /**
     * Update a task.
     */
    public function update(int $id, array $data): Task;

    /**
     * Delete a task.
     */
    public function delete(int $id): bool;

    /**
     * Get all pending tasks.
     */
    public function pending(): Collection;

    /**
     * Get all in-progress tasks.
     */
    public function inProgress(): Collection;

    /**
     * Get all completed tasks.
     */
    public function completed(): Collection;

    /**
     * Get all tasks for a specific project.
     */
    public function forProject(int $projectId): Collection;

    /**
     * Get all tasks assigned to a specific user.
     */
    public function forUser(int $userId): Collection;
}
