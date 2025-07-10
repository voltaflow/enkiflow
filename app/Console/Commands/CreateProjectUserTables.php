<?php

namespace App\Console\Commands;

use App\Models\Space;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateProjectUserTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:project-user-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create project_user table in all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sql = file_get_contents(base_path('create_project_user_table.sql'));
        $spaces = Space::all();
        $created = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($spaces as $space) {
            try {
                tenancy()->initialize($space);
                
                // Check if table exists
                $exists = DB::connection('tenant')->getSchemaBuilder()->hasTable('project_user');
                
                if (!$exists) {
                    DB::connection('tenant')->unprepared($sql);
                    $this->info("✓ Created table for: {$space->name}");
                    $created++;
                } else {
                    $this->line("- Table already exists for: {$space->name}");
                    $skipped++;
                }
                
                tenancy()->end();
            } catch (\Exception $e) {
                $this->error("✗ Failed for {$space->name}: " . $e->getMessage());
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("Summary:");
        $this->info("  Created: {$created}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Errors: {$errors}");
        
        return 0;
    }
}