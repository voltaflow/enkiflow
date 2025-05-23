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
     */
    public function all(): Collection
    {
        return Project::all();
    }

    /**
     * Get paginated projects.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Project::paginate($perPage);
    }

    /**
     * Get a project by ID.
     */
    public function find(int $id): ?Project
    {
        return Project::find($id);
    }

    /**
     * Create a new project.
     */
    public function create(array $data): Project
    {
        return Project::create($data);
    }

    /**
     * Update a project.
     */
    public function update(int $id, array $data): Project
    {
        $project = $this->find($id);
        $project->update($data);

        return $project;
    }

    /**
     * Delete a project.
     */
    public function delete(int $id): bool
    {
        return (bool) Project::destroy($id);
    }

    /**
     * Get all active projects.
     */
    public function active(): Collection
    {
        return Project::active()->get();
    }

    /**
     * Get all completed projects.
     */
    public function completed(): Collection
    {
        return Project::completed()->get();
    }

    /**
     * Get all projects for a specific user.
     */
    public function forUser(int $userId): Collection
    {
        return Project::where('user_id', $userId)->get();
    }
}
