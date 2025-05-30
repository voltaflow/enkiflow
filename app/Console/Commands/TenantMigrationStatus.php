<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantMigrationStatus extends Command
{
    protected $signature = 'tenants:migration-status {--tenant= : El ID del tenant}';

    protected $description = 'Muestra el estado de todas las migraciones de tenant';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        $query = DB::table('tenant_migration_states');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $states = $query->get();

        if ($states->isEmpty()) {
            $this->info('No se encontraron estados de migración.');

            return 0;
        }

        // Agrupar por tenant
        $byTenant = $states->groupBy('tenant_id');

        foreach ($byTenant as $tenantId => $migrations) {
            $this->info("Tenant: $tenantId");

            $table = [];
            foreach ($migrations as $migration) {
                $table[] = [
                    'Migración' => $migration->migration,
                    'Estado' => $migration->status,
                    'Lote' => $migration->batch ?? 'N/A',
                    'Iniciado' => $migration->started_at ?? 'N/A',
                    'Completado' => $migration->completed_at ?? 'N/A',
                    'Error' => $migration->error_message ? substr($migration->error_message, 0, 50).'...' : 'N/A',
                ];
            }

            $this->table(
                ['Migración', 'Estado', 'Lote', 'Iniciado', 'Completado', 'Error'],
                $table
            );

            $this->newLine();
        }

        return 0;
    }
}
