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

    /**
     * Search and filter projects.
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Project::query()
            ->with(['client:id,name', 'tags', 'user:id,name']);
        
        // Filter by user assignments if not owner/admin
        $user = auth()->user();
        $space = tenant();
        
        // Check if user is space owner
        $isOwner = $space && $user->id === $space->owner_id;
        
        if (!$isOwner) {
            // Get SpaceUser record
            $spaceUser = \App\Models\SpaceUser::where('tenant_id', tenant('id'))
                ->where('user_id', $user->id)
                ->first();
            
            // If user is not admin, check permissions
            if (!$spaceUser || !$spaceUser->isAdmin()) {
                // Check if user has VIEW_ALL_PROJECTS permission
                if (!$spaceUser || !$spaceUser->hasPermission(\App\Enums\SpacePermission::VIEW_ALL_PROJECTS)) {
                    // Only filter by assigned projects if user doesn't have VIEW_ALL_PROJECTS permission
                    $query->whereExists(function ($q) use ($user) {
                        $q->select(\DB::raw(1))
                            ->from('project_user')
                            ->whereColumn('projects.id', 'project_user.project_id')
                            ->where('project_user.user_id', $user->id);
                    });
                }
            }
        }

        // Search by term
        if (!empty($filters['term'])) {
            $term = $filters['term'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('description', 'LIKE', "%{$term}%");
            });
        }

        // Filter by status
        if (isset($filters['status']) && $filters['status'] !== '') {
            if ($filters['status'] === 'active') {
                $query->where('status', 'active');
            } elseif ($filters['status'] === 'completed') {
                $query->where('status', 'completed');
            }
        }

        // Filter by client
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Include trashed if requested
        if (!empty($filters['include_archived'])) {
            $query->withTrashed();
        }

        // Add task counts
        $query->withCount(['tasks', 'tasks as completed_tasks_count' => function ($query) {
            $query->where('status', 'completed');
        }]);

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }
}
