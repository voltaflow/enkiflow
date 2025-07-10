<?php

namespace App\Services;

use App\DataTransferObjects\TimeReportDTO;
use App\Models\TimeEntry;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TimeReportService
{
    /**
     * Generate a report by date range
     */
    public function getReportByDateRange(Carbon $startDate, Carbon $endDate, array $filters = []): TimeReportDTO
    {
        $cacheKey = $this->generateCacheKey('date_range', [
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
            'filters' => $filters
        ]);
        
        return Cache::tags(['reports', 'tenant:' . tenant('id')])
            ->remember($cacheKey, now()->addHours(24), function () use ($startDate, $endDate, $filters) {
                $query = TimeEntry::query()
                    ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
                
                // Apply filters
                if (!empty($filters['project_id'])) {
                    $query->where('project_id', $filters['project_id']);
                }
                
                if (!empty($filters['user_id'])) {
                    $query->where('user_id', $filters['user_id']);
                }
                
                if (!empty($filters['client_id'])) {
                    $query->whereHas('project', function ($q) use ($filters) {
                        $q->where('client_id', $filters['client_id']);
                    });
                }
                
                if (isset($filters['is_billable']) && $filters['is_billable'] !== null) {
                    $query->where('is_billable', $filters['is_billable']);
                }
                
                // Get all entries with relations
                $results = $query->with(['project.client', 'user', 'task'])
                    ->orderBy('started_at', 'desc')
                    ->get();
                
                // Transform to DTO
                return new TimeReportDTO(
                    startDate: $startDate,
                    endDate: $endDate,
                    entries: $results,
                    filters: $filters
                );
            });
    }
    
    /**
     * Generate a report by project
     */
    public function getReportByProject(Project $project, ?Carbon $startDate = null, ?Carbon $endDate = null): TimeReportDTO
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();
        
        $cacheKey = $this->generateCacheKey('project', [
            'project_id' => $project->id,
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString()
        ]);
        
        return Cache::tags(['reports', 'tenant:' . tenant('id'), 'project:' . $project->id])
            ->remember($cacheKey, now()->addHours(12), function () use ($project, $startDate, $endDate) {
                // Get all entries for the project
                $results = TimeEntry::where('project_id', $project->id)
                    ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->with(['user', 'task'])
                    ->orderBy('started_at', 'desc')
                    ->get();
                
                return new TimeReportDTO(
                    startDate: $startDate,
                    endDate: $endDate,
                    entries: $results,
                    project: $project
                );
            });
    }
    
    /**
     * Generate a billing report
     */
    public function getBillingReport(Carbon $startDate, Carbon $endDate, array $filters = []): TimeReportDTO
    {
        $cacheKey = $this->generateCacheKey('billing', [
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
            'filters' => $filters
        ]);
        
        return Cache::tags(['reports', 'billing', 'tenant:' . tenant('id')])
            ->remember($cacheKey, now()->addHours(6), function () use ($startDate, $endDate, $filters) {
                $query = TimeEntry::query()
                    ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->where('is_billable', true);
                
                // Apply filters
                if (!empty($filters['project_id'])) {
                    $query->where('project_id', $filters['project_id']);
                }
                
                if (!empty($filters['client_id'])) {
                    $query->whereHas('project', function ($q) use ($filters) {
                        $q->where('client_id', $filters['client_id']);
                    });
                }
                
                // Get all billable entries with relations
                $results = $query->with(['project.client', 'user', 'task'])
                    ->orderBy('started_at', 'desc')
                    ->get();
                
                return new TimeReportDTO(
                    startDate: $startDate,
                    endDate: $endDate,
                    entries: $results,
                    filters: $filters,
                    isBillingReport: true
                );
            });
    }
    
    /**
     * Generate a user productivity report
     */
    public function getUserProductivityReport(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): TimeReportDTO
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();
        
        $cacheKey = $this->generateCacheKey('user_productivity', [
            'user_id' => $user->id,
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString()
        ]);
        
        return Cache::tags(['reports', 'tenant:' . tenant('id'), 'user:' . $user->id])
            ->remember($cacheKey, now()->addHours(12), function () use ($user, $startDate, $endDate) {
                $results = TimeEntry::where('user_id', $user->id)
                    ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->select(
                        'project_id',
                        DB::raw('DATE(started_at) as entry_date'),
                        DB::raw('SUM(duration) as total_duration')
                    )
                    ->groupBy('project_id', 'entry_date')
                    ->with(['project'])
                    ->get();
                
                return new TimeReportDTO(
                    startDate: $startDate,
                    endDate: $endDate,
                    entries: $results,
                    user: $user
                );
            });
    }
    
    /**
     * Get summary report view
     */
    public function getSummaryReport(Carbon $startDate, Carbon $endDate, string $groupBy = 'project'): TimeReportDTO
    {
        $cacheKey = $this->generateCacheKey('summary', [
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
            'group_by' => $groupBy
        ]);
        
        return Cache::tags(['reports', 'tenant:' . tenant('id')])
            ->remember($cacheKey, now()->addHours(12), function () use ($startDate, $endDate, $groupBy) {
                // Get all entries with relations for the summary
                $results = TimeEntry::query()
                    ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->with(['project.client', 'user', 'task'])
                    ->orderBy('started_at', 'desc')
                    ->get();
                
                return new TimeReportDTO(
                    startDate: $startDate,
                    endDate: $endDate,
                    entries: $results,
                    filters: ['group_by' => $groupBy]
                );
            });
    }
    
    /**
     * Get weekly report view
     */
    public function getWeeklyReport(Carbon $startDate, Carbon $endDate): TimeReportDTO
    {
        $cacheKey = $this->generateCacheKey('weekly', [
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString()
        ]);
        
        return Cache::tags(['reports', 'tenant:' . tenant('id')])
            ->remember($cacheKey, now()->addHours(12), function () use ($startDate, $endDate) {
                // Get all entries for the week with day of week info
                $results = TimeEntry::query()
                    ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->selectRaw('time_entries.*, EXTRACT(DOW FROM started_at) as day_of_week')
                    ->with(['user', 'project', 'task'])
                    ->orderBy('started_at')
                    ->get();
                
                return new TimeReportDTO(
                    startDate: $startDate,
                    endDate: $endDate,
                    entries: $results
                );
            });
    }
    
    /**
     * Generate cache key based on report type and parameters
     */
    private function generateCacheKey(string $reportType, array $params): string
    {
        $tenantId = tenant('id');
        $paramsString = md5(json_encode($params));
        
        return "report:{$tenantId}:{$reportType}:{$paramsString}";
    }
    
    /**
     * Invalidate cache for specific tenant
     */
    public function invalidateTenantCache(string $tenantId): void
    {
        Cache::tags(['tenant:' . $tenantId])->flush();
    }
    
    /**
     * Invalidate cache for specific project
     */
    public function invalidateProjectCache(string $projectId): void
    {
        Cache::tags(['project:' . $projectId])->flush();
    }
    
    /**
     * Invalidate cache for specific user
     */
    public function invalidateUserCache(string $userId): void
    {
        Cache::tags(['user:' . $userId])->flush();
    }
}