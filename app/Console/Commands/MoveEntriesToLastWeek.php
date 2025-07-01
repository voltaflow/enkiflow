<?php

namespace App\Console\Commands;

use App\Models\TimeEntry;
use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MoveEntriesToLastWeek extends Command
{
    protected $signature = 'move:entries-to-last-week {--tenant=}';
    
    protected $description = 'Move all time entries to last week';
    
    public function handle()
    {
        $tenantId = $this->option('tenant');
        
        if (!$tenantId) {
            $this->error('Please provide a tenant ID with --tenant=');
            return Command::FAILURE;
        }
        
        // Initialize tenant
        $space = Space::find($tenantId);
        if (!$space) {
            $this->error("Tenant not found: {$tenantId}");
            return Command::FAILURE;
        }
        
        tenancy()->initialize($space);
        
        // Get all time entries
        $entries = TimeEntry::all();
        
        if ($entries->isEmpty()) {
            $this->info("No time entries found");
            return Command::SUCCESS;
        }
        
        $this->info("Found {$entries->count()} time entries");
        
        // Define last week's date range
        $lastWeekMonday = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekSunday = Carbon::now()->subWeek()->endOfWeek();
        
        $this->info("Will move entries to last week: {$lastWeekMonday->format('Y-m-d')} to {$lastWeekSunday->format('Y-m-d')}");
        
        if ($this->confirm("Do you want to move all time entries to last week?")) {
            $dayIndex = 0;
            foreach ($entries as $entry) {
                // Distribute entries across the week
                $targetDay = $lastWeekMonday->copy()->addDays($dayIndex % 7);
                
                // Keep the same time of day
                $originalTime = Carbon::parse($entry->started_at);
                $newStartedAt = $targetDay->copy()
                    ->setHour($originalTime->hour)
                    ->setMinute($originalTime->minute)
                    ->setSecond($originalTime->second);
                
                // Calculate new ended_at if exists
                $newEndedAt = null;
                if ($entry->ended_at) {
                    $duration = Carbon::parse($entry->started_at)->diffInSeconds(Carbon::parse($entry->ended_at));
                    $newEndedAt = $newStartedAt->copy()->addSeconds($duration);
                }
                
                $entry->update([
                    'started_at' => $newStartedAt,
                    'ended_at' => $newEndedAt,
                ]);
                
                $dayIndex++;
            }
            
            $this->info("Successfully moved {$entries->count()} time entries to last week");
            
            // Show new date range
            $entries = TimeEntry::orderBy('started_at')->get();
            $firstDate = Carbon::parse($entries->first()->started_at)->format('Y-m-d');
            $lastDate = Carbon::parse($entries->last()->started_at)->format('Y-m-d');
            $this->info("New date range: {$firstDate} to {$lastDate}");
        }
        
        tenancy()->end();
        
        return Command::SUCCESS;
    }
}