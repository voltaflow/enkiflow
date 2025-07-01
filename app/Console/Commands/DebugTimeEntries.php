<?php

namespace App\Console\Commands;

use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Space;
use Illuminate\Console\Command;

class DebugTimeEntries extends Command
{
    protected $signature = 'debug:time-entries {--tenant=}';
    
    protected $description = 'Debug time entries in a tenant';
    
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
        
        // Get all users in this tenant
        $users = User::all();
        $this->info("Users in tenant {$tenantId}:");
        foreach ($users as $user) {
            $this->line("  ID: {$user->id}, Email: {$user->email}");
        }
        
        // Get time entries grouped by user
        $this->info("\nTime entries by user:");
        $entriesByUser = TimeEntry::selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->get();
            
        foreach ($entriesByUser as $entry) {
            $user = User::find($entry->user_id);
            $userName = $user ? $user->email : 'Unknown';
            $this->line("  User ID: {$entry->user_id} ({$userName}): {$entry->count} entries");
        }
        
        // Show sample entries
        $this->info("\nSample time entries:");
        $sampleEntries = TimeEntry::with(['project', 'task'])
            ->limit(5)
            ->get();
            
        foreach ($sampleEntries as $entry) {
            $this->line("  ID: {$entry->id}");
            $this->line("    User ID: {$entry->user_id}");
            $this->line("    Project: " . ($entry->project ? $entry->project->name : 'None'));
            $this->line("    Task: " . ($entry->task ? $entry->task->title : 'None'));
            $this->line("    Description: {$entry->description}");
            $this->line("    Started: {$entry->started_at}");
            $this->line("");
        }
        
        tenancy()->end();
        
        return Command::SUCCESS;
    }
}