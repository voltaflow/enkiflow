<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\MigrationFailed;
use Stancl\Tenancy\Tenancy;

class RegisterMigrationFail
{
    /**
     * Maneja el evento de migración fallida.
     *
     * @param MigrationFailed $event
     * @return void
     */
    public function handle(MigrationFailed $event): void
    {
        $tenant = $event->tenant;
        $migration = $event->migration;
        $exception = $event->exception;
        
        // Guardar el tenant actual si existe
        $currentTenant = tenant();
        
        // Salir temporalmente del contexto del tenant
        tenancy()->end();
        
        try {
            // Actualizar el estado de la migración a 'failed' en la base de datos central
            DB::connection(config('tenancy.database.central_connection'))->table('tenant_migration_states')
                ->updateOrInsert(
                    ['tenant_id' => $tenant->getTenantKey(), 'migration' => $migration],
                    [
                        'status' => 'failed',
                        'error_message' => $exception->getMessage(),
                        'updated_at' => now(),
                    ]
                );
            
            Log::channel('tenant_migrations')->error("Error en migración para tenant: {$tenant->getTenantKey()}, migración: {$migration}, error: {$exception->getMessage()}");
        } finally {
            // Re-inicializar el tenant si había uno activo y no es el mismo que falló
            if ($currentTenant && $currentTenant->getTenantKey() !== $tenant->getTenantKey()) {
                try {
                    tenancy()->initialize($currentTenant);
                } catch (\Exception $e) {
                    // Si falla la reinicialización, continuar sin tenant
                    Log::channel('tenant_migrations')->warning("No se pudo reinicializar tenant: {$e->getMessage()}");
                }
            }
        }
    }
}