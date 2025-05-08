<?php

namespace App\Repositories\Interfaces;

use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProjectRepositoryInterface
{
    /**
     * Get all projects.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Get paginated projects.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a project by ID.
     *
     * @param int $id
     * @return Project|null
     */
    public function find(int $id): ?Project;

    /**
     * Create a new project.
     *
     * @param array $data
     * @return Project
     */
    public function create(array $data): Project;

    /**
     * Update a project.
     *
     * @param int $id
     * @param array $data
     * @return Project
     */
    public function update(int $id, array $data): Project;

    /**
     * Delete a project.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get all active projects.
     *
     * @return Collection
     */
    public function active(): Collection;

    /**
     * Get all completed projects.
     *
     * @return Collection
     */
    public function completed(): Collection;

    /**
     * Get all projects for a specific user.
     *
     * @param int $userId
     * @return Collection
     */
    public function forUser(int $userId): Collection;
}