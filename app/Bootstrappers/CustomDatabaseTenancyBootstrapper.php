<?php

namespace App\Bootstrappers;

use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;

class CustomDatabaseTenancyBootstrapper extends DatabaseTenancyBootstrapper
{
    public function bootstrap(Tenant $tenant)
    {
        /** @var TenantWithDatabase $tenant */

        // Skip database existence check during creation process
        // This check is causing issues when TenantUpdated events fire during creation
        if ($this->shouldSkipDatabaseCheck()) {
            \Log::info('CustomDatabaseTenancyBootstrapper: Skipping database check during creation process');
            $this->database->connectToTenant($tenant);
            return;
        }

        // Only check in local environment and if tenant has an ID
        if (app()->environment('local') && $tenant instanceof TenantWithDatabase && $tenant->getTenantKey()) {
            try {
                $database = $tenant->database()->getName();
                $manager = $tenant->database()->manager();
                
                if (!$manager->databaseExists($database)) {
                    throw new TenantDatabaseDoesNotExistException($database);
                }
            } catch (\Exception $e) {
                if ($e instanceof TenantDatabaseDoesNotExistException) {
                    throw $e;
                }
                \Log::warning('Error checking tenant database existence', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->database->connectToTenant($tenant);
    }
    
    /**
     * Determine if we should skip the database check
     * Skip during the creation process to avoid race conditions
     */
    protected function shouldSkipDatabaseCheck(): bool
    {
        // Check if we're in the middle of creating a tenant
        // by looking at the call stack
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        
        foreach ($backtrace as $frame) {
            if (isset($frame['class']) && isset($frame['function'])) {
                // Skip if called from TenantCreator::create
                if ($frame['class'] === 'App\Services\TenantCreator' && $frame['function'] === 'create') {
                    return true;
                }
                
                // Skip if called from Space creation event handlers
                if ($frame['class'] === 'App\Jobs\SyncTenantData' && $frame['function'] === 'handle') {
                    return true;
                }
            }
        }
        
        return false;
    }
}