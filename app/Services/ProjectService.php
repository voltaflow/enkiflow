<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProjectService
{
    /**
     * @var ProjectRepositoryInterface
     */
    protected ProjectRepositoryInterface $projectRepository;

    /**
     * ProjectService constructor.
     *
     * @param ProjectRepositoryInterface $projectRepository
     */
    public function __construct(ProjectRepositoryInterface $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    /**
     * Get all projects.
     *
     * @return Collection
     */
    public function getAllProjects(): Collection
    {
        return $this->projectRepository->all();
    }

    /**
     * Get paginated projects.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedProjects(int $perPage = 15): LengthAwarePaginator
    {
        return $this->projectRepository->paginate($perPage);
    }

    /**
     * Get a project by ID.
     *
     * @param int $id
     * @return Project|null
     */
    public function getProjectById(int $id): ?Project
    {
        return $this->projectRepository->find($id);
    }

    /**
     * Create a new project.
     *
     * @param array $data
     * @return Project
     */
    public function createProject(array $data): Project
    {
        return $this->projectRepository->create($data);
    }

    /**
     * Update a project.
     *
     * @param int $id
     * @param array $data
     * @return Project
     */
    public function updateProject(int $id, array $data): Project
    {
        return $this->projectRepository->update($id, $data);
    }

    /**
     * Delete a project.
     *
     * @param int $id
     * @return bool
     */
    public function deleteProject(int $id): bool
    {
        return $this->projectRepository->delete($id);
    }

    /**
     * Get all active projects.
     *
     * @return Collection
     */
    public function getActiveProjects(): Collection
    {
        return $this->projectRepository->active();
    }

    /**
     * Get all completed projects.
     *
     * @return Collection
     */
    public function getCompletedProjects(): Collection
    {
        return $this->projectRepository->completed();
    }

    /**
     * Get all projects for a specific user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getProjectsForUser(int $userId): Collection
    {
        return $this->projectRepository->forUser($userId);
    }

    /**
     * Mark a project as completed.
     *
     * @param int $id
     * @return Project
     */
    public function markProjectAsCompleted(int $id): Project
    {
        $project = $this->projectRepository->find($id);
        $project->markAsCompleted();
        return $project;
    }

    /**
     * Mark a project as active.
     *
     * @param int $id
     * @return Project
     */
    public function markProjectAsActive(int $id): Project
    {
        $project = $this->projectRepository->find($id);
        $project->markAsActive();
        return $project;
    }

    /**
     * Sync tags for a project.
     *
     * @param int $projectId
     * @param array $tagIds
     * @return Project
     */
    public function syncTags(int $projectId, array $tagIds): Project
    {
        $project = $this->projectRepository->find($projectId);
        $project->tags()->sync($tagIds);
        return $project;
    }
}