<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\WeeklyTimesheet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WeeklyTimesheetController extends Controller
{
    /**
     * Display the weekly timesheet view.
     */
    public function index(Request $request)
    {
        $weekStart = $request->input('week', now()->startOfWeek()->format('Y-m-d'));
        $weekStartDate = Carbon::parse($weekStart)->startOfWeek();
        $weekEndDate = $weekStartDate->copy()->endOfWeek();

        // Get or create timesheet for the week
        $timesheet = WeeklyTimesheet::findOrCreateForWeek(auth()->id(), $weekStartDate);

        // Get all time entries for the week
        $timeEntries = TimeEntry::with(['project', 'task'])
            ->where('user_id', auth()->id())
            ->forWeek($weekStartDate)
            ->get();

        // Get all projects with tasks for the user
        $projects = Project::with(['tasks' => function ($query) {
            $query->where('status', '!=', 'completed');
        }])
            ->whereHas('timeEntries', function ($query) use ($weekStartDate, $weekEndDate) {
                $query->where('user_id', auth()->id())
                    ->whereBetween('started_at', [$weekStartDate, $weekEndDate]);
            })
            ->orWhere('status', 'active')
            ->get();

        // Organize entries by project/task and date
        $entriesByProjectTask = $this->organizeEntriesByProjectTask($timeEntries, $weekStartDate);

        // Calculate daily totals
        $dailyTotals = $this->calculateDailyTotals($timeEntries, $weekStartDate);

        return Inertia::render('TimeTracking/WeeklyTimesheet', [
            'weekStart' => $weekStartDate->format('Y-m-d'),
            'weekEnd' => $weekEndDate->format('Y-m-d'),
            'timesheet' => $timesheet,
            'projects' => $projects,
            'entriesByProjectTask' => $entriesByProjectTask,
            'dailyTotals' => $dailyTotals,
            'weekTotal' => $timesheet->total_hours,
        ]);
    }

    /**
     * Update time entries for the week (batch update).
     */
    public function update(Request $request, WeeklyTimesheet $timesheet)
    {
        // Validate that the user owns this timesheet
        if ($timesheet->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if timesheet is editable
        if (! $timesheet->is_editable) {
            return response()->json(['message' => 'Timesheet is locked'], 403);
        }

        $validated = $request->validate([
            'entries' => 'required|array',
            'entries.*.project_id' => 'required|exists:projects,id',
            'entries.*.task_id' => 'nullable|exists:tasks,id',
            'entries.*.date' => 'required|date',
            'entries.*.hours' => 'required|numeric|min:0|max:24',
            'entries.*.description' => 'nullable|string|max:255',
            'entries.*.is_billable' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $timesheet) {
            foreach ($validated['entries'] as $entryData) {
                $date = Carbon::parse($entryData['date']);
                $hours = $entryData['hours'];

                // Skip if hours is 0
                if ($hours == 0) {
                    // Delete existing entry if any
                    TimeEntry::where('user_id', auth()->id())
                        ->where('project_id', $entryData['project_id'])
                        ->where('task_id', $entryData['task_id'])
                        ->whereDate('started_at', $date)
                        ->where('weekly_timesheet_id', $timesheet->id)
                        ->delete();

                    continue;
                }

                // Find or create entry
                $entry = TimeEntry::firstOrNew([
                    'user_id' => auth()->id(),
                    'project_id' => $entryData['project_id'],
                    'task_id' => $entryData['task_id'],
                    'weekly_timesheet_id' => $timesheet->id,
                    'started_at' => $date->setTime(9, 0), // Default start time
                ]);

                // Update entry
                $entry->ended_at = $date->copy()->setTime(9, 0)->addHours($hours);
                $entry->duration = $hours * 3600; // Convert to seconds
                $entry->description = $entryData['description'] ?? '';
                $entry->is_billable = $entryData['is_billable'] ?? true;
                $entry->created_from = 'manual';
                $entry->save();
            }

            // Recalculate timesheet totals
            $timesheet->calculateTotals()->save();
        });

        return response()->json([
            'message' => 'Timesheet updated successfully',
            'timesheet' => $timesheet->fresh()->load('timeEntries'),
        ]);
    }

    /**
     * Submit timesheet for approval.
     */
    public function submit(WeeklyTimesheet $timesheet)
    {
        // Validate ownership
        if ($timesheet->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if can be submitted
        if ($timesheet->status !== 'draft') {
            return response()->json(['message' => 'Timesheet cannot be submitted'], 400);
        }

        // Check if has entries
        if ($timesheet->timeEntries()->count() === 0) {
            return response()->json(['message' => 'Cannot submit empty timesheet'], 400);
        }

        $timesheet->submit();

        return response()->json([
            'message' => 'Timesheet submitted successfully',
            'timesheet' => $timesheet,
        ]);
    }

    /**
     * Quick add entry from timesheet view.
     */
    public function quickAdd(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0|max:24',
            'description' => 'nullable|string|max:255',
            'is_billable' => 'boolean',
            'is_placeholder' => 'boolean', // Flag to indicate this is just a placeholder
        ]);

        $date = Carbon::parse($validated['date']);
        $weekStart = $date->copy()->startOfWeek();

        // Get or create timesheet
        $timesheet = WeeklyTimesheet::findOrCreateForWeek(auth()->id(), $weekStart);

        // Check if editable
        if (! $timesheet->is_editable) {
            return response()->json(['message' => 'Timesheet is locked'], 403);
        }

        // If hours is 0 and not a placeholder, delete the entry
        if ($validated['hours'] == 0 && !($validated['is_placeholder'] ?? false)) {
            $deletedCount = TimeEntry::where('user_id', auth()->id())
                ->where('project_id', $validated['project_id'])
                ->where('task_id', $validated['task_id'])
                ->whereDate('started_at', $date)
                ->delete();
            
            // Update timesheet totals
            $timesheet->calculateTotals()->save();
            
            return response()->json([
                'message' => $deletedCount > 0 ? 'Entry deleted successfully' : 'No entry found to delete',
                'deleted' => $deletedCount > 0,
                'timesheet' => $timesheet,
            ]);
        }

        // Check if an entry already exists for this project/task/date combination
        $existingEntry = TimeEntry::where('user_id', auth()->id())
            ->where('project_id', $validated['project_id'])
            ->where('task_id', $validated['task_id'])
            ->whereDate('started_at', $date)
            ->first();

        if ($existingEntry) {
            // Update existing entry
            $existingEntry->update([
                'duration' => $validated['hours'] * 3600,
                'ended_at' => Carbon::parse($existingEntry->started_at)->addHours($validated['hours']),
                'description' => $validated['description'] ?? '',
                'is_billable' => $validated['is_billable'] ?? true,
            ]);
            $entry = $existingEntry;
        } else {
            // Create new entry
            $entry = TimeEntry::create([
                'user_id' => auth()->id(),
                'weekly_timesheet_id' => $timesheet->id,
                'project_id' => $validated['project_id'],
                'task_id' => $validated['task_id'],
                'started_at' => $date->setTime(9, 0),
                'ended_at' => $date->copy()->setTime(9, 0)->addHours($validated['hours']),
                'duration' => $validated['hours'] * 3600,
                'description' => $validated['description'] ?? '',
                'is_billable' => $validated['is_billable'] ?? true,
                'created_from' => 'manual',
            ]);
        }

        // Update timesheet totals
        $timesheet->calculateTotals()->save();
        
        // Remove from virtual rows if it was there
        $weekKey = $weekStart->format('Y-m-d');
        $sessionKey = "timesheet_virtual_rows_" . auth()->id() . "_" . $weekKey;
        $virtualRows = session($sessionKey, []);
        $rowKey = $validated['project_id'] . '-' . ($validated['task_id'] ?? '0');
        
        if (($key = array_search($rowKey, $virtualRows)) !== false) {
            unset($virtualRows[$key]);
            session([$sessionKey => array_values($virtualRows)]);
        }

        return response()->json([
            'message' => 'Entry added successfully',
            'entry' => $entry->load(['project', 'task']),
            'timesheet' => $timesheet,
        ]);
    }

    /**
     * Get timesheet data for a specific week.
     */
    public function weekData(Request $request)
    {
        $weekStart = Carbon::parse($request->input('week', now()->startOfWeek()));
        $weekEnd = $weekStart->copy()->endOfWeek();

        $timesheet = WeeklyTimesheet::where('user_id', auth()->id())
            ->forWeek($weekStart)
            ->first();

        $entries = TimeEntry::with(['project', 'task'])
            ->where('user_id', auth()->id())
            ->forWeek($weekStart)
            ->get();

        $organized = $this->organizeEntriesByProjectTask($entries, $weekStart);
        $dailyTotals = $this->calculateDailyTotals($entries, $weekStart);

        return response()->json([
            'weekStart' => $weekStart->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
            'timesheet' => $timesheet,
            'entries' => $organized,
            'dailyTotals' => $dailyTotals,
            'weekTotal' => $entries->sum('duration') / 3600,
        ]);
    }

    /**
     * Organize entries by project/task and date.
     */
    private function organizeEntriesByProjectTask($entries, $weekStart)
    {
        $organized = [];

        foreach ($entries as $entry) {
            $projectId = $entry->project_id;
            $taskId = $entry->task_id ?? 'no-task';
            $date = $entry->started_at->format('Y-m-d');

            if (! isset($organized[$projectId])) {
                $organized[$projectId] = [
                    'project' => $entry->project,
                    'tasks' => [],
                ];
            }

            if (! isset($organized[$projectId]['tasks'][$taskId])) {
                $organized[$projectId]['tasks'][$taskId] = [
                    'task' => $entry->task,
                    'entries' => [],
                ];
            }

            $organized[$projectId]['tasks'][$taskId]['entries'][$date] = [
                'id' => $entry->id,
                'duration' => $entry->duration, // Keep in seconds
                'hours' => round($entry->duration / 3600, 2),
                'description' => $entry->description,
                'is_billable' => $entry->is_billable,
                'locked' => $entry->locked ?? false,
                'user_id' => $entry->user_id,
            ];
        }

        return $organized;
    }

    /**
     * Calculate daily totals.
     */
    private function calculateDailyTotals($entries, $weekStart)
    {
        $totals = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');

            $dayEntries = $entries->filter(function ($entry) use ($date) {
                return $entry->started_at->format('Y-m-d') === $date->format('Y-m-d');
            });

            $totals[$dateStr] = round($dayEntries->sum('duration') / 3600, 2);
        }

        return $totals;
    }
}
