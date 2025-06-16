<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityAggregationService;
use Illuminate\Console\Command;

class AggregateTimeEntryActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'time-entries:aggregate-activities 
                            {--user= : The ID of the user to aggregate activities for}
                            {--days=1 : Number of days to look back}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate activity data for time entries to optimize reporting';

    /**
     * Execute the console command.
     */
    public function handle(ActivityAggregationService $aggregationService): int
    {
        $userId = $this->option('user');
        $days = (int) $this->option('days');
        
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return Command::FAILURE;
            }
            
            $this->info("Aggregating activities for user: {$user->name}");
            $aggregationService->aggregateActivitiesForDateRange($user->id, $startDate, $endDate);
            $this->info("Activities aggregated successfully for user: {$user->name}");
        } else {
            $this->info("Aggregating activities for all users...");
            
            $users = User::all();
            $bar = $this->output->createProgressBar(count($users));
            $bar->start();
            
            foreach ($users as $user) {
                $aggregationService->aggregateActivitiesForDateRange($user->id, $startDate, $endDate);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("Activities aggregated successfully for all users.");
        }
        
        return Command::SUCCESS;
    }
}