<?php

namespace App\Repositories\Interfaces;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TimeEntryRepositoryInterface
{
    /**
     * Get all time entries.
     */
    public function all(): Collection;
    
    /**
     * Get paginated time entries.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Find time entry by ID.
     */
    public function find(int $id): ?TimeEntry;
    
    /**
     * Create a new time entry.
     */
    public function create(array $data): TimeEntry;
    
    /**
     * Update a time entry.
     */
    public function update(int $id, array $data): ?TimeEntry;
    
    /**
     * Delete a time entry.
     */
    public function delete(int $id): bool;
    
    /**
     * Get currently running time entry for a user.
     */
    public function getRunningForUser(int $userId): ?TimeEntry;
    
    /**
     * Get time entries for a specific task.
     */
    public function getForTask(int $taskId): Collection;
    
    /**
     * Get time entries for a specific project.
     */
    public function getForProject(int $projectId): Collection;
    
    /**
     * Get time entries for a specific user in a date range.
     */
    public function getForUserInDateRange(int $userId, Carbon $startDate, Carbon $endDate): Collection;
    
    /**
     * Get total duration of time entries in a collection.
     */
    public function getTotalDuration(Collection $timeEntries): int;
    
    /**
     * Get billable duration of time entries in a collection.
     */
    public function getBillableDuration(Collection $timeEntries): int;
    
    /**
     * Get time entries grouped by date.
     */
    public function groupByDate(Collection $timeEntries): array;
    
    /**
     * Get time entries grouped by project.
     */
    public function groupByProject(Collection $timeEntries): array;
    
    /**
     * Get time entries grouped by category.
     */
    public function groupByCategory(Collection $timeEntries): array;
    
    /**
     * Get unbilled time entries for a project.
     */
    public function getUnbilledForProject(int $projectId): Collection;
}
