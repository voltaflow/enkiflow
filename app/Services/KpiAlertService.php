<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\KpiAlertNotification;
use Carbon\Carbon;

class KpiAlertService
{
    protected $kpiService;
    
    public function __construct(TimeKpiService $kpiService)
    {
        $this->kpiService = $kpiService;
    }
    
    /**
     * Check KPIs against thresholds and send alerts if needed
     */
    public function checkAndSendAlerts(): void
    {
        $startDate = now()->startOfMonth();
        $endDate = now();
        
        // Check tenant-level KPIs
        $this->checkTenantKpis($startDate, $endDate);
        
        // Check project-level KPIs
        $this->checkProjectKpis($startDate, $endDate);
        
        // Check user-level KPIs
        $this->checkUserKpis($startDate, $endDate);
    }
    
    /**
     * Check tenant-level KPIs
     */
    protected function checkTenantKpis(Carbon $startDate, Carbon $endDate): void
    {
        $metrics = $this->kpiService->getMetrics($startDate, $endDate);
        
        // Check billable utilization
        if ($metrics['billable_utilization'] < $metrics['thresholds']['billable_utilization']['critical']) {
            // Alert tenant admin
            $admin = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->first();
            
            if ($admin) {
                $admin->notify(new KpiAlertNotification(
                    'Critical: Low Billable Utilization',
                    "Billable utilization is at {$metrics['billable_utilization']}%, below critical threshold of {$metrics['thresholds']['billable_utilization']['critical']}%.",
                    'billable_utilization',
                    $metrics['billable_utilization']
                ));
            }
        }
    }
    
    /**
     * Check project-level KPIs
     */
    protected function checkProjectKpis(Carbon $startDate, Carbon $endDate): void
    {
        // Get active projects
        $projects = \App\Models\Project::where('status', 'active')->get();
        
        foreach ($projects as $project) {
            $metrics = $this->kpiService->getMetrics($startDate, $endDate, 'project', $project->id);
            
            // Check budget burn rate
            if ($metrics['budget_burn_rate'] && $metrics['budget_burn_rate'] > $metrics['thresholds']['budget_burn_rate']['warning']) {
                // Alert project manager
                $projectManager = $project->user; // Assuming project owner is the manager
                
                if ($projectManager) {
                    $projectManager->notify(new KpiAlertNotification(
                        "Warning: Budget Burn Rate for {$project->name}",
                        "Budget burn rate is at {$metrics['budget_burn_rate']}%, above warning threshold of {$metrics['thresholds']['budget_burn_rate']['warning']}%.",
                        'budget_burn_rate',
                        $metrics['budget_burn_rate']
                    ));
                }
            }
        }
    }
    
    /**
     * Check user-level KPIs
     */
    protected function checkUserKpis(Carbon $startDate, Carbon $endDate): void
    {
        // Get active users with space_user relationships
        $spaceUsers = \DB::table('space_users')
            ->where('space_id', tenant('id'))
            ->get();
        
        foreach ($spaceUsers as $spaceUser) {
            $user = User::find($spaceUser->user_id);
            if (!$user) continue;
            
            $metrics = $this->kpiService->getMetrics($startDate, $endDate, 'user', $user->id);
            
            // Check overtime hours
            if ($metrics['overtime_hours'] > 10) { // Threshold for overtime
                // Alert user and their manager
                $user->notify(new KpiAlertNotification(
                    "Overtime Alert",
                    "You have logged {$metrics['overtime_hours']} overtime hours this month.",
                    'overtime_hours',
                    $metrics['overtime_hours']
                ));
                
                // Also notify admin
                $admin = User::whereHas('roles', function ($query) {
                    $query->where('name', 'admin');
                })->first();
                
                if ($admin && $admin->id !== $user->id) {
                    $admin->notify(new KpiAlertNotification(
                        "Team Member Overtime: {$user->name}",
                        "{$user->name} has logged {$metrics['overtime_hours']} overtime hours this month.",
                        'overtime_hours',
                        $metrics['overtime_hours']
                    ));
                }
            }
            
            // Check capacity utilization
            if ($metrics['capacity_utilization'] > 90) { // Over 90% utilization is concerning
                $user->notify(new KpiAlertNotification(
                    "High Capacity Utilization",
                    "Your capacity utilization is at {$metrics['capacity_utilization']}%.",
                    'capacity_utilization',
                    $metrics['capacity_utilization']
                ));
            }
        }
    }
}