<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard.
     */
    public function index()
    {
        return Inertia::render('TimeTracking/Analytics', [
            'stats' => $this->getOverviewStats(),
            'weeklyData' => $this->getWeeklyData(),
            'projectDistribution' => $this->getProjectDistribution(),
            'productivityTrends' => $this->getProductivityTrends(),
        ]);
    }

    /**
     * Get analytics data via API.
     */
    public function data(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'metric' => 'nullable|in:hours,productivity,projects,tasks',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $metric = $validated['metric'] ?? 'hours';

        return response()->json([
            'data' => $this->getMetricData($metric, $startDate, $endDate),
            'summary' => $this->getPeriodSummary($startDate, $endDate),
        ]);
    }

    /**
     * Get overview statistics.
     */
    private function getOverviewStats()
    {
        $userId = Auth::id();
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'today' => [
                'hours' => TimeEntry::where('user_id', $userId)
                    ->whereDate('started_at', $today)
                    ->sum('duration') / 3600,
                'entries' => TimeEntry::where('user_id', $userId)
                    ->whereDate('started_at', $today)
                    ->count(),
            ],
            'week' => [
                'hours' => TimeEntry::where('user_id', $userId)
                    ->where('started_at', '>=', $thisWeek)
                    ->sum('duration') / 3600,
                'billable_hours' => TimeEntry::where('user_id', $userId)
                    ->where('started_at', '>=', $thisWeek)
                    ->where('is_billable', true)
                    ->sum('duration') / 3600,
                'projects' => TimeEntry::where('user_id', $userId)
                    ->where('started_at', '>=', $thisWeek)
                    ->distinct('project_id')
                    ->count('project_id'),
            ],
            'month' => [
                'hours' => TimeEntry::where('user_id', $userId)
                    ->where('started_at', '>=', $thisMonth)
                    ->sum('duration') / 3600,
                'average_daily' => TimeEntry::where('user_id', $userId)
                    ->where('started_at', '>=', $thisMonth)
                    ->groupBy(DB::raw('DATE(started_at)'))
                    ->selectRaw('SUM(duration) / 3600 as daily_hours')
                    ->pluck('daily_hours')
                    ->avg(),
            ],
        ];
    }

    /**
     * Get weekly data for charts.
     */
    private function getWeeklyData()
    {
        $userId = Auth::id();
        $startOfWeek = Carbon::now()->startOfWeek();
        $data = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $entries = TimeEntry::where('user_id', $userId)
                ->whereDate('started_at', $date)
                ->get();

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'total_hours' => $entries->sum('duration') / 3600,
                'billable_hours' => $entries->where('is_billable', true)->sum('duration') / 3600,
                'non_billable_hours' => $entries->where('is_billable', false)->sum('duration') / 3600,
                'entry_count' => $entries->count(),
            ];
        }

        return $data;
    }

    /**
     * Get project distribution data.
     */
    private function getProjectDistribution()
    {
        $userId = Auth::id();
        $startDate = Carbon::now()->subDays(30);

        $projectData = TimeEntry::where('user_id', $userId)
            ->where('started_at', '>=', $startDate)
            ->with('project')
            ->get()
            ->groupBy('project_id')
            ->map(function ($entries, $projectId) {
                $project = $entries->first()->project;

                return [
                    'id' => $projectId,
                    'name' => $project ? $project->name : 'No Project',
                    'hours' => $entries->sum('duration') / 3600,
                    'percentage' => 0, // Will be calculated client-side
                    'color' => $this->getProjectColor($projectId),
                ];
            })
            ->values()
            ->sortByDesc('hours')
            ->take(5);

        return $projectData;
    }

    /**
     * Get productivity trends.
     */
    private function getProductivityTrends()
    {
        $userId = Auth::id();
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(30);
        $trends = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayEntries = TimeEntry::where('user_id', $userId)
                ->whereDate('started_at', $date)
                ->get();

            $totalHours = $dayEntries->sum('duration') / 3600;
            $focusTime = $this->calculateFocusTime($dayEntries);
            $productivity = $totalHours > 0 ? ($focusTime / $totalHours) * 100 : 0;

            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'total_hours' => round($totalHours, 2),
                'focus_hours' => round($focusTime, 2),
                'productivity_score' => round($productivity, 1),
                'entry_count' => $dayEntries->count(),
            ];
        }

        return $trends;
    }

    /**
     * Calculate focus time (continuous work periods > 30 minutes).
     */
    private function calculateFocusTime($entries)
    {
        $focusTime = 0;
        $focusThreshold = 30 * 60; // 30 minutes in seconds

        foreach ($entries as $entry) {
            if ($entry->duration >= $focusThreshold) {
                $focusTime += $entry->duration;
            }
        }

        return $focusTime / 3600; // Convert to hours
    }

    /**
     * Get metric-specific data.
     */
    private function getMetricData($metric, $startDate, $endDate)
    {
        $userId = Auth::id();

        switch ($metric) {
            case 'productivity':
                return $this->getProductivityMetric($userId, $startDate, $endDate);

            case 'projects':
                return $this->getProjectsMetric($userId, $startDate, $endDate);

            case 'tasks':
                return $this->getTasksMetric($userId, $startDate, $endDate);

            case 'hours':
            default:
                return $this->getHoursMetric($userId, $startDate, $endDate);
        }
    }

    /**
     * Get hours metric data.
     */
    private function getHoursMetric($userId, $startDate, $endDate)
    {
        return TimeEntry::where('user_id', $userId)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(started_at)'))
            ->selectRaw('DATE(started_at) as date, SUM(duration) / 3600 as hours, COUNT(*) as entries')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'value' => round($item->hours, 2),
                    'entries' => $item->entries,
                ];
            });
    }

    /**
     * Get productivity metric data.
     */
    private function getProductivityMetric($userId, $startDate, $endDate)
    {
        $data = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayEntries = TimeEntry::where('user_id', $userId)
                ->whereDate('started_at', $date)
                ->get();

            if ($dayEntries->isEmpty()) {
                continue;
            }

            $totalHours = $dayEntries->sum('duration') / 3600;
            $billableHours = $dayEntries->where('is_billable', true)->sum('duration') / 3600;
            $productivity = $totalHours > 0 ? ($billableHours / $totalHours) * 100 : 0;

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'value' => round($productivity, 1),
                'billable' => round($billableHours, 2),
                'total' => round($totalHours, 2),
            ];
        }

        return $data;
    }

    /**
     * Get projects metric data.
     */
    private function getProjectsMetric($userId, $startDate, $endDate)
    {
        return Project::withCount(['timeEntries as hours' => function ($query) use ($userId, $startDate, $endDate) {
            $query->where('user_id', $userId)
                ->whereBetween('started_at', [$startDate, $endDate])
                ->select(DB::raw('SUM(duration) / 3600'));
        }])
            ->having('hours', '>', 0)
            ->orderBy('hours', 'desc')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'value' => round($project->hours, 2),
                    'status' => $project->status,
                ];
            });
    }

    /**
     * Get tasks metric data.
     */
    private function getTasksMetric($userId, $startDate, $endDate)
    {
        return Task::withCount(['timeEntries as hours' => function ($query) use ($userId, $startDate, $endDate) {
            $query->where('user_id', $userId)
                ->whereBetween('started_at', [$startDate, $endDate])
                ->select(DB::raw('SUM(duration) / 3600'));
        }])
            ->with('project')
            ->having('hours', '>', 0)
            ->orderBy('hours', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'project' => $task->project->name,
                    'value' => round($task->hours, 2),
                    'status' => $task->status,
                ];
            });
    }

    /**
     * Get period summary.
     */
    private function getPeriodSummary($startDate, $endDate)
    {
        $userId = Auth::id();

        $entries = TimeEntry::where('user_id', $userId)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->get();

        $workDays = TimeEntry::where('user_id', $userId)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->distinct(DB::raw('DATE(started_at)'))
            ->count(DB::raw('DATE(started_at)'));

        $totalHours = $entries->sum('duration') / 3600;
        $averagePerDay = $workDays > 0 ? $totalHours / $workDays : 0;

        return [
            'total_hours' => round($totalHours, 2),
            'billable_hours' => round($entries->where('is_billable', true)->sum('duration') / 3600, 2),
            'work_days' => $workDays,
            'average_per_day' => round($averagePerDay, 2),
            'projects_worked' => $entries->pluck('project_id')->unique()->filter()->count(),
            'tasks_completed' => $entries->pluck('task_id')->unique()->filter()->count(),
            'longest_session' => round($entries->max('duration') / 3600, 2),
        ];
    }

    /**
     * Get color for project visualization.
     */
    private function getProjectColor($projectId)
    {
        $colors = [
            '#3b82f6', // blue
            '#10b981', // emerald
            '#f59e0b', // amber
            '#ef4444', // red
            '#8b5cf6', // violet
            '#ec4899', // pink
            '#14b8a6', // teal
            '#f97316', // orange
        ];

        return $colors[$projectId % count($colors)];
    }

    /**
     * Export analytics report.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,csv',
        ]);

        // TODO: Implement export functionality

        return response()->json([
            'message' => 'Export functionality coming soon',
        ], 501);
    }
}
