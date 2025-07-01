<?php

namespace App\Console\Commands;

use App\Models\TimeEntry;
use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AdjustTimeEntriesDates extends Command
{
    protected $signature = 'adjust:time-entries-dates {--tenant=} {--to-current-week}';
    
    protected $description = 'Adjust time entries dates to current week';
    
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
        
        if ($this->option('to-current-week')) {
            // Get all time entries
            $entries = TimeEntry::orderBy('started_at')->get();
            
            if ($entries->isEmpty()) {
                $this->info("No time entries found");
                return Command::SUCCESS;
            }
            
            // Get the date range of existing entries
            $firstEntry = $entries->first();
            $lastEntry = $entries->last();
            $originalStart = Carbon::parse($firstEntry->started_at);
            $originalEnd = Carbon::parse($lastEntry->started_at);
            
            $this->info("Found {$entries->count()} time entries");
            $this->info("Original date range: {$originalStart->format('Y-m-d')} to {$originalEnd->format('Y-m-d')}");
            
            // Calculate the difference to move entries to current week
            $currentWeekStart = Carbon::now()->startOfWeek();
            // We want to move the first entry to the start of current week
            $daysDiff = $currentWeekStart->diffInDays($originalStart, false);
            
            $this->info("Will adjust entries by {$daysDiff} days to current week");
            
            if ($this->confirm("Do you want to adjust all time entries to the current week?")) {
                foreach ($entries as $entry) {
                    $newStartedAt = Carbon::parse($entry->started_at)->addDays($daysDiff);
                    $newEndedAt = $entry->ended_at ? Carbon::parse($entry->ended_at)->addDays($daysDiff) : null;
                    
                    $entry->update([
                        'started_at' => $newStartedAt,
                        'ended_at' => $newEndedAt,
                    ]);
                }
                
                $this->info("Successfully adjusted {$entries->count()} time entries to current week");
                
                // Show new date range
                $entries = TimeEntry::orderBy('started_at')->get();
                $newFirst = $entries->first();
                $newLast = $entries->last();
                $this->info("New date range: {$newFirst->started_at} to {$newLast->started_at}");
            }
        }
        
        tenancy()->end();
        
        return Command::SUCCESS;
    }
}