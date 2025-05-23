<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProjectService
{
    protected ProjectRepositoryInterface $projectRepository;

    /**
     * ProjectService constructor.
     */
    public function __construct(ProjectRepositoryInterface $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    /**
     * Get all projects.
     */
    public function getAllProjects(): Collection
    {
        return $this->projectRepository->all();
    }

    /**
     * Get paginated projects.
     */
    public function getPaginatedProjects(int $perPage = 15): LengthAwarePaginator
    {
        return $this->projectRepository->paginate($perPage);
    }

    /**
     * Get a project by ID.
     */
    public function getProjectById(int $id): ?Project
    {
        return $this->projectRepository->find($id);
    }

    /**
     * Create a new project.
     */
    public function createProject(array $data): Project
    {
        return $this->projectRepository->create($data);
    }

    /**
     * Update a project.
     */
    public function updateProject(int $id, array $data): Project
    {
        return $this->projectRepository->update($id, $data);
    }

    /**
     * Delete a project.
     */
    public function deleteProject(int $id): bool
    {
        return $this->projectRepository->delete($id);
    }

    /**
     * Get all active projects.
     */
    public function getActiveProjects(): Collection
    {
        return $this->projectRepository->active();
    }

    /**
     * Get all completed projects.
     */
    public function getCompletedProjects(): Collection
    {
        return $this->projectRepository->completed();
    }

    /**
     * Get all projects for a specific user.
     */
    public function getProjectsForUser(int $userId): Collection
    {
        return $this->projectRepository->forUser($userId);
    }

    /**
     * Mark a project as completed.
     */
    public function markProjectAsCompleted(int $id): Project
    {
        $project = $this->projectRepository->find($id);
        $project->markAsCompleted();

        return $project;
    }

    /**
     * Mark a project as active.
     */
    public function markProjectAsActive(int $id): Project
    {
        $project = $this->projectRepository->find($id);
        $project->markAsActive();

        return $project;
    }

    /**
     * Sync tags for a project.
     */
    public function syncTags(int $projectId, array $tagIds): Project
    {
        $project = $this->projectRepository->find($projectId);
        $project->tags()->sync($tagIds);

        return $project;
    }
}
