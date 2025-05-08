<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class QueryDebugServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (config('app.debug') && config('app.query_logging', false)) {
            DB::listen(function (QueryExecuted $query) {
                // Skip internal queries
                if ($this->shouldIgnoreQuery($query->sql)) {
                    return;
                }
                
                // Skip short queries (typically internal)
                if (strlen($query->sql) < 40) {
                    return;
                }
                
                // Check for tenant-specific tables without WHERE tenant_id clause
                if ($this->isTenantTableWithoutTenantId($query->sql)) {
                    Log::channel('query_debug')->warning('Potential tenant data leak: Query on tenant table without tenant_id filter', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'tenant_id' => tenant() ? tenant()->id : null,
                        'url' => request()->fullUrl(),
                        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                    ]);
                }
                
                // Log all queries for debugging if needed
                if (config('app.log_all_queries', false)) {
                    Log::channel('query_debug')->info('Query executed', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ]);
                }
            });
        }
    }
    
    /**
     * Check if we should ignore this query.
     */
    protected function shouldIgnoreQuery(string $sql): bool
    {
        $ignorePatterns = [
            'information_schema',
            'pg_catalog',
            'sqlite_master',
            'migrations',
            'tenants',
            'domains',
        ];
        
        foreach ($ignorePatterns as $pattern) {
            if (stripos($sql, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if the query is on a tenant table but doesn't have a tenant_id filter.
     */
    protected function isTenantTableWithoutTenantId(string $sql): bool
    {
        // Single database tenant tables that should have tenant_id
        $tenantTables = [
            'projects',
            'tasks',
            'comments',
            'tags',
        ];
        
        // Check if query affects tenant tables
        $isOnTenantTable = false;
        foreach ($tenantTables as $table) {
            if (preg_match('/\b' . $table . '\b/', $sql)) {
                $isOnTenantTable = true;
                break;
            }
        }
        
        // If it's not a tenant table, no need to check further
        if (!$isOnTenantTable) {
            return false;
        }
        
        // Check if there's a tenant_id filter (for single database tenancy)
        if (stripos($sql, 'tenant_id') !== false) {
            return false;
        }
        
        // If this is a SELECT inside a tenant database, it's already isolated
        if (tenant() && DB::connection()->getDatabaseName() === tenant()->database()->getName()) {
            return false;
        }
        
        return true;
    }
}