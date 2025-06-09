<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Events\TenantUpdated;

class UpdateTenantCache
{
    /**
     * Handle the event.
     */
    public function handle(TenantUpdated $event): void
    {
        $tenant = $event->tenant;
        
        // Actualizar caché con la información más reciente del tenant
        Cache::put("tenant:{$tenant->id}:metadata", [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => $tenant->domains->first()->domain ?? null,
            'owner_id' => $tenant->owner_id,
            'subscription_status' => $tenant->subscription_status ?? 'active',
            'subscription_plan' => $tenant->subscription_plan ?? null,
            'updated_at' => $tenant->updated_at->toIso8601String(),
        ], now()->addDay());
        
        // Limpiar otras cachés relacionadas que podrían estar desactualizadas
        Cache::forget("tenant:{$tenant->id}:settings");
        Cache::forget("tenant:{$tenant->id}:stats");
        
        // Si el tenant cambió de estado, limpiar caché de permisos
        if ($tenant->wasChanged('subscription_status')) {
            Cache::forget("tenant:{$tenant->id}:features");
            Cache::forget("tenant:{$tenant->id}:limits");
        }
    }
}