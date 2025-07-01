<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Symfony\Component\Yaml\Yaml;

class DemoDataService
{
    /**
     * Modelos que pueden contener datos de demostración.
     *
     * @var array
     */
    protected $models = [
        TimeEntry::class => 'Entradas de tiempo',
        Comment::class => 'Comentarios',
        Task::class => 'Tareas',
        Project::class => 'Proyectos',
        TaskState::class => 'Estados de tareas',
        Tag::class => 'Etiquetas',
    ];

    /**
     * Obtener estadísticas de datos demo.
     *
     * @return array
     */
    public function getDemoStats(): array
    {
        $stats = [];
        $total = 0;
        
        foreach ($this->models as $model => $label) {
            $count = $model::where('is_demo', true)->count();
            $stats[$model] = [
                'label' => $label,
                'count' => $count,
            ];
            $total += $count;
        }
        
        return [
            'models' => $stats,
            'total' => $total,
        ];
    }

    /**
     * Obtener escenarios disponibles.
     *
     * @return array
     */
    public function getAvailableScenarios(): array
    {
        $scenariosPath = database_path('demos');
        
        if (!File::exists($scenariosPath)) {
            return [];
        }
        
        $files = File::files($scenariosPath);
        $scenarios = [];
        
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            
            try {
                $content = Yaml::parseFile($file);
                $description = $content['description'] ?? 'Sin descripción';
                
                $scenarios[] = [
                    'id' => $name,
                    'name' => str_replace('_', ' ', ucfirst($name)),
                    'description' => $description,
                    'projects_count' => count($content['projects'] ?? []),
                ];
            } catch (\Exception $e) {
                // Ignorar archivos con formato incorrecto
                continue;
            }
        }
        
        return $scenarios;
    }

    /**
     * Generar datos de demostración.
     *
     * @param string|null $scenario
     * @param array $options
     * @return array
     */
    public function generateDemoData(?string $scenario = null, array $options = []): array
    {
        $command = 'demo:seed';
        
        // Usar el tenant_id de las opciones si está disponible, o intentar obtenerlo del contexto
        $tenantId = $options['tenant_id'] ?? null;
        
        // Si no viene en opciones, intentar obtenerlo del contexto
        if (!$tenantId) {
            $currentTenant = tenant();
            if ($currentTenant) {
                $tenantId = $currentTenant->id ?? $currentTenant->getKey() ?? tenant('id');
            }
        }
        
        if ($tenantId) {
            $command .= " --tenant={$tenantId}";
        }
        
        // No agregar el escenario si es "default" o está vacío
        if ($scenario && $scenario !== 'default') {
            $command .= " --scenario={$scenario}";
        }
        
        if (!empty($options['skip_time_entries'])) {
            $command .= ' --skip-time-entries';
        }
        
        if (!empty($options['only_structure'])) {
            $command .= ' --only-structure';
        }
        
        if (!empty($options['start_date'])) {
            $command .= " --start-date=\"{$options['start_date']}\"";
        }
        
        if (!empty($options['user_id'])) {
            $command .= " --user={$options['user_id']}";
        }
        
        try {
            \Log::info('DemoDataService: Ejecutando comando', [
                'command' => $command,
                'tenant_from_options' => $options['tenant_id'] ?? null,
                'tenant_from_context' => tenant() ? (tenant()->id ?? tenant()->getKey() ?? tenant('id')) : null,
                'tenant_used' => $tenantId,
            ]);
            
            $exitCode = Artisan::call($command);
            $output = Artisan::output();
            
            \Log::info('DemoDataService: Comando ejecutado', [
                'exitCode' => $exitCode,
                'output' => $output,
            ]);
            
            return [
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Datos de demostración generados correctamente.' : 'Error al generar datos',
                'output' => $output,
            ];
        } catch (\Exception $e) {
            \Log::error('DemoDataService: Error al ejecutar comando', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al generar datos de demostración: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Eliminar datos de demostración.
     *
     * @return array
     */
    public function resetDemoData(): array
    {
        try {
            $command = 'demo:reset';
            
            // Obtener el tenant actual de la misma manera que en generateDemoData
            $tenantId = null;
            
            // Intentar obtener del contexto actual
            $currentTenant = tenant();
            if ($currentTenant) {
                $tenantId = $currentTenant->id ?? $currentTenant->getKey() ?? tenant('id');
            }
            
            // Si no se encontró, intentar desde el dominio
            if (!$tenantId && request()) {
                $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', request()->getHost())->first();
                if ($domain) {
                    $tenantId = $domain->tenant_id;
                }
            }
            
            // Si no se encontró, intentar desde la sesión
            if (!$tenantId && session()->has('current_space_id')) {
                $tenantId = session('current_space_id');
            }
            
            if ($tenantId) {
                $command .= " --tenant={$tenantId}";
            }
            
            // Agregar --force para evitar confirmaciones
            $command .= " --force";
            
            \Log::info('DemoDataService: Ejecutando reset', [
                'command' => $command,
                'tenant_id' => $tenantId,
            ]);
            
            $exitCode = Artisan::call($command);
            $output = Artisan::output();
            
            return [
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Datos de demostración eliminados correctamente.' : 'Error al eliminar datos',
                'output' => $output,
            ];
        } catch (\Exception $e) {
            \Log::error('DemoDataService: Error al resetear', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al eliminar datos de demostración: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generar snapshot de datos demo.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function generateSnapshot()
    {
        $tenant = tenant('id');
        $filename = "demo_data_{$tenant}_" . date('Y-m-d_His') . '.sql';
        $tempFile = storage_path('app/' . $filename);
        
        // Generar SQL para cada modelo
        $sql = "-- Demo data snapshot for tenant: {$tenant}\n";
        $sql .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($this->models as $model => $label) {
            $records = $model::where('is_demo', true)->get();
            
            if ($records->isEmpty()) {
                continue;
            }
            
            $sql .= "-- {$label} ({$records->count()} records)\n";
            
            foreach ($records as $record) {
                $table = $record->getTable();
                $attributes = $record->getAttributes();
                
                // Construir INSERT statement
                $sql .= "INSERT INTO `{$table}` (";
                $sql .= implode(', ', array_map(function ($key) {
                    return "`{$key}`";
                }, array_keys($attributes)));
                $sql .= ") VALUES (";
                $sql .= implode(', ', array_map(function ($value) {
                    if (is_null($value)) {
                        return 'NULL';
                    } elseif (is_bool($value)) {
                        return $value ? '1' : '0';
                    } elseif (is_numeric($value)) {
                        return $value;
                    } else {
                        return "'" . addslashes($value) . "'";
                    }
                }, array_values($attributes)));
                $sql .= ");\n";
            }
            
            $sql .= "\n";
        }
        
        // Guardar SQL en archivo temporal
        File::put($tempFile, $sql);
        
        // Devolver archivo para descarga
        return Response::download($tempFile, $filename, [
            'Content-Type' => 'application/sql',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Clonar datos a otro tenant.
     *
     * @param string $targetTenant
     * @param bool $markAsDemo
     * @return array
     */
    public function cloneToTenant(string $targetTenant, bool $markAsDemo = true): array
    {
        $sourceTenant = tenant('id');
        $command = "demo:clone {$sourceTenant} {$targetTenant}";
        
        if ($markAsDemo) {
            $command .= ' --mark-as-demo';
        }
        
        try {
            Artisan::call($command);
            
            return [
                'success' => true,
                'message' => "Datos clonados correctamente al tenant '{$targetTenant}'.",
                'output' => Artisan::output(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al clonar datos: ' . $e->getMessage(),
            ];
        }
    }
}