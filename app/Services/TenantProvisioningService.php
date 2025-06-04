<?php

namespace App\Services;

use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TenantProvisioningService
{
    public function __construct(
        protected TenantCreator $creator,
    ) {}

    /**
     * Provisiona un nuevo tenant con toda la configuración necesaria.
     *
     * @param array $data Datos del tenant (name, plan, settings, etc.)
     * @param User|int $owner Usuario o ID del usuario propietario
     * @return Space
     * @throws \Exception
     */
    public function provision(array $data, $owner): Space
    {
        // Si se pasa un ID de usuario, obtener el objeto User
        if (is_numeric($owner)) {
            $owner = User::findOrFail($owner);
        }

        return DB::transaction(function () use ($data, $owner) {
            try {
                // Crear el tenant usando el TenantCreator existente
                // Asegurarse de que seed_data esté habilitado para inicializar datos
                $data['seed_data'] = $data['seed_data'] ?? true;
                
                $tenant = $this->creator->create($owner, $data);
                
                // Registrar en logs
                Log::info('Tenant provisionado completamente', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'owner_id' => $owner->id,
                    'owner_email' => $owner->email,
                    'plan' => $data['plan'] ?? 'free',
                ]);
                
                // Disparar eventos adicionales
                event(new \App\Events\TenantProvisioned($tenant));
                
                // Ejecutar jobs adicionales si están configurados
                if (config('tenancy.provisioning.send_welcome_email', true)) {
                    \App\Jobs\SendTenantWelcomeMail::dispatch($tenant)->afterCommit();
                }
                
                return $tenant;
            } catch (\Exception $e) {
                Log::error('Error al provisionar tenant', [
                    'error' => $e->getMessage(),
                    'owner_id' => $owner->id,
                    'data' => $data,
                ]);
                
                throw $e;
            }
        });
    }

    /**
     * Actualiza la configuración de un tenant existente.
     *
     * @param Space $tenant
     * @param array $settings
     * @return Space
     */
    public function updateSettings(Space $tenant, array $settings): Space
    {
        return DB::transaction(function () use ($tenant, $settings) {
            // Actualizar configuraciones del tenant
            $tenant->data = array_merge($tenant->data ?? [], ['settings' => $settings]);
            $tenant->save();
            
            Log::info('Configuración de tenant actualizada', [
                'tenant_id' => $tenant->id,
                'settings' => $settings,
            ]);
            
            return $tenant;
        });
    }

    /**
     * Activa o desactiva un tenant.
     *
     * @param Space $tenant
     * @param bool $active
     * @return Space
     */
    public function setActive(Space $tenant, bool $active): Space
    {
        return DB::transaction(function () use ($tenant, $active) {
            $previousStatus = $tenant->status;
            $tenant->status = $active ? 'active' : 'inactive';
            $tenant->save();
            
            Log::info('Estado de tenant actualizado', [
                'tenant_id' => $tenant->id,
                'previous_status' => $previousStatus,
                'new_status' => $tenant->status,
            ]);
            
            // Disparar evento de cambio de estado
            event(new \App\Events\TenantStatusChanged($tenant, $previousStatus, $tenant->status));
            
            return $tenant;
        });
    }

    /**
     * Suspende un tenant por falta de pago o violación de términos.
     *
     * @param Space $tenant
     * @param string $reason
     * @return Space
     */
    public function suspend(Space $tenant, string $reason = 'payment_failed'): Space
    {
        return DB::transaction(function () use ($tenant, $reason) {
            $tenant->status = 'suspended';
            $tenant->data = array_merge($tenant->data ?? [], [
                'suspension' => [
                    'reason' => $reason,
                    'suspended_at' => now()->toIso8601String(),
                ]
            ]);
            $tenant->save();
            
            Log::warning('Tenant suspendido', [
                'tenant_id' => $tenant->id,
                'reason' => $reason,
            ]);
            
            return $tenant;
        });
    }

    /**
     * Verifica si un tenant puede ser provisionado con el plan especificado.
     *
     * @param User $owner
     * @param string $plan
     * @return bool
     */
    public function canProvision(User $owner, string $plan): bool
    {
        // Verificar límites según el plan
        $limits = config('tenancy.plan_limits', [
            'free' => ['max_tenants' => 1],
            'pro' => ['max_tenants' => 5],
            'premium' => ['max_tenants' => -1], // Sin límite
        ]);
        
        $planLimits = $limits[$plan] ?? $limits['free'];
        $maxTenants = $planLimits['max_tenants'] ?? 1;
        
        // Si no hay límite, permitir
        if ($maxTenants === -1) {
            return true;
        }
        
        // Contar tenants actuales del usuario
        $currentTenants = Space::where('owner_id', $owner->id)
            ->whereIn('status', ['active', 'inactive'])
            ->count();
        
        return $currentTenants < $maxTenants;
    }
}