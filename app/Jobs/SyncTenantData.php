<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Space;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTenantData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Space $tenant
    ) {
        // Usar la cola de migraciones de tenant si existe
        $this->onQueue('tenant-migrations');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando sincronización de datos para tenant', [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
        ]);
        
        // Sincronizar configuraciones con servicios externos
        $this->syncExternalServices();
        
        // Actualizar configuraciones relacionadas
        $this->updateRelatedConfigurations();
        
        // Regenerar estadísticas si es necesario
        $this->regenerateStatistics();
        
        Log::info('Sincronización completada para tenant', [
            'tenant_id' => $this->tenant->id,
        ]);
    }
    
    /**
     * Sincronizar con servicios externos (Stripe, etc.)
     */
    protected function syncExternalServices(): void
    {
        // Si el tenant tiene un customer_id de Stripe, actualizar metadata
        if ($this->tenant->stripe_id) {
            try {
                // Aquí podrías actualizar la información en Stripe
                Log::info("Actualizando metadata de Stripe para tenant {$this->tenant->id}");
                
                // Ejemplo de actualización de metadata en Stripe
                // $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                // $stripe->customers->update($this->tenant->stripe_id, [
                //     'metadata' => [
                //         'tenant_id' => $this->tenant->id,
                //         'tenant_name' => $this->tenant->name,
                //         'subscription_status' => $this->tenant->subscription_status,
                //     ]
                // ]);
            } catch (\Exception $e) {
                Log::error("Error actualizando Stripe para tenant {$this->tenant->id}: {$e->getMessage()}");
            }
        }
    }
    
    /**
     * Actualizar configuraciones relacionadas
     */
    protected function updateRelatedConfigurations(): void
    {
        // Actualizar límites basados en el plan
        if ($this->tenant->wasChanged('subscription_plan')) {
            $limits = $this->getPlanLimits($this->tenant->subscription_plan);
            
            // Solo intentar actualizar si la base de datos del tenant existe
            try {
                $dbName = 'tenant' . $this->tenant->id;
                $dbExists = \DB::connection('pgsql')->select(
                    "SELECT 1 FROM pg_database WHERE datname = ?",
                    [$dbName]
                );
                
                if (count($dbExists) > 0) {
                    // Guardar límites en la base de datos del tenant
                    tenancy()->initialize($this->tenant);
                    
                    // Aquí podrías actualizar configuraciones específicas del tenant
                    // Por ejemplo, actualizar una tabla de configuración
                    
                    tenancy()->end();
                    
                    Log::info("Límites actualizados para tenant {$this->tenant->id} con plan {$this->tenant->subscription_plan}");
                } else {
                    Log::info("Saltando actualización de configuración - base de datos del tenant aún no existe", [
                        'tenant_id' => $this->tenant->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning("Error verificando existencia de base de datos del tenant", [
                    'tenant_id' => $this->tenant->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Regenerar estadísticas del tenant
     */
    protected function regenerateStatistics(): void
    {
        // Si cambió información relevante, regenerar estadísticas
        if ($this->tenant->wasChanged(['name', 'subscription_status'])) {
            // Aquí podrías recalcular estadísticas del tenant
            Log::info("Regenerando estadísticas para tenant {$this->tenant->id}");
        }
    }
    
    /**
     * Obtener límites según el plan
     */
    protected function getPlanLimits(string $plan): array
    {
        return match($plan) {
            'premium' => [
                'users' => 100,
                'projects' => 50,
                'storage_gb' => 100,
            ],
            'pro' => [
                'users' => 25,
                'projects' => 20,
                'storage_gb' => 50,
            ],
            default => [
                'users' => 5,
                'projects' => 5,
                'storage_gb' => 10,
            ],
        };
    }
}