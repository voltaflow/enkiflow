<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TimeKpiService
{
    /**
     * Get all metrics for a given date range and scope
     */
    public function getMetrics(Carbon $startDate, Carbon $endDate, string $scope = 'tenant', ?int $scopeId = null): array
    {
        $cacheKey = $this->generateCacheKey('metrics', [
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
            'scope' => $scope,
            'scope_id' => $scopeId,
        ]);
        
        return Cache::tags(['kpis', 'tenant:' . tenant('id')])
            ->remember($cacheKey, now()->addHour(), function () use ($startDate, $endDate, $scope, $scopeId) {
                // Base query
                $query = TimeEntry::query()
                    ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
                
                // Apply scope filter
                if ($scope === 'user' && $scopeId) {
                    $query->where('user_id', $scopeId);
                } elseif ($scope === 'project' && $scopeId) {
                    $query->where('project_id', $scopeId);
                }
                
                // Calculate basic metrics (convert seconds to hours)
                $totalSeconds = $query->sum('duration');
                $billableSeconds = $query->clone()->where('is_billable', true)->sum('duration');
                
                $totalHours = $totalSeconds / 3600;
                $billableHours = $billableSeconds / 3600;
                $nonBillableHours = $totalHours - $billableHours;
                
                // Calculate billable utilization
                $billableUtilization = $totalHours > 0 ? ($billableHours / $totalHours) * 100 : 0;
                
                // Calculate average hourly rate (placeholder - need to get from project or user settings)
                // TODO: Implement proper hourly rate calculation based on project rates
                $averageRate = 0;
                
                // Calculate total revenue (placeholder for now)
                $totalRevenue = $billableHours * 50; // Assuming $50/hour default rate
                
                // Calculate capacity utilization (simplified for now)
                // TODO: Implement proper capacity calculation based on user settings
                $capacityUtilization = 80; // Mock value
                
                // Calculate budget burn rate for projects
                $budgetBurnRate = null;
                if ($scope === 'project' && $scopeId) {
                    // TODO: Implement proper budget burn rate calculation
                    $budgetBurnRate = 0;
                }
                
                // Calculate overtime hours (simplified for now)
                // TODO: Implement proper overtime calculation
                $overtimeHours = 0;
                
                // Calculate time to entry (simplified for now)
                // TODO: Implement proper time to entry calculation
                $avgTimeToEntry = 24; // Mock value in hours
                
                // Get working days count
                $workingDays = $this->getWorkingDaysCount($startDate, $endDate);
                $averageDailyHours = $workingDays > 0 ? $totalHours / $workingDays : 0;
                
                // Get counts
                $projectsCount = $query->clone()->distinct('project_id')->count('project_id');
                $tasksCompleted = $query->clone()->whereNotNull('task_id')->distinct('task_id')->count('task_id');
                
                return [
                    'total_hours' => round($totalHours, 2),
                    'billable_hours' => round($billableHours, 2),
                    'non_billable_hours' => round($nonBillableHours, 2),
                    'total_revenue' => round($totalRevenue, 2),
                    'average_daily_hours' => round($averageDailyHours, 2),
                    'utilization_rate' => round($billableUtilization, 2), // Alias for billable_utilization
                    'billable_utilization' => round($billableUtilization, 2),
                    'capacity_utilization' => round($capacityUtilization, 2),
                    'average_hourly_rate' => round($averageRate, 2),
                    'budget_burn_rate' => $budgetBurnRate ? round($budgetBurnRate, 2) : null,
                    'overtime_hours' => round($overtimeHours, 2),
                    'avg_time_to_entry' => $avgTimeToEntry,
                    'projects_count' => $projectsCount,
                    'tasks_completed' => $tasksCompleted,
                    'thresholds' => [
                        'billable_utilization' => [
                            'warning' => 70,
                            'critical' => 60
                        ],
                        'budget_burn_rate' => [
                            'warning' => 80,
                            'critical' => 90
                        ],
                        'avg_time_to_entry' => [
                            'warning' => 48, // hours
                            'critical' => 72 // hours
                        ]
                    ]
                ];
            });
    }
    
    /**
     * Calculate billable utilization for a specific scope
     */
    public function billableUtilization(Carbon $startDate, Carbon $endDate, ?int $userId = null): float
    {
        $query = TimeEntry::query()
            ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
            
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $totalHours = $query->sum('duration');
        $billableHours = $query->clone()->where('is_billable', true)->sum('duration');
        
        return $totalHours > 0 ? ($billableHours / $totalHours) * 100 : 0;
    }
    
    /**
     * Calculate capacity utilization (hours logged vs available hours)
     */
    private function calculateCapacityUtilization(Carbon $startDate, Carbon $endDate, string $scope, ?int $scopeId): float
    {
        // This would typically involve calculating working days in the period
        // and comparing against standard capacity (e.g., 8 hours per working day)
        
        // For users, we can calculate based on their work schedule
        if ($scope === 'user' && $scopeId) {
            $user = User::find($scopeId);
            if (!$user) return 0;
            
            $workingDays = $this->getWorkingDaysCount($startDate, $endDate);
            
            // Get user's capacity from space_users pivot table
            $spaceUser = DB::table('space_users')
                ->where('user_id', $user->id)
                ->where('space_id', tenant('id'))
                ->first();
                
            $weeklyCapacity = $spaceUser->capacity_hours ?? 40; // Default 40 hours per week
            $dailyCapacity = $weeklyCapacity / 5; // Assuming 5 working days
            $totalCapacity = $workingDays * $dailyCapacity;
            
            $hoursLogged = TimeEntry::where('user_id', $scopeId)
                ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->sum('duration');
                
            return $totalCapacity > 0 ? ($hoursLogged / $totalCapacity) * 100 : 0;
        }
        
        // For projects or tenant, we need to aggregate across all relevant users
        // This is a simplified implementation
        if ($scope === 'project' && $scopeId) {
            // Get all users who have logged time to this project
            $userIds = TimeEntry::where('project_id', $scopeId)
                ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->distinct('user_id')
                ->pluck('user_id');
                
            $totalCapacity = 0;
            $totalHoursLogged = 0;
            
            foreach ($userIds as $userId) {
                $spaceUser = DB::table('space_users')
                    ->where('user_id', $userId)
                    ->where('space_id', tenant('id'))
                    ->first();
                    
                $weeklyCapacity = $spaceUser->capacity_hours ?? 40;
                $dailyCapacity = $weeklyCapacity / 5;
                $workingDays = $this->getWorkingDaysCount($startDate, $endDate);
                $totalCapacity += $workingDays * $dailyCapacity;
                
                $hoursLogged = TimeEntry::where('user_id', $userId)
                    ->where('project_id', $scopeId)
                    ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->sum('duration');
                    
                $totalHoursLogged += $hoursLogged;
            }
            
            return $totalCapacity > 0 ? ($totalHoursLogged / $totalCapacity) * 100 : 0;
        }
        
        // For tenant scope, calculate across all users
        if ($scope === 'tenant') {
            $users = DB::table('space_users')
                ->where('space_id', tenant('id'))
                ->get();
                
            $totalCapacity = 0;
            $workingDays = $this->getWorkingDaysCount($startDate, $endDate);
            
            foreach ($users as $user) {
                $weeklyCapacity = $user->capacity_hours ?? 40;
                $dailyCapacity = $weeklyCapacity / 5;
                $totalCapacity += $workingDays * $dailyCapacity;
            }
            
            $hoursLogged = TimeEntry::whereBetween('date', [$startDate, $endDate])
                ->sum('duration');
                
            return $totalCapacity > 0 ? ($hoursLogged / $totalCapacity) * 100 : 0;
        }
        
        return 0;
    }
    
    /**
     * Calculate budget burn rate for a project
     */
    private function calculateBudgetBurnRate(int $projectId, float $totalBillableAmount): ?float
    {
        $project = Project::find($projectId);
        if (!$project || !isset($project->budget) || $project->budget <= 0) {
            return null;
        }
        
        return ($totalBillableAmount / $project->budget) * 100;
    }
    
    /**
     * Calculate overtime hours
     */
    private function calculateOvertimeHours(Carbon $startDate, Carbon $endDate, string $scope, ?int $scopeId): float
    {
        // Group entries by user and date, then calculate overtime
        $query = TimeEntry::query()
            ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->select(
                'user_id',
                DB::raw('DATE(started_at) as work_date'),
                DB::raw('SUM(duration) as daily_hours')
            )
            ->groupBy('user_id', 'work_date');
            
        // Apply scope filter
        if ($scope === 'user' && $scopeId) {
            $query->where('user_id', $scopeId);
        } elseif ($scope === 'project' && $scopeId) {
            $query->where('project_id', $scopeId);
        }
        
        $dailyEntries = $query->get();
        
        // Calculate overtime (hours beyond standard workday)
        $standardWorkday = 8; // hours
        $overtimeHours = 0;
        
        foreach ($dailyEntries as $entry) {
            $overtime = max(0, $entry->daily_hours - $standardWorkday);
            $overtimeHours += $overtime;
        }
        
        return $overtimeHours;
    }
    
    /**
     * Calculate average time between work date and entry creation
     */
    private function calculateAverageTimeToEntry(Carbon $startDate, Carbon $endDate, string $scope, ?int $scopeId): float
    {
        $query = TimeEntry::query()
            ->whereBetween('started_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->select(
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, date, created_at)) as avg_hours')
            );
            
        // Apply scope filter
        if ($scope === 'user' && $scopeId) {
            $query->where('user_id', $scopeId);
        } elseif ($scope === 'project' && $scopeId) {
            $query->where('project_id', $scopeId);
        }
        
        $result = $query->first();
        
        return $result ? round($result->avg_hours, 1) : 0;
    }
    
    /**
     * Get number of working days in a date range
     */
    private function getWorkingDaysCount(Carbon $startDate, Carbon $endDate): int
    {
        $days = 0;
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            // Skip weekends (6 = Saturday, 0 = Sunday)
            if ($current->dayOfWeek !== 0 && $current->dayOfWeek !== 6) {
                $days++;
            }
            $current->addDay();
        }
        
        return $days;
    }
    
    /**
     * Generate cache key for KPI metrics
     */
    private function generateCacheKey(string $type, array $params): string
    {
        $tenantId = tenant('id');
        $paramsString = md5(json_encode($params));
        
        return "kpi:{$tenantId}:{$type}:{$paramsString}";
    }
    
    /**
     * Invalidate KPI cache
     */
    public function invalidateKpiCache(): void
    {
        Cache::tags(['kpis', 'tenant:' . tenant('id')])->flush();
    }
}