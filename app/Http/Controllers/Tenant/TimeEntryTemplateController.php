<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TimeEntryTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimeEntryTemplateController extends Controller
{
    /**
     * Display a listing of the templates.
     */
    public function index()
    {
        $templates = TimeEntryTemplate::forUser(Auth::id())
            ->with(['project', 'task', 'category'])
            ->byUsageFrequency()
            ->paginate(20);

        return response()->json([
            'templates' => $templates,
        ]);
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'category_id' => 'nullable|exists:time_categories,id',
            'default_hours' => 'required|numeric|min:0.25|max:24',
            'is_billable' => 'boolean',
        ]);

        $template = TimeEntryTemplate::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'template' => $template->load(['project', 'task', 'category']),
            'message' => 'Template created successfully',
        ], 201);
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, TimeEntryTemplate $template)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'category_id' => 'nullable|exists:time_categories,id',
            'default_hours' => 'sometimes|required|numeric|min:0.25|max:24',
            'is_billable' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return response()->json([
            'template' => $template->load(['project', 'task', 'category']),
            'message' => 'Template updated successfully',
        ]);
    }

    /**
     * Remove the specified template.
     */
    public function destroy(TimeEntryTemplate $template)
    {
        $this->authorize('delete', $template);

        $template->delete();

        return response()->json([
            'message' => 'Template deleted successfully',
        ]);
    }

    /**
     * Create a time entry from a template.
     */
    public function createEntry(Request $request, TimeEntryTemplate $template)
    {
        $this->authorize('use', $template);

        $validated = $request->validate([
            'started_at' => 'nullable|date',
            'duration_hours' => 'nullable|numeric|min:0.25|max:24',
            'description' => 'nullable|string',
            'is_billable' => 'nullable|boolean',
        ]);

        $overrides = [];

        if (isset($validated['started_at'])) {
            $overrides['started_at'] = $validated['started_at'];
            if (isset($validated['duration_hours'])) {
                $overrides['ended_at'] = \Carbon\Carbon::parse($validated['started_at'])
                    ->addHours($validated['duration_hours']);
                $overrides['duration'] = $validated['duration_hours'] * 3600;
            }
        }

        if (isset($validated['description'])) {
            $overrides['description'] = $validated['description'];
        }

        if (isset($validated['is_billable'])) {
            $overrides['is_billable'] = $validated['is_billable'];
        }

        $timeEntry = $template->createTimeEntry($overrides);

        return response()->json([
            'time_entry' => $timeEntry->load(['project', 'task', 'category']),
            'message' => 'Time entry created from template',
        ]);
    }

    /**
     * Get template suggestions based on context.
     */
    public function suggestions(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);

        $suggestions = TimeEntryTemplate::getSuggestions(
            Auth::id(),
            $validated['project_id'] ?? null,
            $validated['limit'] ?? 5
        );

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Bulk create time entries from multiple templates.
     */
    public function bulkCreateEntries(Request $request)
    {
        $validated = $request->validate([
            'template_ids' => 'required|array',
            'template_ids.*' => 'exists:time_entry_templates,id',
            'date' => 'required|date',
        ]);

        $templates = TimeEntryTemplate::whereIn('id', $validated['template_ids'])
            ->forUser(Auth::id())
            ->get();

        $entries = [];
        $date = \Carbon\Carbon::parse($validated['date']);

        foreach ($templates as $template) {
            $startTime = $date->copy()->addHours(9 + count($entries) * 2); // Stagger start times

            $entries[] = $template->createTimeEntry([
                'started_at' => $startTime,
                'ended_at' => $startTime->copy()->addHours($template->default_hours),
            ]);
        }

        return response()->json([
            'entries' => $entries,
            'message' => count($entries).' time entries created from templates',
        ]);
    }
}
