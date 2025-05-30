<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Events\TenantUpdated;

class LogTenantUpdated
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TenantUpdated $event): void
    {
        // Acceder al tenant actualizado
        $tenant = $event->tenant;
        
        // Registrar la actualización en el log
        Log::info('Tenant actualizado', [
            'id' => $tenant->id,
            'nombre' => $tenant->name,
            'dominio' => $tenant->domains->first()->domain ?? 'sin dominio',
            'actualizado_en' => now()->toDateTimeString(),
        ]);
        
        // Si existe el canal específico para migraciones de tenant, usarlo también
        if (Log::channel('tenant_migrations')) {
            Log::channel('tenant_migrations')->info("Tenant actualizado: {$tenant->id} - {$tenant->name}");
        }
    }
}