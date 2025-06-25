<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TimeUnifiedController extends Controller
{
    /**
     * Display the unified time tracking interface.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        // Allow passing a week parameter to get entries for different weeks
        $weekParam = $request->get('week');
        if ($weekParam) {
            $weekStart = Carbon::parse($weekParam)->startOfWeek();
        } else {
            $weekStart = Carbon::now()->startOfWeek();
        }
        $weekEnd = $weekStart->copy()->endOfWeek();

        // Get active projects
        $projects = Project::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get tasks for active projects
        $tasks = Task::whereIn('project_id', $projects->pluck('id'))
            ->where('status', '!=', 'completed')
            ->orderBy('title')
            ->get(['id', 'title', 'project_id']);

        // Get today's entries
        $todayEntries = TimeEntry::where('user_id', $user->id)
            ->whereDate('started_at', $today)
            ->with(['project', 'task'])
            ->orderBy('started_at', 'desc')
            ->get();

        // Get week's entries
        $weekEntries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('started_at', [$weekStart, $weekEnd])
            ->with(['project', 'task'])
            ->get();
            
        \Log::info('Week entries query', [
            'user_id' => $user->id,
            'week_start' => $weekStart->format('Y-m-d H:i:s'),
            'week_end' => $weekEnd->format('Y-m-d H:i:s'),
            'count' => $weekEntries->count(),
            'entries' => $weekEntries->take(2)->toArray()
        ]);

        // Determine active view from request
        $activeView = $request->get('view', 'timer');

        return Inertia::render('TimeTracking/TimeUnified', [
            'projects' => $projects,
            'tasks' => $tasks,
            'todayEntries' => $todayEntries,
            'weekEntries' => $weekEntries,
            'activeView' => $activeView,
            'weekStart' => $weekStart->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
        ]);
    }
    
    /**
     * Get week data for AJAX requests
     */
    public function weekData(Request $request)
    {
        $user = Auth::user();
        
        // Parse week parameter
        $weekParam = $request->get('week');
        if ($weekParam) {
            $weekStart = Carbon::parse($weekParam)->startOfWeek();
        } else {
            $weekStart = Carbon::now()->startOfWeek();
        }
        $weekEnd = $weekStart->copy()->endOfWeek();

        // Get week's entries
        $weekEntries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('started_at', [$weekStart, $weekEnd])
            ->with(['project', 'task'])
            ->get();
            
        \Log::info('WeekData method', [
            'user_id' => $user->id,
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'count' => $weekEntries->count(),
            'first_entry' => $weekEntries->first()
        ]);

        // Get virtual rows from session
        $weekKey = $weekStart->format('Y-m-d');
        $sessionKey = "timesheet_virtual_rows_{$user->id}_{$weekKey}";
        $virtualRows = session($sessionKey, []);
        
        // Add virtual entries for rows without time entries
        $virtualEntries = [];
        foreach ($virtualRows as $rowKey) {
            [$projectId, $taskId] = explode('-', $rowKey);
            $taskId = $taskId === '0' ? null : (int)$taskId;
            
            // Check if this combination already has real entries
            $hasRealEntries = $weekEntries->where('project_id', $projectId)
                ->where('task_id', $taskId)
                ->isNotEmpty();
                
            if (!$hasRealEntries) {
                // Create virtual entries for each day of the week
                for ($i = 0; $i < 7; $i++) {
                    $date = $weekStart->copy()->addDays($i);
                    $virtualEntries[] = [
                        'id' => 'virtual_' . $rowKey . '_' . $i,
                        'user_id' => $user->id,
                        'project_id' => (int)$projectId,
                        'task_id' => $taskId,
                        'started_at' => $date->format('Y-m-d 09:00:00'),
                        'ended_at' => $date->format('Y-m-d 09:00:00'),
                        'duration' => 0,
                        'description' => '',
                        'is_billable' => true,
                        'is_virtual' => true,
                        'project' => Project::find($projectId),
                        'task' => $taskId ? Task::find($taskId) : null,
                        'date' => $date->format('Y-m-d'),
                    ];
                }
            }
        }
        
        // Merge real entries with virtual entries
        $allEntries = $weekEntries->toArray();
        $allEntries = array_merge($allEntries, $virtualEntries);
        
        return response()->json([
            'weekStart' => $weekStart->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
            'entries' => $allEntries,
        ]);
    }
    
    /**
     * Get day entries for AJAX requests
     */
    public function dayEntries(Request $request)
    {
        $user = Auth::user();
        
        // Parse date parameter
        $dateParam = $request->get('date');
        if ($dateParam) {
            $date = Carbon::parse($dateParam);
        } else {
            $date = Carbon::today();
        }
        
        // Get day's entries
        $dayEntries = TimeEntry::where('user_id', $user->id)
            ->whereDate('started_at', $date)
            ->with(['project', 'task'])
            ->orderBy('started_at', 'desc')
            ->get();
            
        \Log::info('DayEntries method', [
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'count' => $dayEntries->count()
        ]);

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'entries' => $dayEntries,
        ]);
    }
}