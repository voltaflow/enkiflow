<?php

namespace App\Repositories\Interfaces;

use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProjectRepositoryInterface
{
    /**
     * Get all projects.
     */
    public function all(): Collection;

    /**
     * Get paginated projects.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a project by ID.
     */
    public function find(int $id): ?Project;

    /**
     * Create a new project.
     */
    public function create(array $data): Project;

    /**
     * Update a project.
     */
    public function update(int $id, array $data): Project;

    /**
     * Delete a project.
     */
    public function delete(int $id): bool;

    /**
     * Get all active projects.
     */
    public function active(): Collection;

    /**
     * Get all completed projects.
     */
    public function completed(): Collection;

    /**
     * Get all projects for a specific user.
     */
    public function forUser(int $userId): Collection;
}
