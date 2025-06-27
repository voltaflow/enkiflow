<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\TenantCreated;
use Illuminate\Support\Facades\Log;

class CreateTenantDatabase
{
    public function handle(TenantCreated $event): void
    {
        $tenant = $event->tenant;
        
        Log::info('CreateTenantDatabase: Starting database creation', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'event_class' => get_class($event)
        ]);
        
        try {
            // Check if database already exists to prevent duplicates
            $databaseName = $tenant->database()->getName();
            
            // Create database credentials
            $tenant->database()->makeCredentials();
            
            // Get the database manager through the tenant's database config
            $manager = $tenant->database()->manager();
            
            // Check if database already exists before creating
            $exists = $this->databaseExists($databaseName);
            
            if ($exists) {
                Log::warning('CreateTenantDatabase: Database already exists', [
                    'database_name' => $databaseName
                ]);
            } else {
                Log::info('CreateTenantDatabase: Database does not exist, proceeding with creation', [
                    'database_name' => $databaseName
                ]);
            }
            
            if (!$exists) {
                // Create the database - this is the only method we need
                $manager->createDatabase($tenant);
                
                Log::info('CreateTenantDatabase: Database created successfully', [
                    'database_name' => $databaseName
                ]);
            } else {
                Log::info('CreateTenantDatabase: Skipping database creation as it already exists', [
                    'database_name' => $databaseName
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('CreateTenantDatabase: Failed to create database', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Check if a database exists
     */
    protected function databaseExists(string $databaseName): bool
    {
        try {
            // Query PostgreSQL to check if database exists
            $result = \DB::connection('pgsql')->select(
                "SELECT 1 FROM pg_database WHERE datname = ?",
                [$databaseName]
            );
            
            return count($result) > 0;
        } catch (\Exception $e) {
            Log::error('Error checking database existence', [
                'database' => $databaseName,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}