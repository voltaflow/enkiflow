<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\TimeEntry;
use Illuminate\Console\Command;

class DemoResetCommand extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'demo:reset {--tenant= : ID del tenant específico (opcional)} {--force : No pedir confirmación}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Elimina todos los datos de demostración';

    /**
     * Modelos que pueden contener datos de demostración.
     *
     * @var array
     */
    protected $models = [
        TimeEntry::class,
        Comment::class,
        Task::class,
        Project::class,
        TaskState::class,
        Tag::class,
    ];

    /**
     * Ejecutar el comando.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            // Ejecutar para un tenant específico
            $this->resetTenant($tenantId);
        } else {
            // Preguntar si ejecutar para todos los tenants
            if ($this->confirm('¿Desea eliminar los datos de demostración de todos los tenants?', false)) {
                $this->resetAllTenants();
            } else {
                // Mostrar lista de tenants para seleccionar
                $tenants = \App\Models\Space::all();
                
                if ($tenants->isEmpty()) {
                    $this->error('No hay tenants disponibles.');
                    return Command::FAILURE;
                }
                
                $choices = $tenants->pluck('name', 'id')->toArray();
                
                $selectedTenant = $this->choice(
                    '¿De qué tenant desea eliminar los datos de demostración?',
                    $choices
                );
                
                $tenantId = array_search($selectedTenant, $choices);
                $this->resetTenant($tenantId);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Eliminar datos de demostración de un tenant específico.
     */
    protected function resetTenant(string $tenantId)
    {
        $this->info("Eliminando datos de demostración del tenant: {$tenantId}");
        
        try {
            tenancy()->initialize($tenantId);
            
            // Mostrar resumen de lo que se eliminará
            $this->showSummary();
            
            // Confirmar eliminación solo si no se usa --force
            if (!$this->option('force') && !$this->confirm('¿Está seguro de que desea eliminar estos datos?', true)) {
                $this->info('Operación cancelada.');
                return;
            }
            
            // Eliminar datos de demostración
            $this->deleteData();
            
            $this->info("✓ Datos de demostración eliminados correctamente del tenant: {$tenantId}");
        } catch (\Exception $e) {
            $this->error("Error al eliminar datos del tenant {$tenantId}: " . $e->getMessage());
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Eliminar datos de demostración de todos los tenants.
     */
    protected function resetAllTenants()
    {
        $tenants = \App\Models\Space::all();
        
        // Mostrar resumen global
        $this->info('Se eliminarán datos de demostración de ' . count($tenants) . ' tenants.');
        
        // Confirmar eliminación solo si no se usa --force
        if (!$this->option('force') && !$this->confirm('¿Está seguro de que desea eliminar estos datos?', false)) {
            $this->info('Operación cancelada.');
            return;
        }
        
        $bar = $this->output->createProgressBar(count($tenants));
        $bar->start();
        
        foreach ($tenants as $tenant) {
            $this->resetTenant($tenant->id);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info('✓ Datos de demostración eliminados de todos los tenants');
    }

    /**
     * Mostrar resumen de lo que se eliminará.
     */
    protected function showSummary()
    {
        $this->info('Se eliminarán los siguientes datos de demostración:');
        
        $counts = [];
        
        foreach ($this->models as $model) {
            $count = $model::where('is_demo', true)->count();
            $modelName = class_basename($model);
            $counts[$modelName] = $count;
            
            $this->line("  - {$modelName}: {$count}");
        }
        
        return $counts;
    }

    /**
     * Eliminar datos de demostración.
     */
    protected function deleteData()
    {
        $bar = $this->output->createProgressBar(count($this->models));
        $bar->start();
        
        foreach ($this->models as $model) {
            // Usar forceDelete para modelos con soft deletes, delete normal para los demás
            $query = $model::where('is_demo', true);
            
            if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) {
                $deleted = $query->forceDelete();
            } else {
                $deleted = $query->delete();
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
    }
}