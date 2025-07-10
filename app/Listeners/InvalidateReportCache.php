<?php

namespace App\Listeners;

use App\Events\TimeEntryCreated;
use App\Events\TimeEntryDeleted;
use App\Events\TimeEntryUpdated;
use App\Services\TimeKpiService;
use App\Services\TimeReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class InvalidateReportCache implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $timeEntry = $event->timeEntry;
        $reportService = app(TimeReportService::class);
        $kpiService = app(TimeKpiService::class);
        
        // Invalidar caché por proyecto
        if ($timeEntry->project_id) {
            $reportService->invalidateProjectCache($timeEntry->project_id);
        }
        
        // Invalidar caché por usuario
        $reportService->invalidateUserCache($timeEntry->user_id);
        
        // Invalidar caché de KPIs
        $kpiService->invalidateKpiCache();
        
        // Opcionalmente, invalidar toda la caché del tenant para reportes globales
        if ($event instanceof TimeEntryCreated || $event instanceof TimeEntryDeleted) {
            $reportService->invalidateTenantCache(tenant('id'));
        }
    }
}