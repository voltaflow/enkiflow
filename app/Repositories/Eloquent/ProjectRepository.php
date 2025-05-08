<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProjectRepository implements ProjectRepositoryInterface
{
    /**
     * Get all projects.
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return Project::all();
    }

    /**
     * Get paginated projects.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Project::paginate($perPage);
    }

    /**
     * Get a project by ID.
     *
     * @param int $id
     * @return Project|null
     */
    public function find(int $id): ?Project
    {
        return Project::find($id);
    }

    /**
     * Create a new project.
     *
     * @param array $data
     * @return Project
     */
    public function create(array $data): Project
    {
        return Project::create($data);
    }

    /**
     * Update a project.
     *
     * @param int $id
     * @param array $data
     * @return Project
     */
    public function update(int $id, array $data): Project
    {
        $project = $this->find($id);
        $project->update($data);
        return $project;
    }

    /**
     * Delete a project.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return (bool) Project::destroy($id);
    }

    /**
     * Get all active projects.
     *
     * @return Collection
     */
    public function active(): Collection
    {
        return Project::active()->get();
    }

    /**
     * Get all completed projects.
     *
     * @return Collection
     */
    public function completed(): Collection
    {
        return Project::completed()->get();
    }

    /**
     * Get all projects for a specific user.
     *
     * @param int $userId
     * @return Collection
     */
    public function forUser(int $userId): Collection
    {
        return Project::where('user_id', $userId)->get();
    }
}