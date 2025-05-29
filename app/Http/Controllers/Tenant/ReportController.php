<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Models\DailySummary;
use App\Services\TrackingAnalyzer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected TrackingAnalyzer $trackingAnalyzer;

    public function __construct(TrackingAnalyzer $trackingAnalyzer)
    {
        $this->trackingAnalyzer = $trackingAnalyzer;
    }

    /**
     * Get daily report data.
     */
    public function daily(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $date = $validated['date'] ?? today()->toDateString();
        $userId = $validated['user_id'] ?? $request->user()->id;

        // Get time entries for the day
        $timeEntries = TimeEntry::forUser($userId)
            ->whereDate('started_at', $date)
            ->with(['project', 'task'])
            ->orderBy('started_at', 'desc')
            ->get();

        // Get daily summary
        $summary = DailySummary::generateForUserAndDate($userId, $date);

        // Calculate total time by project
        $projectTime = $timeEntries->groupBy('project_id')->map(function ($entries) {
            return [
                'project' => $entries->first()->project,
                'total_duration' => $entries->sum('duration'),
                'entries_count' => $entries->count(),
            ];
        })->values();

        return response()->json([
            'date' => $date,
            'summary' => [
                'total_time' => $summary->total_time_seconds,
                'manual_time' => $summary->manual_time,
                'tracked_time' => $summary->tracked_time,
                'formatted_total' => $summary->formatted_total_time,
                'productivity' => $summary->productivity_summary,
            ],
            'time_entries' => $timeEntries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'description' => $entry->description,
                    'duration' => $entry->duration,
                    'formatted_duration' => $entry->formatted_duration,
                    'started_at' => $entry->started_at,
                    'ended_at' => $entry->ended_at,
                    'project' => $entry->project,
                    'task' => $entry->task,
                    'is_billable' => $entry->is_billable,
                    'created_via' => $entry->created_via,
                ];
            }),
            'project_breakdown' => $projectTime,
        ]);
    }

    /**
     * Get weekly report data.
     */
    public function weekly(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $startDate = $validated['start_date'] 
            ? Carbon::parse($validated['start_date'])->startOfWeek()
            : now()->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();
        $userId = $validated['user_id'] ?? $request->user()->id;

        // Get time entries for the week
        $timeEntries = TimeEntry::forUser($userId)
            ->between($startDate, $endDate)
            ->with(['project', 'task'])
            ->get();

        // Group by day
        $dailyData = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayEntries = $timeEntries->filter(function ($entry) use ($date) {
                return $entry->started_at->toDateString() === $date->toDateString();
            });

            $dailyData[] = [
                'date' => $date->toDateString(),
                'day_name' => $date->format('l'),
                'total_duration' => $dayEntries->sum('duration'),
                'entries_count' => $dayEntries->count(),
            ];
        }

        // Get productivity stats if tracking is enabled
        $productivityStats = null;
        if (tenant()->auto_tracking_enabled) {
            $productivityStats = $this->trackingAnalyzer->getProductivityStats(
                $request->user(),
                $startDate,
                $endDate
            );
        }

        return response()->json([
            'week_start' => $startDate->toDateString(),
            'week_end' => $endDate->toDateString(),
            'total_duration' => $timeEntries->sum('duration'),
            'total_entries' => $timeEntries->count(),
            'daily_breakdown' => $dailyData,
            'project_breakdown' => $this->getProjectBreakdown($timeEntries),
            'productivity_stats' => $productivityStats,
        ]);
    }

    /**
     * Get monthly report data.
     */
    public function monthly(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $date = $validated['month'] ? Carbon::parse($validated['month']) : now();
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        $userId = $validated['user_id'] ?? $request->user()->id;

        // Get time entries for the month
        $timeEntries = TimeEntry::forUser($userId)
            ->between($startDate, $endDate)
            ->with(['project', 'task'])
            ->get();

        // Group by week
        $weeklyData = [];
        $currentWeek = $startDate->copy()->startOfWeek();
        
        while ($currentWeek <= $endDate) {
            $weekEnd = $currentWeek->copy()->endOfWeek();
            $weekEntries = $timeEntries->filter(function ($entry) use ($currentWeek, $weekEnd) {
                return $entry->started_at >= $currentWeek && $entry->started_at <= $weekEnd;
            });

            $weeklyData[] = [
                'week_start' => $currentWeek->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'total_duration' => $weekEntries->sum('duration'),
                'entries_count' => $weekEntries->count(),
            ];

            $currentWeek->addWeek();
        }

        return response()->json([
            'month' => $date->format('F Y'),
            'month_start' => $startDate->toDateString(),
            'month_end' => $endDate->toDateString(),
            'total_duration' => $timeEntries->sum('duration'),
            'total_entries' => $timeEntries->count(),
            'weekly_breakdown' => $weeklyData,
            'project_breakdown' => $this->getProjectBreakdown($timeEntries),
            'category_breakdown' => $this->getCategoryBreakdown($timeEntries),
        ]);
    }

    /**
     * Get project-specific report.
     */
    public function project(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : now()->subDays(30);
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : now();

        $timeEntries = TimeEntry::where('project_id', $validated['project_id'])
            ->between($startDate, $endDate)
            ->with(['user', 'task'])
            ->get();

        // Group by user
        $userBreakdown = $timeEntries->groupBy('user_id')->map(function ($entries) {
            return [
                'user' => $entries->first()->user,
                'total_duration' => $entries->sum('duration'),
                'entries_count' => $entries->count(),
                'billable_duration' => $entries->where('is_billable', true)->sum('duration'),
            ];
        })->values();

        // Group by task
        $taskBreakdown = $timeEntries->groupBy('task_id')->map(function ($entries) {
            return [
                'task' => $entries->first()->task,
                'total_duration' => $entries->sum('duration'),
                'entries_count' => $entries->count(),
            ];
        })->values();

        return response()->json([
            'project_id' => $validated['project_id'],
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_duration' => $timeEntries->sum('duration'),
            'billable_duration' => $timeEntries->where('is_billable', true)->sum('duration'),
            'total_entries' => $timeEntries->count(),
            'user_breakdown' => $userBreakdown,
            'task_breakdown' => $taskBreakdown,
            'daily_trend' => $this->getDailyTrend($timeEntries, $startDate, $endDate),
        ]);
    }

    /**
     * Get project breakdown from time entries.
     */
    private function getProjectBreakdown($timeEntries)
    {
        return $timeEntries->groupBy('project_id')->map(function ($entries) {
            return [
                'project' => $entries->first()->project,
                'total_duration' => $entries->sum('duration'),
                'entries_count' => $entries->count(),
                'percentage' => 0, // Will be calculated in frontend
            ];
        })->values();
    }

    /**
     * Get category breakdown from time entries.
     */
    private function getCategoryBreakdown($timeEntries)
    {
        return $timeEntries->groupBy('category_id')->map(function ($entries) {
            return [
                'category' => $entries->first()->category,
                'total_duration' => $entries->sum('duration'),
                'entries_count' => $entries->count(),
            ];
        })->values();
    }

    /**
     * Get daily trend data.
     */
    private function getDailyTrend($timeEntries, $startDate, $endDate)
    {
        $trend = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayEntries = $timeEntries->filter(function ($entry) use ($currentDate) {
                return $entry->started_at->toDateString() === $currentDate->toDateString();
            });

            $trend[] = [
                'date' => $currentDate->toDateString(),
                'duration' => $dayEntries->sum('duration'),
                'entries' => $dayEntries->count(),
            ];

            $currentDate->addDay();
        }

        return $trend;
    }
}