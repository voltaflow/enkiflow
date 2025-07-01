<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Space;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CheckAuthContext extends Command
{
    protected $signature = 'check:auth-context {--tenant=}';
    
    protected $description = 'Check authentication context in tenant';
    
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
        
        $this->info("Initializing tenant: {$space->name} (ID: {$space->id})");
        tenancy()->initialize($space);
        
        // Check auth context
        $this->info("\nAuth context from CLI:");
        $authUser = Auth::user();
        if ($authUser) {
            $this->line("  Authenticated user: {$authUser->email} (ID: {$authUser->id})");
        } else {
            $this->line("  No authenticated user in CLI context");
        }
        
        // List all users in tenant
        $this->info("\nAll users in this tenant:");
        $users = User::all();
        foreach ($users as $user) {
            $this->line("  ID: {$user->id}, Email: {$user->email}, Name: {$user->name}");
        }
        
        // Check which user owns the time entries
        $this->info("\nChecking time entries ownership:");
        $userCounts = \App\Models\TimeEntry::selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->get();
            
        foreach ($userCounts as $entry) {
            $user = User::find($entry->user_id);
            $userName = $user ? $user->email : 'Unknown';
            $this->line("  User ID: {$entry->user_id} ({$userName}): {$entry->count} entries");
        }
        
        // Check who john@continental.com is
        $johnUser = User::where('email', 'john@continental.com')->first();
        if ($johnUser) {
            $this->info("\njohn@continental.com details:");
            $this->line("  ID: {$johnUser->id}");
            $this->line("  Name: {$johnUser->name}");
            $this->line("  Created: {$johnUser->created_at}");
        }
        
        tenancy()->end();
        
        return Command::SUCCESS;
    }
}