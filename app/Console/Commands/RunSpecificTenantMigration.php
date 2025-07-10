<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Space;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class RunSpecificTenantMigration extends Command
{
    protected $signature = 'tenant:run-migration {tenant} {--table=}';
    protected $description = 'Run specific migration for a tenant';

    public function handle()
    {
        $tenantId = $this->argument('tenant');
        $tableName = $this->option('table') ?? 'project_permissions';
        
        $tenant = Space::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant {$tenantId} not found");
            return 1;
        }
        
        tenancy()->initialize($tenant);
        
        if ($tableName === 'project_permissions') {
            if (Schema::hasTable('project_permissions')) {
                $this->info("Table project_permissions already exists");
                return 0;
            }
            
            Schema::create('project_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
                $table->unsignedBigInteger('user_id');
                $table->string('role', 50);
                
                // Explicit permissions
                $table->boolean('can_manage_project')->nullable();
                $table->boolean('can_manage_members')->nullable();
                $table->boolean('can_edit_content')->nullable();
                $table->boolean('can_view_reports')->nullable();
                $table->boolean('can_track_time')->nullable();
                $table->boolean('can_view_budget')->nullable();
                $table->boolean('can_export_data')->nullable();
                $table->boolean('can_delete_content')->nullable();
                $table->boolean('can_manage_integrations')->nullable();
                $table->boolean('can_view_all_time_entries')->nullable();
                
                // Metadata
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                
                $table->timestamps();
                
                // Indexes
                $table->unique(['project_id', 'user_id']);
                $table->index('user_id');
                $table->index('role');
                $table->index('is_active');
                $table->index('expires_at');
            });
            
            $this->info("Table project_permissions created successfully");
        }
        
        return 0;
    }
}