<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Events\DatabaseMigrated;

class RegisterMigrationSuccess
{
    /**
     * Maneja el evento de migración exitosa.
     */
    public function handle(DatabaseMigrated $event): void
    {
        $tenant = $event->tenant;

        // Guardar el tenant actual si existe
        $currentTenant = tenant();

        // Obtener el batch actual del tenant antes de salir del contexto
        $batch = 1;
        try {
            $batch = DB::table('migrations')->max('batch') ?: 1;
        } catch (\Exception $e) {
            // Si hay error, usar batch 1
        }

        // Salir temporalmente del contexto del tenant
        tenancy()->end();

        try {

            // Actualizar el estado de las migraciones a 'migrated' en la base de datos central
            DB::connection(config('tenancy.database.central_connection'))->table('tenant_migration_states')
                ->where('tenant_id', $tenant->getTenantKey())
                ->where('status', 'pending')
                ->update([
                    'status' => 'migrated',
                    'completed_at' => now(),
                    'batch' => $batch,
                    'updated_at' => now(),
                ]);

            Log::channel('tenant_migrations')->info("Migraciones completadas para tenant: {$tenant->getTenantKey()}");
        } finally {
            // Re-inicializar el tenant solo si había uno activo y es diferente al actual
            if ($currentTenant && $currentTenant->getTenantKey() !== $tenant->getTenantKey()) {
                try {
                    tenancy()->initialize($currentTenant);
                } catch (\Exception $e) {
                    // Si falla la reinicialización, es normal en contexto de job asíncrono
                    // No logear como warning si estamos en un job
                    if (! app()->runningInConsole() || ! str_contains($e->getMessage(), 'Database tenant does not exist')) {
                        Log::channel('tenant_migrations')->warning("No se pudo reinicializar tenant: {$e->getMessage()}");
                    }
                }
            }
        }
    }
}
