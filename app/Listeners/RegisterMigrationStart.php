<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Events\MigratingDatabase;

class RegisterMigrationStart
{
    /**
     * Maneja el evento de inicio de migración.
     *
     * @param MigratingDatabase $event
     * @return void
     */
    public function handle(MigratingDatabase $event): void
    {
        $tenant = $event->tenant;
        
        Log::channel('tenant_migrations')->info("Iniciando migraciones para tenant: {$tenant->getTenantKey()}");
    }
}