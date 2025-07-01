<?php

namespace App\Console\Commands;

use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Space;
use Illuminate\Console\Command;

class FixTimeEntriesUser extends Command
{
    protected $signature = 'fix:time-entries-user {--tenant=} {--from-user=} {--to-user=}';
    
    protected $description = 'Fix time entries user assignment';
    
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $fromUserId = $this->option('from-user');
        $toUserId = $this->option('to-user');
        
        if (!$tenantId || !$fromUserId || !$toUserId) {
            $this->error('Please provide all required options: --tenant= --from-user= --to-user=');
            return Command::FAILURE;
        }
        
        // Initialize tenant
        $space = Space::find($tenantId);
        if (!$space) {
            $this->error("Tenant not found: {$tenantId}");
            return Command::FAILURE;
        }
        
        tenancy()->initialize($space);
        
        // Verify users exist
        $fromUser = User::find($fromUserId);
        $toUser = User::find($toUserId);
        
        if (!$fromUser) {
            $this->error("From user not found: {$fromUserId}");
            return Command::FAILURE;
        }
        
        if (!$toUser) {
            $this->error("To user not found: {$toUserId}");
            return Command::FAILURE;
        }
        
        // Count entries to update
        $count = TimeEntry::where('user_id', $fromUserId)->count();
        
        if ($count === 0) {
            $this->info("No time entries found for user {$fromUserId}");
            return Command::SUCCESS;
        }
        
        $this->info("Found {$count} time entries for user {$fromUser->email}");
        
        if ($this->confirm("Do you want to reassign these entries to {$toUser->email}?")) {
            TimeEntry::where('user_id', $fromUserId)
                ->update(['user_id' => $toUserId]);
                
            $this->info("Successfully reassigned {$count} time entries from {$fromUser->email} to {$toUser->email}");
        }
        
        tenancy()->end();
        
        return Command::SUCCESS;
    }
}