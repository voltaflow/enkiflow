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
use Illuminate\Support\Facades\DB;
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
        \Log::info('TimeEntryController::store - Request data:', $request->all());
        
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        
        \Log::info('TimeEntryController::store - Validated data:', $data);

        // Parse date and times to create start and end datetime
        if (isset($data['date']) && isset($data['start_time']) && isset($data['end_time'])) {
            $data['started_at'] = Carbon::parse($data['date'].' '.$data['start_time']);
            $data['ended_at'] = Carbon::parse($data['date'].' '.$data['end_time']);

            // Remove temporary fields
            unset($data['date'], $data['start_time'], $data['end_time']);
        }
        
        \Log::info('TimeEntryController::store - Data before creating entry:', $data);

        $entry = $this->timeEntryService->createTimeEntry($data);
        
        \Log::info('TimeEntryController::store - Created entry:', [
            'id' => $entry->id,
            'duration' => $entry->duration,
            'started_at' => $entry->started_at,
            'ended_at' => $entry->ended_at,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['time_entry' => $entry]);
        }

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

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Time entry deleted successfully.'
            ]);
        }

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
     * Copy rows (project/task combinations) from the most recent week with entries.
     * This replicates Harvest's "Copy rows from most recent timesheet" functionality.
     */
    public function copyRowsFromPreviousWeek(Request $request)
    {
        $request->validate([
            'target_week_start' => 'required|date',
            'create_entries' => 'boolean', // Option to create entries directly
        ]);

        $userId = Auth::id();
        $targetWeekStart = Carbon::parse($request->target_week_start)->startOfWeek();
        $targetWeekEnd = $targetWeekStart->copy()->endOfWeek();
        $createEntries = $request->input('create_entries', false);

        // Check if the target week already has entries
        $existingEntries = TimeEntry::where('user_id', $userId)
            ->whereBetween('started_at', [$targetWeekStart, $targetWeekEnd])
            ->exists();

        if ($existingEntries) {
            return response()->json([
                'message' => 'La semana actual ya tiene entradas de tiempo',
                'rows' => []
            ], 400);
        }

        // Find the most recent week with entries before the target week
        $mostRecentEntry = TimeEntry::where('user_id', $userId)
            ->where('started_at', '<', $targetWeekStart)
            ->orderBy('started_at', 'desc')
            ->first();

        if (!$mostRecentEntry) {
            return response()->json([
                'message' => 'No hay hojas previas con datos para copiar',
                'rows' => []
            ], 404);
        }

        // Get the week start of the most recent entry
        $recentWeekStart = Carbon::parse($mostRecentEntry->started_at)->startOfWeek();
        $recentWeekEnd = $recentWeekStart->copy()->endOfWeek();

        // Get unique project/task combinations from that week
        // Only get entries with valid project_id (not null)
        $uniqueRows = TimeEntry::where('user_id', $userId)
            ->whereBetween('started_at', [$recentWeekStart, $recentWeekEnd])
            ->whereNotNull('project_id')
            ->select('project_id', 'task_id')
            ->distinct()
            ->with(['project', 'task'])
            ->get();

        \Log::info('Found unique rows from previous week', [
            'count' => $uniqueRows->count(),
            'week' => $recentWeekStart->format('Y-m-d'),
            'rows' => $uniqueRows->toArray()
        ]);

        // If create_entries is true, create the entries directly
        if ($createEntries) {
            $createdEntries = [];
            $weekDates = [];
            
            // Generate dates for the target week
            for ($i = 0; $i < 7; $i++) {
                $weekDates[] = $targetWeekStart->copy()->addDays($i);
            }
            
            DB::transaction(function () use ($uniqueRows, $weekDates, $userId, &$createdEntries) {
                foreach ($uniqueRows as $row) {
                    foreach ($weekDates as $date) {
                        $entry = TimeEntry::create([
                            'user_id' => $userId,
                            'project_id' => $row->project_id,
                            'task_id' => $row->task_id,
                            'started_at' => $date->copy()->setTime(9, 0),
                            'ended_at' => $date->copy()->setTime(9, 0),
                            'duration' => 0,
                            'description' => '',
                            'is_billable' => true,
                            'created_from' => 'manual',
                        ]);
                        $createdEntries[] = $entry;
                    }
                }
            });
            
            return response()->json([
                'message' => 'Filas copiadas exitosamente',
                'created_count' => count($createdEntries),
                'rows_count' => $uniqueRows->count(),
                'from_week' => $recentWeekStart->format('Y-m-d'),
            ]);
        }
        
        // Otherwise, return the rows that would be created (frontend will handle the actual creation)
        $rows = $uniqueRows->map(function ($row) {
            return [
                'project_id' => $row->project_id,
                'task_id' => $row->task_id,
                'project' => $row->project,
                'task' => $row->task,
            ];
        });

        return response()->json([
            'message' => 'Filas encontradas exitosamente',
            'rows' => $rows,
            'from_week' => $recentWeekStart->format('Y-m-d'),
            'count' => $rows->count(),
        ]);
    }

    /**
     * Duplicate time entries from the most recent day with entries to the target date.
     * Only copies project/task combinations, not the actual time.
     */
    public function duplicateDay(Request $request)
    {
        \Log::info('DuplicateDay called', [
            'user_id' => Auth::id(),
            'to_date' => $request->to_date,
            'all_params' => $request->all()
        ]);
        
        $request->validate([
            'to_date' => 'required|date',
        ]);

        $userId = Auth::id();
        $toDate = Carbon::parse($request->to_date)->startOfDay();

        // Check if target date already has entries
        $existingEntries = TimeEntry::where('user_id', $userId)
            ->whereDate('started_at', $toDate)
            ->exists();

        if ($existingEntries) {
            return response()->json([
                'message' => 'El día seleccionado ya tiene entradas de tiempo',
                'entries' => []
            ], 400);
        }

        // Find the most recent day with entries before the target date
        $mostRecentDate = TimeEntry::where('user_id', $userId)
            ->where('started_at', '<', $toDate)
            ->whereNotNull('project_id')
            ->orderBy('started_at', 'desc')
            ->value('started_at');

        if (!$mostRecentDate) {
            return response()->json([
                'message' => 'No se encontraron días anteriores con entradas de tiempo',
                'entries' => []
            ], 404);
        }

        $fromDate = Carbon::parse($mostRecentDate)->startOfDay();
        
        // Get unique project/task combinations from the most recent day
        $sourceEntries = TimeEntry::where('user_id', $userId)
            ->whereDate('started_at', $fromDate)
            ->whereNotNull('project_id')
            ->with(['project', 'task'])
            ->get();
            
        \Log::info('Source entries found', [
            'count' => $sourceEntries->count(),
            'from_date' => $fromDate->format('Y-m-d'),
            'user_id' => $userId,
            'entries' => $sourceEntries->map(function($e) {
                return [
                    'id' => $e->id,
                    'project_id' => $e->project_id,
                    'task_id' => $e->task_id,
                    'started_at' => $e->started_at,
                    'is_billable' => $e->is_billable
                ];
            })
        ]);

        // Get unique project/task combinations
        $seenCombinations = [];
        $duplicatedEntries = [];

        foreach ($sourceEntries as $sourceEntry) {
            try {
                $key = $sourceEntry->project_id . '-' . ($sourceEntry->task_id ?? '0');
                
                // Skip if we've already created this combination
                if (in_array($key, $seenCombinations)) {
                    continue;
                }
                
                $seenCombinations[] = $key;

                // Create new entry with just project/task (no duration)
                $newStartTime = $toDate->copy()->setTime(9, 0, 0);
                $newEndTime = $toDate->copy()->setTime(9, 0, 0);
                
                $entryData = [
                    'user_id' => $userId,
                    'project_id' => $sourceEntry->project_id,
                    'task_id' => $sourceEntry->task_id,
                    'description' => '',
                    'started_at' => $newStartTime,
                    'ended_at' => $newEndTime,
                    'duration' => 0,
                    'is_billable' => $sourceEntry->is_billable ?? true,
                    'is_manual' => true,
                    'created_from' => 'manual',
                ];
                
                \Log::info('Creating duplicate entry', $entryData);

                $newEntry = TimeEntry::create($entryData);
                
                // Load relationships for the response
                $newEntry->load(['project', 'task']);
                $duplicatedEntries[] = $newEntry;
                
            } catch (\Exception $e) {
                \Log::error('Error duplicating entry', [
                    'source_entry_id' => $sourceEntry->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'entry_data' => $entryData
                ]);
                continue;
            }
        }

        if (count($duplicatedEntries) === 0) {
            return response()->json([
                'message' => 'No se pudieron duplicar las entradas. Revisa los logs para más detalles.',
                'entries' => [],
                'count' => 0
            ], 422);
        }
        
        return response()->json([
            'message' => 'Entradas duplicadas exitosamente',
            'entries' => $duplicatedEntries,
            'count' => count($duplicatedEntries),
        ]);
    }
    
    /**
     * Add a new project/task row to the weekly timesheet.
     * Stores in session to display virtual rows until user adds time.
     */
    public function addWeekRow(Request $request)
    {
        $request->validate([
            'week_start' => 'required|date',
            'project_id' => 'required|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
        ]);
        
        $userId = Auth::id();
        $weekStart = Carbon::parse($request->week_start)->startOfWeek();
        $weekKey = $weekStart->format('Y-m-d');
        
        // Check if this project/task combination already exists for this week
        $existingEntry = TimeEntry::where('user_id', $userId)
            ->where('project_id', $request->project_id)
            ->where('task_id', $request->task_id)
            ->whereBetween('started_at', [$weekStart, $weekStart->copy()->endOfWeek()])
            ->exists();
            
        if ($existingEntry) {
            return response()->json([
                'message' => 'Esta combinación de proyecto/tarea ya existe en la semana',
            ], 400);
        }
        
        // Store virtual rows in session
        $sessionKey = "timesheet_virtual_rows_{$userId}_{$weekKey}";
        $virtualRows = session($sessionKey, []);
        
        // Check if already in virtual rows
        $rowKey = $request->project_id . '-' . ($request->task_id ?? '0');
        if (in_array($rowKey, $virtualRows)) {
            return response()->json([
                'message' => 'Esta combinación de proyecto/tarea ya existe en la semana',
            ], 400);
        }
        
        // Add to virtual rows
        $virtualRows[] = $rowKey;
        session([$sessionKey => $virtualRows]);
        
        // Load project and task for response
        $project = Project::find($request->project_id);
        $task = $request->task_id ? Task::find($request->task_id) : null;
        
        return response()->json([
            'message' => 'Fila agregada exitosamente',
            'project_id' => $request->project_id,
            'task_id' => $request->task_id,
            'project' => $project,
            'task' => $task,
        ]);
    }
}
