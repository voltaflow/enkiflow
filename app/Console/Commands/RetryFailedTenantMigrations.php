<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RetryFailedTenantMigrations extends Command
{
    protected $signature = 'tenants:migrate-retry {--tenant= : El ID del tenant} {--migration= : Migración específica a reintentar}';
    protected $description = 'Reintenta migraciones fallidas de tenant';

    /**
     * Ejecuta el comando.
     *
     * @return int
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $migration = $this->option('migration');
        
        $query = DB::table('tenant_migration_states')
            ->where('status', 'failed');
            
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        if ($migration) {
            $query->where('migration', $migration);
        }
        
        $failedMigrations = $query->get();
        
        if ($failedMigrations->isEmpty()) {
            $this->info('No se encontraron migraciones fallidas.');
            return 0;
        }
        
        $this->info('Se encontraron ' . $failedMigrations->count() . ' migraciones fallidas.');
        
        // Agrupar por tenant
        $byTenant = $failedMigrations->groupBy('tenant_id');
        
        foreach ($byTenant as $tenantId => $migrations) {
            $this->info("Reintentando migraciones para tenant: $tenantId");
            
            // Actualizar estado a pendiente
            DB::table('tenant_migration_states')
                ->where('tenant_id', $tenantId)
                ->whereIn('migration', $migrations->pluck('migration'))
                ->update(['status' => 'pending', 'error_message' => null, 'updated_at' => now()]);
            
            // Ejecutar migraciones
            $this->call('tenants:migrate-extended', [
                '--tenants' => [$tenantId],
            ]);
        }
        
        return 0;
    }
}