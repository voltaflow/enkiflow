<?php

namespace App\Services;

use App\Models\ApplicationSession;
use App\Models\AppCategory;
use App\Models\DailySummary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TrackingAnalyzer
{
    /**
     * Process tracking data from external application.
     *
     * @param User $user
     * @param array $data
     * @return ApplicationSession
     */
    public function processTrackingData(User $user, array $data): ApplicationSession
    {
        // Get or create app category
        $appCategory = AppCategory::getOrCreateForApp($data['app_name']);

        // Check if there's an active session for this app
        $activeSession = ApplicationSession::forUser($user->id)
            ->active()
            ->where('app_name', $data['app_name'])
            ->first();

        if ($activeSession) {
            // Update the session if it's the same window
            if ($activeSession->window_title === $data['window_title']) {
                return $activeSession;
            }

            // End the previous session
            $activeSession->end();
        }

        // Create new session
        return ApplicationSession::create([
            'user_id' => $user->id,
            'app_name' => $data['app_name'],
            'window_title' => $data['window_title'] ?? null,
            'start_time' => $data['start_time'] ?? now(),
            'end_time' => null,
            'duration_seconds' => 0,
        ]);
    }

    /**
     * Process heartbeat to keep session alive.
     *
     * @param ApplicationSession $session
     * @return ApplicationSession
     */
    public function processHeartbeat(ApplicationSession $session): ApplicationSession
    {
        // Update the session to keep it alive
        $session->touch();

        return $session;
    }

    /**
     * End a tracking session.
     *
     * @param ApplicationSession $session
     * @return ApplicationSession
     */
    public function endSession(ApplicationSession $session): ApplicationSession
    {
        $session->end();

        return $session;
    }

    /**
     * Get productivity stats for a user and date range.
     *
     * @param User $user
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getProductivityStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = ApplicationSession::forUser($user->id)
            ->between($startDate, $endDate)
            ->with('category')
            ->get();

        $stats = [
            'total_time' => 0,
            'productive_time' => 0,
            'neutral_time' => 0,
            'distracting_time' => 0,
            'app_breakdown' => [],
            'category_breakdown' => [],
        ];

        foreach ($sessions as $session) {
            $duration = $session->duration_seconds;
            $stats['total_time'] += $duration;

            // Productivity breakdown
            switch ($session->productivity_level) {
                case 'productive':
                    $stats['productive_time'] += $duration;
                    break;
                case 'distracting':
                    $stats['distracting_time'] += $duration;
                    break;
                default:
                    $stats['neutral_time'] += $duration;
                    break;
            }

            // App breakdown
            if (!isset($stats['app_breakdown'][$session->app_name])) {
                $stats['app_breakdown'][$session->app_name] = 0;
            }
            $stats['app_breakdown'][$session->app_name] += $duration;

            // Category breakdown
            $category = $session->category->category ?? 'Other';
            if (!isset($stats['category_breakdown'][$category])) {
                $stats['category_breakdown'][$category] = 0;
            }
            $stats['category_breakdown'][$category] += $duration;
        }

        // Sort app breakdown by duration
        arsort($stats['app_breakdown']);

        // Calculate percentages
        if ($stats['total_time'] > 0) {
            $stats['productive_percentage'] = round(($stats['productive_time'] / $stats['total_time']) * 100, 2);
            $stats['neutral_percentage'] = round(($stats['neutral_time'] / $stats['total_time']) * 100, 2);
            $stats['distracting_percentage'] = round(($stats['distracting_time'] / $stats['total_time']) * 100, 2);
        } else {
            $stats['productive_percentage'] = 0;
            $stats['neutral_percentage'] = 0;
            $stats['distracting_percentage'] = 0;
        }

        return $stats;
    }

    /**
     * Get time spent on each project based on tracking data.
     *
     * @param User $user
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getProjectTimeFromTracking(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        // This would require more sophisticated analysis to map application usage to projects
        // For now, return empty collection
        return collect();
    }

    /**
     * Generate daily summary for a user.
     *
     * @param User $user
     * @param string $date
     * @return DailySummary
     */
    public function generateDailySummary(User $user, string $date): DailySummary
    {
        return DailySummary::generateForUserAndDate($user->id, $date);
    }

    /**
     * Clean up old sessions that were not properly closed.
     *
     * @param int $hoursThreshold
     * @return int Number of sessions closed
     */
    public function cleanupStaleSessions(int $hoursThreshold = 24): int
    {
        $threshold = now()->subHours($hoursThreshold);
        $staleSessions = ApplicationSession::active()
            ->where('updated_at', '<', $threshold)
            ->get();

        $count = 0;
        foreach ($staleSessions as $session) {
            $session->end();
            $count++;
        }

        return $count;
    }

    /**
     * Link application sessions to a time entry.
     *
     * @param int $timeEntryId
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @param int $userId
     * @return int Number of sessions linked
     */
    public function linkSessionsToTimeEntry(int $timeEntryId, Carbon $startTime, Carbon $endTime, int $userId): int
    {
        return ApplicationSession::forUser($userId)
            ->where('start_time', '>=', $startTime)
            ->where('start_time', '<=', $endTime)
            ->whereNull('linked_time_entry_id')
            ->update(['linked_time_entry_id' => $timeEntryId]);
    }
}