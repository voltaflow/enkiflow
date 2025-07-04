<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Repositories\Interfaces\TimeEntryRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TimeEntryService
{
    /**
     * @var TimeEntryRepositoryInterface
     */
    protected $timeEntryRepository;

    /**
     * TimeEntryService constructor.
     */
    public function __construct(TimeEntryRepositoryInterface $timeEntryRepository)
    {
        $this->timeEntryRepository = $timeEntryRepository;
    }

    /**
     * Get all time entries.
     */
    public function getAllTimeEntries(): Collection
    {
        return $this->timeEntryRepository->all();
    }

    /**
     * Get paginated time entries.
     */
    public function getPaginatedTimeEntries(int $perPage = 15): LengthAwarePaginator
    {
        return $this->timeEntryRepository->paginate($perPage);
    }

    /**
     * Get time entry by ID.
     */
    public function getTimeEntryById(int $id): ?TimeEntry
    {
        return $this->timeEntryRepository->find($id);
    }

    /**
     * Create a time entry.
     */
    public function createTimeEntry(array $data): TimeEntry
    {
        // If manually creating an entry with start and end times, calculate duration
        if (! empty($data['started_at']) && ! empty($data['ended_at'])) {
            $startTime = Carbon::parse($data['started_at']);
            $endTime = Carbon::parse($data['ended_at']);
            
            // Log the parsed times for debugging
            \Log::info('Creating time entry with times:', [
                'started_at_raw' => $data['started_at'],
                'ended_at_raw' => $data['ended_at'],
                'started_at_parsed' => $startTime->format('Y-m-d H:i:s'),
                'ended_at_parsed' => $endTime->format('Y-m-d H:i:s'),
                'start_timestamp' => $startTime->timestamp,
                'end_timestamp' => $endTime->timestamp,
            ]);
            
            // Calculate duration - ensure it's always positive and an integer
            $data['duration'] = (int) $endTime->diffInSeconds($startTime);
            
            // Log the calculated duration
            \Log::info('Calculated duration:', [
                'duration_seconds' => $data['duration'],
                'duration_hours' => $data['duration'] / 3600,
            ]);
            
            // Ensure duration is not negative
            if ($data['duration'] < 0) {
                \Log::error('Negative duration detected!', [
                    'duration' => $data['duration'],
                    'started_at' => $startTime->format('Y-m-d H:i:s'),
                    'ended_at' => $endTime->format('Y-m-d H:i:s'),
                    'timezone' => $startTime->timezone->getName(),
                ]);
                
                // Check if this is a case where end time wrapped to next day
                if ($endTime->hour < $startTime->hour) {
                    // Add one day to end time
                    $endTime->addDay();
                    $data['duration'] = (int) $endTime->diffInSeconds($startTime);
                    $data['ended_at'] = $endTime->format('Y-m-d H:i:s');
                    
                    \Log::info('Fixed negative duration by assuming end time is next day', [
                        'new_duration' => $data['duration'],
                        'new_ended_at' => $data['ended_at'],
                    ]);
                } else {
                    // Just make it positive as a fallback
                    $data['duration'] = (int) abs($data['duration']);
                    \Log::warning('Made negative duration positive as fallback', [
                        'new_duration' => $data['duration'],
                    ]);
                }
            }
        }

        return $this->timeEntryRepository->create($data);
    }

    /**
     * Update a time entry.
     */
    public function updateTimeEntry(int $id, array $data): ?TimeEntry
    {
        // If updating start or end time, recalculate duration
        $timeEntry = $this->timeEntryRepository->find($id);

        if ($timeEntry) {
            $startTime = isset($data['started_at']) ? Carbon::parse($data['started_at']) : $timeEntry->started_at;
            $endTime = isset($data['ended_at']) ? Carbon::parse($data['ended_at']) : $timeEntry->ended_at;

            if ($startTime && $endTime) {
                // Calculate duration as the difference from start to end
                $data['duration'] = $startTime->diffInSeconds($endTime);
            }
        }

        return $this->timeEntryRepository->update($id, $data);
    }

    /**
     * Delete a time entry.
     */
    public function deleteTimeEntry(int $id): bool
    {
        return $this->timeEntryRepository->delete($id);
    }

    /**
     * Start a new time entry.
     */
    public function startTimeEntry(int $userId, ?int $taskId = null, ?int $projectId = null, ?string $description = null): TimeEntry
    {
        // Check if there's already a running entry for this user
        $runningEntry = $this->timeEntryRepository->getRunningForUser($userId);

        // If there's a running entry, stop it first
        if ($runningEntry) {
            $this->stopTimeEntry($runningEntry->id);
        }

        // Create a new time entry
        $data = [
            'user_id' => $userId,
            'task_id' => $taskId,
            'project_id' => $projectId,
            'description' => $description,
            'started_at' => now(),
            'is_manual' => false,
        ];

        return $this->timeEntryRepository->create($data);
    }

    /**
     * Stop a time entry.
     */
    public function stopTimeEntry(int $id): ?TimeEntry
    {
        $timeEntry = $this->timeEntryRepository->find($id);

        if ($timeEntry && $timeEntry->isRunning()) {
            $endTime = now();
            $duration = $endTime->diffInSeconds($timeEntry->started_at);

            return $this->timeEntryRepository->update($id, [
                'ended_at' => $endTime,
                'duration' => $duration,
            ]);
        }

        return $timeEntry;
    }

    /**
     * Get currently running time entry for a user.
     */
    public function getRunningTimeEntryForUser(int $userId): ?TimeEntry
    {
        return $this->timeEntryRepository->getRunningForUser($userId);
    }

    /**
     * Get time entries for a specific date range.
     */
    public function getTimeEntriesForDateRange(int $userId, string $startDate, string $endDate): Collection
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return $this->timeEntryRepository->getForUserInDateRange($userId, $start, $end);
    }

    /**
     * Get time entries for a specific task.
     */
    public function getTimeEntriesForTask(int $taskId): Collection
    {
        return $this->timeEntryRepository->getForTask($taskId);
    }

    /**
     * Get time entries for a specific project.
     */
    public function getTimeEntriesForProject(int $projectId): Collection
    {
        return $this->timeEntryRepository->getForProject($projectId);
    }

    /**
     * Get time statistics for a specific date range.
     */
    public function getTimeStatistics(int $userId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $timeEntries = $this->timeEntryRepository->getForUserInDateRange($userId, $start, $end);

        $totalDuration = $this->timeEntryRepository->getTotalDuration($timeEntries);
        $billableDuration = $this->timeEntryRepository->getBillableDuration($timeEntries);

        $entriesByDate = $this->timeEntryRepository->groupByDate($timeEntries);
        $entriesByProject = $this->timeEntryRepository->groupByProject($timeEntries);
        $entriesByCategory = $this->timeEntryRepository->groupByCategory($timeEntries);

        // Calculate billable percentage
        $billablePercentage = $totalDuration > 0 ? ($billableDuration / $totalDuration) * 100 : 0;

        return [
            'total_duration' => $totalDuration,
            'billable_duration' => $billableDuration,
            'billable_percentage' => round($billablePercentage, 1),
            'entries_by_date' => $entriesByDate,
            'entries_by_project' => $entriesByProject,
            'entries_by_category' => $entriesByCategory,
            'total_days' => count($entriesByDate),
            'total_projects' => count($entriesByProject),
            'total_entries' => $timeEntries->count(),
        ];
    }

    /**
     * Get unbilled time entries for a project.
     */
    public function getUnbilledTimeEntriesForProject(int $projectId): Collection
    {
        return $this->timeEntryRepository->getUnbilledForProject($projectId);
    }
}
