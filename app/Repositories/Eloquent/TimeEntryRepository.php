<?php

namespace App\Repositories\Eloquent;

use App\Models\TimeEntry;
use App\Repositories\Interfaces\TimeEntryRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TimeEntryRepository implements TimeEntryRepositoryInterface
{
    /**
     * Get all time entries.
     */
    public function all(): Collection
    {
        return TimeEntry::with(['task', 'project', 'category', 'user'])
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Get paginated time entries.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return TimeEntry::with(['task', 'project', 'category', 'user'])
            ->orderBy('started_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find time entry by ID.
     */
    public function find(int $id): ?TimeEntry
    {
        return TimeEntry::with(['task', 'project', 'category', 'user'])
            ->find($id);
    }

    /**
     * Create a new time entry.
     */
    public function create(array $data): TimeEntry
    {
        return TimeEntry::create($data);
    }

    /**
     * Update a time entry.
     */
    public function update(int $id, array $data): ?TimeEntry
    {
        $timeEntry = $this->find($id);
        if ($timeEntry) {
            $timeEntry->update($data);

            return $timeEntry->fresh();
        }

        return null;
    }

    /**
     * Delete a time entry.
     */
    public function delete(int $id): bool
    {
        $timeEntry = $this->find($id);
        if ($timeEntry) {
            return $timeEntry->delete();
        }

        return false;
    }

    /**
     * Get currently running time entry for a user.
     */
    public function getRunningForUser(int $userId): ?TimeEntry
    {
        return TimeEntry::with(['task', 'project', 'category'])
            ->where('user_id', $userId)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->first();
    }

    /**
     * Get time entries for a specific task.
     */
    public function getForTask(int $taskId): Collection
    {
        return TimeEntry::with(['user', 'project', 'category'])
            ->where('task_id', $taskId)
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Get time entries for a specific project.
     */
    public function getForProject(int $projectId): Collection
    {
        return TimeEntry::with(['user', 'task', 'category'])
            ->where('project_id', $projectId)
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Get time entries for a specific user in a date range.
     */
    public function getForUserInDateRange(int $userId, Carbon $startDate, Carbon $endDate): Collection
    {
        return TimeEntry::with(['task', 'project', 'category'])
            ->where('user_id', $userId)
            ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Get total duration of time entries in a collection.
     */
    public function getTotalDuration(Collection $timeEntries): int
    {
        return $timeEntries->sum('duration');
    }

    /**
     * Get billable duration of time entries in a collection.
     */
    public function getBillableDuration(Collection $timeEntries): int
    {
        return $timeEntries->where('is_billable', true)->sum('duration');
    }

    /**
     * Get time entries grouped by date.
     */
    public function groupByDate(Collection $timeEntries): array
    {
        return $timeEntries->groupBy(function ($entry) {
            return Carbon::parse($entry->started_at)->format('Y-m-d');
        })->toArray();
    }

    /**
     * Get time entries grouped by project.
     */
    public function groupByProject(Collection $timeEntries): array
    {
        return $timeEntries->groupBy('project_id')->toArray();
    }

    /**
     * Get time entries grouped by category.
     */
    public function groupByCategory(Collection $timeEntries): array
    {
        return $timeEntries->groupBy('category_id')->toArray();
    }

    /**
     * Get unbilled time entries for a project.
     */
    public function getUnbilledForProject(int $projectId): Collection
    {
        return TimeEntry::with(['task', 'project', 'user'])
            ->where('project_id', $projectId)
            ->where('is_billable', true)
            ->whereNull('invoice_item_id') // Assuming a relationship to invoices will be added later
            ->orderBy('started_at', 'desc')
            ->get();
    }
}
