<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Space;

class TenantMigrateBack extends Command
{
    protected $signature = 'tenants:migrate-back {batch : El número de lote hasta el cual revertir} {--tenant= : El ID del tenant}';
    protected $description = 'Revierte migraciones de tenant hasta el número de lote indicado';

    /**
     * Ejecuta el comando.
     *
     * @return int
     */
    public function handle(): int
    {
        $batch = $this->argument('batch');
        $tenantId = $this->option('tenant');
        
        if (!is_numeric($batch) || $batch < 1) {
            $this->error('El número de lote debe ser un número positivo.');
            return 1;
        }
        
        // Determinar qué tenants procesar
        $tenants = [];
        if ($tenantId) {
            $tenant = Space::find($tenantId);
            if (!$tenant) {
                $this->error("No se encontró el tenant con ID: $tenantId");
                return 1;
            }
            $tenants = [$tenant];
        } else {
            $tenants = Space::all();
        }
        
        foreach ($tenants as $tenant) {
            $this->info("Revirtiendo migraciones para tenant: {$tenant->id} hasta el lote $batch");
            
            // Inicializar el tenant
            tenancy()->initialize($tenant);
            
            // Ejecutar rollback hasta el lote especificado
            $this->call('migrate:rollback', [
                '--batch' => $batch,
            ]);
            
            // Actualizar estados en la tabla central
            DB::table('tenant_migration_states')
                ->where('tenant_id', $tenant->id)
                ->where('batch', '>=', $batch)
                ->delete();
            
            // Finalizar el tenant
            tenancy()->end();
            
            $this->info("Rollback completado para tenant: {$tenant->id}");
        }
        
        return 0;
    }
}