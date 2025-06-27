<?php

namespace App\TenantDatabaseManagers;

use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager;

class CustomPostgreSQLDatabaseManager extends PostgreSQLDatabaseManager
{
    /**
     * Override the databaseExists method to use the correct connection
     * and ensure we're checking in the PostgreSQL server, not just the current database.
     */
    public function databaseExists(string $name): bool
    {
        \Log::info('CustomPostgreSQLDatabaseManager::databaseExists called', [
            'database_name' => $name,
            'connection' => $this->connection ?? 'pgsql'
        ]);
        
        try {
            // Use the central connection (which connects to the main database)
            // to query the pg_database system catalog
            $result = DB::connection($this->connection ?? 'pgsql')->select(
                "SELECT 1 FROM pg_database WHERE datname = ?",
                [$name]
            );
            
            $exists = count($result) > 0;
            
            \Log::info('Database existence check result', [
                'database_name' => $name,
                'exists' => $exists
            ]);
            
            return $exists;
        } catch (\Exception $e) {
            \Log::error('Error checking database existence', [
                'database_name' => $name,
                'error' => $e->getMessage()
            ]);
            // If there's an error querying, assume the database doesn't exist
            return false;
        }
    }
}