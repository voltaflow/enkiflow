<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stancl\Tenancy\Events\TenantUpdated;

class NotifyTenantChanges
{
    /**
     * Handle the event.
     */
    public function handle(TenantUpdated $event): void
    {
        $tenant = $event->tenant;
        
        // Notificar solo si hay cambios críticos
        $criticalFields = ['name', 'subscription_status', 'subscription_plan', 'owner_id'];
        $changedCriticalFields = array_intersect($criticalFields, array_keys($tenant->getChanges()));
        
        if (empty($changedCriticalFields)) {
            return;
        }
        
        // Obtener el owner del tenant
        $owner = User::find($tenant->owner_id);
        
        if (!$owner) {
            Log::warning("No se pudo encontrar el owner del tenant {$tenant->id} para notificar cambios");
            return;
        }
        
        // Preparar información de cambios
        $changes = [];
        foreach ($changedCriticalFields as $field) {
            $original = $tenant->getOriginal($field);
            $new = $tenant->getAttribute($field);
            $changes[$field] = [
                'original' => $original,
                'new' => $new,
            ];
        }
        
        // Log de los cambios críticos
        Log::info('Cambios críticos en tenant', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'changed_fields' => $changedCriticalFields,
            'changes' => $changes,
            'notified_user' => $owner->email,
        ]);
        
        // Si el plan de suscripción cambió, podríamos enviar un email
        if (in_array('subscription_plan', $changedCriticalFields)) {
            // Aquí podrías implementar el envío de email
            // Mail::to($owner)->send(new TenantPlanChanged($tenant, $changes['subscription_plan']));
            
            Log::info("Plan de suscripción cambiado para tenant {$tenant->id}: {$changes['subscription_plan']['original']} -> {$changes['subscription_plan']['new']}");
        }
        
        // Si el estado de suscripción cambió a inactivo, notificar urgentemente
        if (in_array('subscription_status', $changedCriticalFields) && $tenant->subscription_status === 'inactive') {
            Log::warning("Tenant {$tenant->id} marcado como inactivo. Owner: {$owner->email}");
            // Aquí podrías implementar notificaciones urgentes
        }
    }
}