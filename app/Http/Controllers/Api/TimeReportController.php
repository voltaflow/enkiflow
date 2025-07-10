<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimeReport\DateRangeReportRequest;
use App\Http\Requests\TimeReport\ProjectReportRequest;
use App\Http\Requests\TimeReport\UserReportRequest;
use App\Http\Requests\TimeReport\BillingReportRequest;
use App\Jobs\GenerateComplexReportJob;
use App\Models\Project;
use App\Models\User;
use App\Services\TimeReportService;
use App\Services\TimeKpiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeReportController extends Controller
{
    public function __construct(
        protected TimeReportService $reportService,
        protected TimeKpiService $kpiService
    ) {
        // Middleware is handled at route level
    }
    
    /**
     * Get report by date range
     */
    public function byDateRange(DateRangeReportRequest $request): JsonResponse
    {
        // TODO: Implement proper authorization
        // $this->authorize('view-reports');
        
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $filters = $request->validated('filters', []);
        
        $report = $this->reportService->getReportByDateRange($startDate, $endDate, $filters);
        
        return response()->json($report->toApiResponse());
    }
    
    /**
     * Get report by project
     */
    public function byProject(ProjectReportRequest $request, Project $project): JsonResponse
    {
        // TODO: Implement proper authorization
        // $this->authorize('view-project-reports', $project);
        
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : null;
        
        $report = $this->reportService->getReportByProject($project, $startDate, $endDate);
        
        return response()->json($report->toApiResponse());
    }
    
    /**
     * Get user productivity report
     */
    public function byUser(UserReportRequest $request, User $user): JsonResponse
    {
        // TODO: Implement proper authorization
        // $this->authorize('view-user-reports', $user);
        
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : null;
        
        $report = $this->reportService->getUserProductivityReport($user, $startDate, $endDate);
        
        return response()->json($report->toApiResponse());
    }
    
    /**
     * Get billing report
     */
    public function billing(BillingReportRequest $request): JsonResponse
    {
        // TODO: Implement proper authorization
        // $this->authorize('view-billing-reports');
        
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $filters = $request->validated('filters', []);
        
        $report = $this->reportService->getBillingReport($startDate, $endDate, $filters);
        
        return response()->json($report->toApiResponse());
    }
    
    /**
     * Get metrics report
     */
    public function metrics(Request $request): JsonResponse
    {
        // TODO: Implement proper authorization
        // $this->authorize('view-reports');
        
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'scope' => 'sometimes|string|in:user,project,tenant',
            'scope_id' => 'required_if:scope,user,project|integer',
        ]);
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $scope = $validated['scope'] ?? 'tenant';
        $scopeId = $validated['scope_id'] ?? null;
        
        $metrics = $this->kpiService->getMetrics($startDate, $endDate, $scope, $scopeId);
        
        return response()->json($metrics);
    }
    
    /**
     * Get summary report view
     */
    public function summary(Request $request): JsonResponse
    {
        // TODO: Implement proper authorization
        // $this->authorize('view-reports');
        
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'sometimes|string|in:project,user,client,date',
        ]);
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $groupBy = $validated['group_by'] ?? 'project';
        
        $report = $this->reportService->getSummaryReport($startDate, $endDate, $groupBy);
        
        return response()->json($report->toApiResponse());
    }

    /**
     * Get weekly report view
     */
    public function weekly(Request $request): JsonResponse
    {
        // TODO: Implement proper authorization
        // $this->authorize('view-reports');
        
        $validated = $request->validate([
            'week' => 'sometimes|integer',
            'year' => 'sometimes|integer',
        ]);
        
        $week = $validated['week'] ?? now()->week;
        $year = $validated['year'] ?? now()->year;
        
        $startDate = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();
        
        $report = $this->reportService->getWeeklyReport($startDate, $endDate);
        
        return response()->json($report->toApiResponse());
    }
    
    /**
     * Request generation of a complex report (async)
     */
    public function requestComplexReport(Request $request): JsonResponse
    {
        // TODO: Implement proper authorization
        // $this->authorize('generate-complex-reports');
        
        $validated = $request->validate([
            'report_type' => 'required|string|in:detailed,summary,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'filters' => 'sometimes|array',
            'export_format' => 'sometimes|string|in:csv,pdf,xlsx',
        ]);
        
        $jobId = (string) \Str::uuid();
        
        GenerateComplexReportJob::dispatch(
            $jobId,
            $validated['report_type'],
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date']),
            $validated['filters'] ?? [],
            $validated['export_format'] ?? 'csv',
            auth()->id()
        );
        
        return response()->json([
            'message' => 'Report generation has been queued',
            'job_id' => $jobId,
        ]);
    }
    
    /**
     * Check status of a complex report job
     */
    public function checkReportStatus(string $jobId): JsonResponse
    {
        // TODO: Implement proper authorization
        // $this->authorize('generate-complex-reports');
        
        // Check job status in database or cache
        $status = \Cache::get("report_job:{$jobId}:status", 'pending');
        $progress = \Cache::get("report_job:{$jobId}:progress", 0);
        
        return response()->json([
            'job_id' => $jobId,
            'status' => $status,
            'progress' => $progress,
            'download_url' => $status === 'completed' 
                ? route('api.reports.download', ['jobId' => $jobId]) 
                : null,
        ]);
    }
    
    /**
     * Download a generated report
     */
    public function downloadReport(string $jobId): JsonResponse
    {
        // TODO: Implement proper authorization
        // $this->authorize('generate-complex-reports');
        
        $filePath = \Cache::get("report_job:{$jobId}:file_path");
        
        if (!$filePath || !\Storage::exists($filePath)) {
            return response()->json([
                'message' => 'Report not found or has expired',
            ], 404);
        }
        
        return response()->json([
            'download_url' => \Storage::temporaryUrl($filePath, now()->addMinutes(30)),
        ]);
    }
}