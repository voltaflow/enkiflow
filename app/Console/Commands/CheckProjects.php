<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Space;
use Illuminate\Console\Command;

class CheckProjects extends Command
{
    protected $signature = 'check:projects {--tenant=}';
    
    protected $description = 'Check projects in tenant';
    
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
        
        $this->info("Checking projects in tenant: {$space->name} (ID: {$space->id})");
        tenancy()->initialize($space);
        
        // Get all projects
        $projects = Project::all();
        
        $this->info("\nTotal projects: " . $projects->count());
        
        if ($projects->isEmpty()) {
            $this->warn("No projects found in this tenant!");
        } else {
            $this->info("\nProjects list:");
            foreach ($projects as $project) {
                $this->line("  ID: {$project->id}");
                $this->line("    Name: {$project->name}");
                $this->line("    Status: {$project->status}");
                $this->line("    User ID: {$project->user_id}");
                $this->line("    Created: {$project->created_at}");
                $this->line("");
            }
        }
        
        // Check if is_demo flag exists
        $demoProjects = Project::where('is_demo', true)->count();
        $this->info("Demo projects: {$demoProjects}");
        
        tenancy()->end();
        
        return Command::SUCCESS;
    }
}