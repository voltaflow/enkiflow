<?php

namespace App\Jobs;

use App\DataTransferObjects\TimeReportDTO;
use App\Models\User;
use App\Notifications\ReportGeneratedNotification;
use App\Services\TimeReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GenerateComplexReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour
    
    public function __construct(
        protected string $jobId,
        protected string $reportType,
        protected Carbon $startDate,
        protected Carbon $endDate,
        protected array $filters,
        protected string $exportFormat,
        protected int $userId
    ) {
    }
    
    public function handle(TimeReportService $reportService): void
    {
        // Update status to processing
        Cache::put("report_job:{$this->jobId}:status", 'processing', now()->addDay());
        Cache::put("report_job:{$this->jobId}:progress", 10, now()->addDay());
        
        try {
            // Generate the appropriate report based on type
            $report = match($this->reportType) {
                'detailed' => $reportService->getReportByDateRange($this->startDate, $this->endDate, $this->filters),
                'summary' => $this->generateSummaryReport($reportService),
                'custom' => $this->generateCustomReport($reportService),
                default => throw new \Exception('Invalid report type'),
            };
            
            Cache::put("report_job:{$this->jobId}:progress", 50, now()->addDay());
            
            // Export the report to the requested format
            $filePath = $this->exportReport($report);
            
            // Update cache with completed status and file path
            Cache::put("report_job:{$this->jobId}:status", 'completed', now()->addDay());
            Cache::put("report_job:{$this->jobId}:file_path", $filePath, now()->addDay());
            Cache::put("report_job:{$this->jobId}:progress", 100, now()->addDay());
            
            // Notify the user
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new ReportGeneratedNotification($this->jobId, $this->reportType));
            }
        } catch (\Exception $e) {
            // Update cache with error status
            Cache::put("report_job:{$this->jobId}:status", 'failed', now()->addDay());
            Cache::put("report_job:{$this->jobId}:error", $e->getMessage(), now()->addDay());
            
            // Log the error
            \Log::error("Report generation failed: {$e->getMessage()}", [
                'job_id' => $this->jobId,
                'report_type' => $this->reportType,
                'exception' => $e,
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Generate a summary report with high-level aggregations
     */
    protected function generateSummaryReport(TimeReportService $reportService): TimeReportDTO
    {
        // Implementation for summary report generation
        // This would use the service but with different aggregation logic
        
        return $reportService->getReportByDateRange($this->startDate, $this->endDate, $this->filters);
    }
    
    /**
     * Generate a custom report based on specific filters
     */
    protected function generateCustomReport(TimeReportService $reportService): TimeReportDTO
    {
        // Implementation for custom report generation
        // This would use more complex queries and transformations
        
        return $reportService->getReportByDateRange($this->startDate, $this->endDate, $this->filters);
    }
    
    /**
     * Export the report to the requested format
     */
    protected function exportReport(TimeReportDTO $report): string
    {
        $tenantId = tenant('id');
        $filename = "reports/{$tenantId}/{$this->jobId}.{$this->exportFormat}";
        
        // Export logic based on format
        switch ($this->exportFormat) {
            case 'csv':
                $this->exportToCsv($report, $filename);
                break;
            case 'pdf':
                $this->exportToPdf($report, $filename);
                break;
            case 'xlsx':
                $this->exportToExcel($report, $filename);
                break;
        }
        
        return $filename;
    }
    
    /**
     * Export to CSV format
     */
    protected function exportToCsv(TimeReportDTO $report, string $filename): void
    {
        $headers = ['Date', 'Project', 'User', 'Duration', 'Billable Duration', 'Amount'];
        $rows = [];
        
        foreach ($report->entries as $entry) {
            $rows[] = [
                $entry->entry_date ?? $entry->date,
                $entry->project->name ?? 'N/A',
                $entry->user->name ?? 'N/A',
                $entry->total_duration ?? $entry->duration,
                $entry->billable_duration ?? 0,
                $entry->total_amount ?? 0,
            ];
        }
        
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        
        foreach ($rows as $row) {
            fputcsv($csv, $row);
        }
        
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        
        Storage::put($filename, $content);
    }
    
    /**
     * Export to PDF format
     */
    protected function exportToPdf(TimeReportDTO $report, string $filename): void
    {
        // Implementation for PDF export
        // This would typically use a library like DomPDF or Snappy
        
        // Placeholder implementation
        $html = view('reports.pdf', ['report' => $report])->render();
        // @todo: Implement PDF generation with a library like DomPDF
        // $pdf = \PDF::loadHTML($html);
        // Storage::put($filename, $pdf->output());
        
        // For now, just store the HTML as a placeholder
        Storage::put($filename, $html);
    }
    
    /**
     * Export to Excel format
     */
    protected function exportToExcel(TimeReportDTO $report, string $filename): void
    {
        // Implementation for Excel export
        // This would typically use a library like PhpSpreadsheet or Laravel Excel
        
        // @todo: Implement Excel generation with PhpSpreadsheet
        // For now, just store as CSV as a placeholder
        $this->exportToCsv($report, $filename);
    }
    
    /**
     * Get the queue this job should be dispatched to
     */
    public function queue(): string
    {
        return "reports-" . tenant('id');
    }
}