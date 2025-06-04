<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:cleanup {tenant_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up tenant databases and records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');

        if ($tenantId) {
            $this->cleanupSpecificTenant($tenantId);
        } else {
            $this->cleanupOrphanedDatabases();
        }

        $this->info('Cleanup completed!');
    }

    protected function cleanupSpecificTenant($tenantId)
    {
        $this->info("Cleaning up tenant: {$tenantId}");

        // Generate possible database names
        $prefix = config('tenancy.database.prefix', 'tenant');
        $suffix = config('tenancy.database.suffix', '');
        
        $possibleDbNames = [
            $prefix . $tenantId . $suffix,
            $prefix . '_' . $tenantId . $suffix,
            $prefix . '-' . $tenantId . $suffix,
            $tenantId, // Just in case
        ];

        // Drop databases
        foreach ($possibleDbNames as $dbName) {
            try {
                DB::statement("DROP DATABASE IF EXISTS \"{$dbName}\"");
                $this->info("Dropped database: {$dbName}");
            } catch (\Exception $e) {
                $this->warn("Could not drop database {$dbName}: " . $e->getMessage());
            }
        }

        // Remove from tenants table
        $deleted = DB::table('tenants')->where('id', $tenantId)->delete();
        if ($deleted) {
            $this->info("Removed tenant record from database");
        }

        // Remove domains
        $deletedDomains = DB::table('domains')->where('tenant_id', $tenantId)->delete();
        if ($deletedDomains) {
            $this->info("Removed {$deletedDomains} domain(s)");
        }

        // Remove from space_users
        $deletedUsers = DB::table('space_users')->where('tenant_id', $tenantId)->delete();
        if ($deletedUsers) {
            $this->info("Removed {$deletedUsers} user association(s)");
        }
    }

    protected function cleanupOrphanedDatabases()
    {
        $this->info('Looking for orphaned tenant databases...');

        // Get all tenant IDs from database
        $tenantIds = DB::table('tenants')->pluck('id')->toArray();

        // Get all databases with tenant prefix
        $prefix = config('tenancy.database.prefix', 'tenant');
        $databases = DB::select("SELECT datname FROM pg_database WHERE datname LIKE '{$prefix}%'");

        foreach ($databases as $db) {
            $dbName = $db->datname;
            
            // Extract tenant ID from database name
            $tenantId = str_replace($prefix, '', $dbName);
            
            // Check if this tenant exists
            if (!in_array($tenantId, $tenantIds)) {
                $this->warn("Found orphaned database: {$dbName}");
                
                if ($this->confirm("Do you want to drop database {$dbName}?")) {
                    try {
                        DB::statement("DROP DATABASE IF EXISTS \"{$dbName}\"");
                        $this->info("Dropped database: {$dbName}");
                    } catch (\Exception $e) {
                        $this->error("Could not drop database: " . $e->getMessage());
                    }
                }
            }
        }
    }
}