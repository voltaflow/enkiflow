<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TimeEntryExportController extends Controller
{
    /**
     * Export time entries as CSV
     */
    public function exportCsv(Request $request): Response
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'project_id' => 'nullable|exists:projects,id',
            'format' => 'nullable|in:detailed,summary',
            'grouping' => 'nullable|in:none,project,task,day,week,month',
        ]);

        $query = TimeEntry::query()
            ->where('user_id', Auth::id())
            ->with(['project', 'task', 'category']);

        // Apply date filters
        if ($request->has('start_date')) {
            $query->where('started_at', '>=', Carbon::parse($request->start_date)->startOfDay());
        }
        if ($request->has('end_date')) {
            $query->where('started_at', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        // Apply project filter
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $entries = $query->orderBy('started_at', 'desc')->get();

        // Generate CSV based on format
        $format = $request->get('format', 'detailed');
        $grouping = $request->get('grouping', 'none');

        if ($format === 'summary') {
            $csv = $this->generateSummaryCsv($entries, $grouping);
        } else {
            $csv = $this->generateDetailedCsv($entries);
        }

        $filename = 'time-entries-'.now()->format('Y-m-d').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Export time entries as PDF
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'project_id' => 'nullable|exists:projects,id',
            'format' => 'nullable|in:timesheet,invoice,report',
        ]);

        // TODO: Implement PDF export using a package like DomPDF or TCPDF
        // For now, return a placeholder response
        return response()->json([
            'message' => 'PDF export is not yet implemented',
        ], 501);
    }

    /**
     * Generate detailed CSV with one row per time entry
     */
    private function generateDetailedCsv($entries): string
    {
        $headers = [
            'Date',
            'Start Time',
            'End Time',
            'Duration (hours)',
            'Project',
            'Task',
            'Category',
            'Description',
            'Billable',
            'Billed',
            'Created Via',
        ];

        $rows = [];
        $rows[] = implode(',', $headers);

        foreach ($entries as $entry) {
            $row = [
                $entry->started_at->format('Y-m-d'),
                $entry->started_at->format('H:i'),
                $entry->ended_at ? $entry->ended_at->format('H:i') : 'Running',
                number_format($entry->duration / 3600, 2),
                $this->escapeCsvValue($entry->project?->name ?? 'No Project'),
                $this->escapeCsvValue($entry->task?->name ?? 'No Task'),
                $this->escapeCsvValue($entry->category?->name ?? 'No Category'),
                $this->escapeCsvValue($entry->description ?? ''),
                $entry->is_billable ? 'Yes' : 'No',
                $entry->is_billed ? 'Yes' : 'No',
                $entry->created_via,
            ];

            $rows[] = implode(',', $row);
        }

        // Add totals row
        $totalHours = $entries->sum('duration') / 3600;
        $billableHours = $entries->where('is_billable', true)->sum('duration') / 3600;

        $rows[] = ''; // Empty row
        $rows[] = implode(',', [
            'TOTAL',
            '',
            '',
            number_format($totalHours, 2),
            '',
            '',
            '',
            'Billable: '.number_format($billableHours, 2).' hours',
            '',
            '',
            '',
        ]);

        return implode("\n", $rows);
    }

    /**
     * Generate summary CSV grouped by specified criteria
     */
    private function generateSummaryCsv($entries, string $grouping): string
    {
        $grouped = $this->groupEntries($entries, $grouping);
        $headers = $this->getSummaryHeaders($grouping);

        $rows = [];
        $rows[] = implode(',', $headers);

        foreach ($grouped as $key => $group) {
            $totalHours = $group->sum('duration') / 3600;
            $billableHours = $group->where('is_billable', true)->sum('duration') / 3600;

            $row = match ($grouping) {
                'project' => [
                    $this->escapeCsvValue($key),
                    number_format($totalHours, 2),
                    number_format($billableHours, 2),
                    $group->count(),
                ],
                'task' => [
                    $this->escapeCsvValue($group->first()->project?->name ?? 'No Project'),
                    $this->escapeCsvValue($key),
                    number_format($totalHours, 2),
                    number_format($billableHours, 2),
                    $group->count(),
                ],
                'day', 'week', 'month' => [
                    $key,
                    number_format($totalHours, 2),
                    number_format($billableHours, 2),
                    $group->count(),
                ],
                default => [
                    number_format($totalHours, 2),
                    number_format($billableHours, 2),
                    $group->count(),
                ],
            };

            $rows[] = implode(',', $row);
        }

        // Add grand totals
        $totalHours = $entries->sum('duration') / 3600;
        $billableHours = $entries->where('is_billable', true)->sum('duration') / 3600;

        $rows[] = ''; // Empty row
        $totalRow = array_fill(0, count($headers) - 3, 'TOTAL');
        $totalRow[] = number_format($totalHours, 2);
        $totalRow[] = number_format($billableHours, 2);
        $totalRow[] = $entries->count();

        $rows[] = implode(',', $totalRow);

        return implode("\n", $rows);
    }

    /**
     * Group entries by specified criteria
     */
    private function groupEntries($entries, string $grouping)
    {
        return match ($grouping) {
            'project' => $entries->groupBy(fn ($entry) => $entry->project?->name ?? 'No Project'),
            'task' => $entries->groupBy(fn ($entry) => $entry->task?->name ?? 'No Task'),
            'day' => $entries->groupBy(fn ($entry) => $entry->started_at->format('Y-m-d')),
            'week' => $entries->groupBy(fn ($entry) => $entry->started_at->startOfWeek()->format('Y-m-d')),
            'month' => $entries->groupBy(fn ($entry) => $entry->started_at->format('Y-m')),
            default => collect(['All Entries' => $entries]),
        };
    }

    /**
     * Get headers for summary CSV based on grouping
     */
    private function getSummaryHeaders(string $grouping): array
    {
        return match ($grouping) {
            'project' => ['Project', 'Total Hours', 'Billable Hours', 'Entry Count'],
            'task' => ['Project', 'Task', 'Total Hours', 'Billable Hours', 'Entry Count'],
            'day' => ['Date', 'Total Hours', 'Billable Hours', 'Entry Count'],
            'week' => ['Week Starting', 'Total Hours', 'Billable Hours', 'Entry Count'],
            'month' => ['Month', 'Total Hours', 'Billable Hours', 'Entry Count'],
            default => ['Total Hours', 'Billable Hours', 'Entry Count'],
        };
    }

    /**
     * Escape CSV value to handle commas and quotes
     */
    private function escapeCsvValue(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }

    /**
     * Get export templates for quick access
     */
    public function getTemplates()
    {
        return response()->json([
            'templates' => [
                [
                    'id' => 'weekly-detailed',
                    'name' => 'Weekly Detailed Report',
                    'description' => 'Detailed time entries for the current week',
                    'params' => [
                        'start_date' => now()->startOfWeek()->format('Y-m-d'),
                        'end_date' => now()->endOfWeek()->format('Y-m-d'),
                        'format' => 'detailed',
                    ],
                ],
                [
                    'id' => 'monthly-summary',
                    'name' => 'Monthly Summary by Project',
                    'description' => 'Time summary grouped by project for the current month',
                    'params' => [
                        'start_date' => now()->startOfMonth()->format('Y-m-d'),
                        'end_date' => now()->endOfMonth()->format('Y-m-d'),
                        'format' => 'summary',
                        'grouping' => 'project',
                    ],
                ],
                [
                    'id' => 'billable-hours',
                    'name' => 'Billable Hours Report',
                    'description' => 'All billable hours for the current month',
                    'params' => [
                        'start_date' => now()->startOfMonth()->format('Y-m-d'),
                        'end_date' => now()->endOfMonth()->format('Y-m-d'),
                        'format' => 'detailed',
                        'billable_only' => true,
                    ],
                ],
            ],
        ]);
    }
}
