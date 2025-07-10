<?php

namespace App\Console\Commands;

use App\Events\ReportDataUpdated;
use App\Services\TimeKpiService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateDashboardKpis extends Command
{
    protected $signature = 'reports:update-kpis';
    protected $description = 'Update KPIs for real-time dashboards';

    protected $kpiService;

    public function __construct(TimeKpiService $kpiService)
    {
        parent::__construct();
        $this->kpiService = $kpiService;
    }

    public function handle()
    {
        $spaces = \App\Models\Space::all();
        
        foreach ($spaces as $space) {
            \Stancl\Tenancy\Facades\Tenancy::initialize($space);
            
            $startDate = now()->startOfMonth();
            $endDate = now();
            
            // Get tenant-level metrics
            $metrics = $this->kpiService->getMetrics($startDate, $endDate);
            
            // Broadcast metrics update
            event(new ReportDataUpdated(
                $space->id,
                'dashboard',
                $metrics
            ));
            
            $this->info("Updated KPIs for tenant: {$space->id}");
        }
        
        return Command::SUCCESS;
    }
}