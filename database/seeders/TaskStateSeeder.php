<?php

namespace Database\Seeders;

use App\Models\TaskState;
use Illuminate\Database\Seeder;

class TaskStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(bool $isDemo = false, ?string $tenantId = null): void
    {
        $states = [
            [
                'name' => 'Pendiente',
                'color' => '#9CA3AF', // Gris
                'position' => 0,
                'is_default' => true,
                'is_completed' => false,
            ],
            [
                'name' => 'En progreso',
                'color' => '#3B82F6', // Azul
                'position' => 1,
                'is_default' => false,
                'is_completed' => false,
            ],
            [
                'name' => 'En revisión',
                'color' => '#F59E0B', // Naranja
                'position' => 2,
                'is_default' => false,
                'is_completed' => false,
            ],
            [
                'name' => 'Bloqueado',
                'color' => '#EF4444', // Rojo
                'position' => 3,
                'is_default' => false,
                'is_completed' => false,
            ],
            [
                'name' => 'Completado',
                'color' => '#10B981', // Verde
                'position' => 4,
                'is_default' => false,
                'is_completed' => true,
            ],
        ];

        // Log inicial para debug
        $this->command->info("TaskStateSeeder iniciado con tenantId: " . ($tenantId ?? 'null'));
        $this->command->info("tenant('id'): " . (tenant('id') ?? 'null'));
        $this->command->info("tenant() exists: " . (tenant() ? 'yes' : 'no'));
        
        foreach ($states as $state) {
            // Usar tenant_id pasado como parámetro o intentar obtenerlo del contexto
            if (!$tenantId) {
                $tenantId = tenant('id') ?: (tenant() ? tenant()->getKey() : null);
                if (!$tenantId && tenant()) {
                    $tenantId = tenant()->id;
                }
            }
            
            if (!$tenantId) {
                $this->command->error("Debug - tenant('id'): " . var_export(tenant('id'), true));
                $this->command->error("Debug - tenant(): " . var_export(tenant(), true));
                throw new \Exception('No se pudo determinar el tenant_id');
            }
            
            $existingState = TaskState::where('name', $state['name'])
                ->where('tenant_id', $tenantId)
                ->first();
                
            if (!$existingState) {
                $taskState = TaskState::create(array_merge(
                    $state,
                    [
                        'tenant_id' => $tenantId,
                        'is_demo' => $isDemo,
                    ]
                ));
                
                $this->command->info("Estado de tarea creado: {$taskState->name}");
            } else {
                $this->command->info("Estado de tarea ya existe: {$existingState->name}");
            }
        }
    }
}