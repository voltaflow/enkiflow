<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TimeEntry;
use Illuminate\Support\Facades\DB;

class ActivityAggregationService
{
    /**
     * Aggregate activity data for a time entry and update its metadata.
     */
    public function aggregateActivityForTimeEntry(TimeEntry $timeEntry): void
    {
        $activitySummary = $this->calculateActivitySummary($timeEntry);
        
        $metadata = $timeEntry->metadata ?? [];
        $metadata['activity_summary'] = $activitySummary;
        
        $timeEntry->metadata = $metadata;
        $timeEntry->save();
    }
    
    /**
     * Calculate activity summary for a time entry.
     */
    private function calculateActivitySummary(TimeEntry $timeEntry): array
    {
        $keyboardActivity = $timeEntry->activityLogs()
            ->where('activity_type', 'keyboard')
            ->count();
            
        $mouseActivity = $timeEntry->activityLogs()
            ->where('activity_type', 'mouse')
            ->count();
            
        $activeApps = $timeEntry->activityLogs()
            ->where('activity_type', 'application_focus')
            ->pluck('metadata')
            ->pluck('app_name')
            ->unique()
            ->values()
            ->toArray();
            
        $idlePeriods = $timeEntry->activityLogs()
            ->where('activity_type', 'idle')
            ->count();
            
        $activityScore = $this->calculateActivityScore(
            $keyboardActivity, 
            $mouseActivity, 
            $idlePeriods,
            $timeEntry->duration
        );
        
        return [
            'keyboard_activity' => $keyboardActivity,
            'mouse_activity' => $mouseActivity,
            'active_apps' => $activeApps,
            'idle_periods' => $idlePeriods,
            'activity_score' => $activityScore,
            'last_aggregated_at' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Calculate an activity score based on various metrics.
     */
    private function calculateActivityScore(
        int $keyboardActivity, 
        int $mouseActivity, 
        int $idlePeriods,
        int $duration
    ): float {
        if ($duration === 0) {
            return 0;
        }
        
        // Base activity score from keyboard and mouse
        $totalActivity = $keyboardActivity + $mouseActivity;
        $activityPerMinute = ($totalActivity / $duration) * 60;
        
        // Penalize for idle periods
        $idlePenalty = min($idlePeriods * 0.05, 0.5); // Max 50% penalty
        
        // Normalize to 0-100 scale
        $baseScore = min($activityPerMinute / 10, 1.0); // Assume 10 actions/minute is 100%
        $finalScore = max(0, $baseScore - $idlePenalty) * 100;
        
        return round($finalScore, 2);
    }
    
    /**
     * Aggregate activities for multiple time entries.
     */
    public function aggregateActivitiesForDateRange(int $userId, \DateTime $startDate, \DateTime $endDate): void
    {
        $timeEntries = TimeEntry::forUser($userId)
            ->between($startDate, $endDate)
            ->whereDoesntHave('metadata->activity_summary->last_aggregated_at')
            ->orWhere(function ($query) {
                $query->whereRaw("JSON_EXTRACT(metadata, '$.activity_summary.last_aggregated_at') < ?", [
                    now()->subHours(24)->toIso8601String()
                ]);
            })
            ->get();
            
        foreach ($timeEntries as $timeEntry) {
            $this->aggregateActivityForTimeEntry($timeEntry);
        }
    }
    
    /**
     * Get aggregated statistics for a user.
     */
    public function getUserActivityStats(int $userId, \DateTime $startDate, \DateTime $endDate): array
    {
        $stats = DB::table('time_entries')
            ->where('user_id', $userId)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->whereNotNull('metadata->activity_summary')
            ->selectRaw("
                AVG(JSON_EXTRACT(metadata, '$.activity_summary.activity_score')) as avg_activity_score,
                SUM(JSON_EXTRACT(metadata, '$.activity_summary.keyboard_activity')) as total_keyboard_activity,
                SUM(JSON_EXTRACT(metadata, '$.activity_summary.mouse_activity')) as total_mouse_activity,
                SUM(JSON_EXTRACT(metadata, '$.activity_summary.idle_periods')) as total_idle_periods,
                COUNT(*) as total_entries
            ")
            ->first();
            
        $mostUsedApps = DB::table('activity_logs')
            ->join('time_entries', 'activity_logs.time_entry_id', '=', 'time_entries.id')
            ->where('time_entries.user_id', $userId)
            ->where('activity_logs.activity_type', 'application_focus')
            ->whereBetween('activity_logs.timestamp', [$startDate, $endDate])
            ->selectRaw("
                JSON_EXTRACT(activity_logs.metadata, '$.app_name') as app_name,
                COUNT(*) as usage_count
            ")
            ->groupBy('app_name')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();
            
        return [
            'average_activity_score' => round($stats->avg_activity_score ?? 0, 2),
            'total_keyboard_activity' => (int) ($stats->total_keyboard_activity ?? 0),
            'total_mouse_activity' => (int) ($stats->total_mouse_activity ?? 0),
            'total_idle_periods' => (int) ($stats->total_idle_periods ?? 0),
            'total_entries' => (int) ($stats->total_entries ?? 0),
            'most_used_apps' => $mostUsedApps->map(function ($app) {
                return [
                    'name' => json_decode($app->app_name),
                    'usage_count' => $app->usage_count,
                ];
            })->toArray(),
        ];
    }
}