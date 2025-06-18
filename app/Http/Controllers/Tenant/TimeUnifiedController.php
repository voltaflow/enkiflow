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
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

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

        // Determine active view from request
        $activeView = $request->get('view', 'timer');

        return Inertia::render('TimeTracking/TimeUnified', [
            'projects' => $projects,
            'tasks' => $tasks,
            'todayEntries' => $todayEntries,
            'weekEntries' => $weekEntries,
            'activeView' => $activeView,
        ]);
    }
}