<?php

namespace App\Console\Commands;

use App\Models\Space;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckProjectUserTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:project-user-table {tenant?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if project_user table exists in tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant');
        
        if ($tenantId) {
            // Check specific tenant
            $space = Space::find($tenantId);
            if (!$space) {
                $this->error("Tenant {$tenantId} not found");
                return 1;
            }
            
            $this->checkTenant($space);
        } else {
            // Check all tenants
            $spaces = Space::all();
            foreach ($spaces as $space) {
                $this->checkTenant($space);
            }
        }
        
        return 0;
    }
    
    private function checkTenant(Space $space)
    {
        $this->info("Checking tenant: {$space->name} (ID: {$space->id})");
        
        try {
            tenancy()->initialize($space);
            
            // Check if table exists
            $exists = DB::connection('tenant')->getSchemaBuilder()->hasTable('project_user');
            
            if ($exists) {
                $this->info("✓ project_user table exists");
                
                // Get count
                $count = DB::connection('tenant')->table('project_user')->count();
                $this->info("  Records: {$count}");
                
                // Get sample data
                if ($count > 0) {
                    $sample = DB::connection('tenant')->table('project_user')->first();
                    $this->info("  Sample: Project {$sample->project_id} - User {$sample->user_id} - Role: {$sample->role}");
                }
            } else {
                $this->error("✗ project_user table does NOT exist");
                
                // Create the table
                if ($this->confirm("Would you like to create the table?")) {
                    $sql = file_get_contents(base_path('create_project_user_table.sql'));
                    DB::connection('tenant')->unprepared($sql);
                    $this->info("✓ Table created successfully");
                }
            }
            
            tenancy()->end();
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
        
        $this->newLine();
    }
}