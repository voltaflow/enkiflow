<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTimeEntryRequest;
use App\Http\Requests\Tenant\UpdateTimeEntryRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\TimeEntryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TimeEntryController extends Controller
{
    /**
     * The time entry service instance.
     */
    protected $timeEntryService;

    /**
     * Create a new controller instance.
     */
    public function __construct(TimeEntryService $timeEntryService)
    {
        $this->timeEntryService = $timeEntryService;
    }

    /**
     * Display a listing of the time entries.
     */
    public function index(Request $request)
    {
        // Get all projects and tasks for the timer widget
        $projects = Project::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $tasks = Task::whereIn('project_id', $projects->pluck('id'))
            ->where('status', '!=', 'completed')
            ->orderBy('title')
            ->get(['id', 'title', 'project_id']);

        return Inertia::render('TimeTracking/Dashboard', [
            'projects' => $projects,
            'tasks' => $tasks,
        ]);
    }

    /**
     * Store a manually created time entry.
     */
    public function store(StoreTimeEntryRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        // Parse date and times to create start and end datetime
        if (isset($data['date']) && isset($data['start_time']) && isset($data['end_time'])) {
            $data['started_at'] = Carbon::parse($data['date'].' '.$data['start_time']);
            $data['ended_at'] = Carbon::parse($data['date'].' '.$data['end_time']);

            // Remove temporary fields
            unset($data['date'], $data['start_time'], $data['end_time']);
        }

        $this->timeEntryService->createTimeEntry($data);

        return redirect()->route('tenant.time.index')
            ->with('success', 'Time entry created successfully.');
    }

    /**
     * Start a new time entry.
     */
    public function start(Request $request)
    {
        $validatedData = $request->validate([
            'task_id' => 'nullable|exists:tasks,id',
            'project_id' => 'nullable|exists:projects,id',
            'description' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();

        $timeEntry = $this->timeEntryService->startTimeEntry(
            $userId,
            $validatedData['task_id'] ?? null,
            $validatedData['project_id'] ?? null,
            $validatedData['description'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json($timeEntry);
        }

        return redirect()->back()
            ->with('success', 'Timer started successfully.');
    }

    /**
     * Stop a running time entry.
     */
    public function stop(TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);

        $this->timeEntryService->stopTimeEntry($timeEntry->id);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()
            ->with('success', 'Timer stopped successfully.');
    }

    /**
     * Get the currently running time entry.
     */
    public function running()
    {
        $userId = Auth::id();
        $timeEntry = $this->timeEntryService->getRunningTimeEntryForUser($userId);

        return response()->json($timeEntry);
    }

    /**
     * Update a time entry.
     */
    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);

        $data = $request->validated();

        // Handle date and time updates if provided
        if (isset($data['date'])) {
            if (isset($data['start_time'])) {
                $startDateTime = Carbon::parse($data['date'].' '.$data['start_time']);
                $data['started_at'] = $startDateTime;
            }

            if (isset($data['end_time'])) {
                $endDateTime = Carbon::parse($data['date'].' '.$data['end_time']);
                $data['ended_at'] = $endDateTime;
            }

            // Remove temporary fields
            unset($data['date'], $data['start_time'], $data['end_time']);
        }

        $this->timeEntryService->updateTimeEntry($timeEntry->id, $data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()
            ->with('success', 'Time entry updated successfully.');
    }

    /**
     * Update a specific field of a time entry.
     */
    public function updateField(Request $request, TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);

        $field = $request->input('field');
        $value = $request->input('value');

        $allowedFields = ['description', 'task_id', 'project_id', 'category_id', 'is_billable', 'tags'];

        if (! in_array($field, $allowedFields)) {
            return response()->json(['error' => 'Invalid field'], 422);
        }

        $data = [$field => $value];
        $updatedEntry = $this->timeEntryService->updateTimeEntry($timeEntry->id, $data);

        return response()->json($updatedEntry);
    }

    /**
     * Delete a time entry.
     */
    public function destroy(TimeEntry $timeEntry)
    {
        $this->authorize('delete', $timeEntry);

        $this->timeEntryService->deleteTimeEntry($timeEntry->id);

        return redirect()->back()
            ->with('success', 'Time entry deleted successfully.');
    }

    /**
     * Get reporting data for time entries.
     */
    public function reportData(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $type = $request->input('type', 'project');
        $groupBy = $request->input('group_by', 'daily');

        $userId = Auth::id();

        // Get raw time entries for the date range
        $entries = $this->timeEntryService->getTimeEntriesForDateRange($userId, $startDate, $endDate);

        // Generate summary data based on requested type
        $summary = [];
        $timeline = [];

        if ($type === 'project') {
            $summary = $this->generateProjectSummary($entries);
            $timeline = $this->generateProjectTimeline($entries, $groupBy);
        } elseif ($type === 'category') {
            $summary = $this->generateCategorySummary($entries);
            $timeline = $this->generateCategoryTimeline($entries, $groupBy);
        } elseif ($type === 'task') {
            $summary = $this->generateTaskSummary($entries);
            $timeline = $this->generateTaskTimeline($entries, $groupBy);
        } elseif ($type === 'billable') {
            $summary = $this->generateBillableSummary($entries);
            $timeline = $this->generateBillableTimeline($entries, $groupBy);
        }

        // Get statistics for the range
        $stats = $this->timeEntryService->getTimeStatistics($userId, $startDate, $endDate);

        return response()->json([
            'summary' => $summary,
            'timeline' => $timeline,
            'stats' => $stats,
        ]);
    }

    /**
     * Generate a summary of time by project.
     */
    private function generateProjectSummary($entries)
    {
        $projectSummary = $entries->groupBy('project_id')
            ->map(function ($group) {
                $project = $group->first()->project;
                $projectName = $project ? $project->name : 'No Project';

                return [
                    'id' => $group->first()->project_id,
                    'name' => $projectName,
                    'value' => $group->sum('duration'),
                    'billable_value' => $group->where('is_billable', true)->sum('duration'),
                    'entry_count' => $group->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();

        return $projectSummary;
    }

    /**
     * Generate a summary of time by category.
     */
    private function generateCategorySummary($entries)
    {
        $categorySummary = $entries->groupBy('category_id')
            ->map(function ($group) {
                $category = $group->first()->category;
                $categoryName = $category ? $category->name : 'Uncategorized';

                return [
                    'id' => $group->first()->category_id,
                    'name' => $categoryName,
                    'value' => $group->sum('duration'),
                    'billable_value' => $group->where('is_billable', true)->sum('duration'),
                    'entry_count' => $group->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();

        return $categorySummary;
    }

    /**
     * Generate a summary of time by task.
     */
    private function generateTaskSummary($entries)
    {
        $taskSummary = $entries->groupBy('task_id')
            ->map(function ($group) {
                $task = $group->first()->task;
                $taskName = $task ? $task->title : 'No Task';

                return [
                    'id' => $group->first()->task_id,
                    'name' => $taskName,
                    'value' => $group->sum('duration'),
                    'billable_value' => $group->where('is_billable', true)->sum('duration'),
                    'entry_count' => $group->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();

        return $taskSummary;
    }

    /**
     * Generate a summary of billable vs non-billable time.
     */
    private function generateBillableSummary($entries)
    {
        $billableSummary = $entries->groupBy('is_billable')
            ->map(function ($group, $key) {
                return [
                    'id' => $key ? 1 : 0,
                    'name' => $key ? 'Billable' : 'Non-Billable',
                    'value' => $group->sum('duration'),
                    'entry_count' => $group->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();

        return $billableSummary;
    }

    /**
     * Generate timeline data grouped by project.
     */
    private function generateProjectTimeline($entries, $groupBy)
    {
        return $this->generateGenericTimeline($entries, $groupBy, 'project_id');
    }

    /**
     * Generate timeline data grouped by category.
     */
    private function generateCategoryTimeline($entries, $groupBy)
    {
        return $this->generateGenericTimeline($entries, $groupBy, 'category_id');
    }

    /**
     * Generate timeline data grouped by task.
     */
    private function generateTaskTimeline($entries, $groupBy)
    {
        return $this->generateGenericTimeline($entries, $groupBy, 'task_id');
    }

    /**
     * Generate timeline data for billable vs non-billable.
     */
    private function generateBillableTimeline($entries, $groupBy)
    {
        $format = $this->getDateFormat($groupBy);
        $groupEntries = $entries->groupBy(function ($entry) use ($format) {
            return Carbon::parse($entry->started_at)->format($format);
        });

        return $groupEntries->map(function ($dayEntries, $date) {
            return [
                'date' => $date,
                'billable' => $dayEntries->where('is_billable', true)->sum('duration'),
                'non_billable' => $dayEntries->where('is_billable', false)->sum('duration'),
                'total' => $dayEntries->sum('duration'),
            ];
        })->values()->all();
    }

    /**
     * Generate generic timeline data.
     */
    private function generateGenericTimeline($entries, $groupBy, $groupField)
    {
        $format = $this->getDateFormat($groupBy);

        // First group by date
        $dateGroups = $entries->groupBy(function ($entry) use ($format) {
            return Carbon::parse($entry->started_at)->format($format);
        });

        // Create a comprehensive result with all dates
        $result = [];

        foreach ($dateGroups as $date => $dateEntries) {
            $dayData = [
                'date' => $date,
                'total' => $dateEntries->sum('duration'),
            ];

            // Group this day's entries by the requested field
            $fieldGroups = $dateEntries->groupBy($groupField);

            foreach ($fieldGroups as $fieldId => $fieldEntries) {
                $fieldName = null;

                if ($groupField === 'project_id' && $fieldEntries->first()->project) {
                    $fieldName = $fieldEntries->first()->project->name;
                } elseif ($groupField === 'category_id' && $fieldEntries->first()->category) {
                    $fieldName = $fieldEntries->first()->category->name;
                } elseif ($groupField === 'task_id' && $fieldEntries->first()->task) {
                    $fieldName = $fieldEntries->first()->task->title;
                }

                if ($fieldName) {
                    $key = 'field_'.($fieldId ?: 'none');
                    $dayData[$key] = [
                        'id' => $fieldId,
                        'name' => $fieldName ?: 'None',
                        'value' => $fieldEntries->sum('duration'),
                    ];
                }
            }

            $result[] = $dayData;
        }

        return $result;
    }

    /**
     * Get date format for grouping by time period.
     */
    private function getDateFormat($groupBy)
    {
        switch ($groupBy) {
            case 'weekly':
                return 'Y-W'; // Year and week number
            case 'monthly':
                return 'Y-m'; // Year and month
            case 'daily':
            default:
                return 'Y-m-d'; // Year, month, day
        }
    }

    /**
     * Duplicate time entries from one day to another.
     */
    public function duplicateDay(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|different:from_date',
        ]);

        $userId = Auth::id();
        $fromDate = Carbon::parse($request->from_date)->startOfDay();
        $toDate = Carbon::parse($request->to_date)->startOfDay();

        // Get entries from the source date
        $sourceEntries = TimeEntry::where('user_id', $userId)
            ->whereDate('started_at', $fromDate)
            ->with(['project', 'task', 'category'])
            ->get();

        if ($sourceEntries->isEmpty()) {
            return response()->json([
                'message' => 'No hay entradas de tiempo para duplicar en la fecha origen',
                'entries' => []
            ], 404);
        }

        $duplicatedEntries = [];

        foreach ($sourceEntries as $sourceEntry) {
            // Calculate the time difference between start and end
            $startTime = Carbon::parse($sourceEntry->started_at);
            $endTime = Carbon::parse($sourceEntry->ended_at);
            $timeDiff = $startTime->diffInSeconds($endTime);

            // Create new entry with the target date
            $newStartTime = $toDate->copy()
                ->setTimeFromTimeString($startTime->format('H:i:s'));
            
            $newEndTime = $newStartTime->copy()->addSeconds($timeDiff);

            $newEntry = TimeEntry::create([
                'user_id' => $userId,
                'project_id' => $sourceEntry->project_id,
                'task_id' => $sourceEntry->task_id,
                'category_id' => $sourceEntry->category_id,
                'description' => $sourceEntry->description,
                'started_at' => $newStartTime,
                'ended_at' => $newEndTime,
                'duration' => $sourceEntry->duration,
                'is_billable' => $sourceEntry->is_billable,
                'tags' => $sourceEntry->tags,
                'created_via' => 'duplicate',
            ]);

            // Load relationships for the response
            $newEntry->load(['project', 'task', 'category']);
            $duplicatedEntries[] = $newEntry;
        }

        return response()->json([
            'message' => 'Entradas duplicadas exitosamente',
            'entries' => $duplicatedEntries,
            'count' => count($duplicatedEntries),
        ]);
    }
}
