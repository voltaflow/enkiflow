<?php

namespace App\Console\Commands;

use App\Helpers\RelativeDate;
use Database\Seeders\DemoProjectSeeder;
use Database\Seeders\TaskStateSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DemoSeedCommand extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'demo:seed 
                            {--tenant= : ID del tenant específico (opcional)}
                            {--scenario= : Escenario a utilizar (opcional)}
                            {--start-date= : Fecha de inicio para fechas relativas (opcional)}
                            {--skip-time-entries : No generar entradas de tiempo}
                            {--only-structure : Solo generar estructura básica sin datos}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Genera datos de demostración para desarrollo y pruebas';

    /**
     * Ejecutar el comando.
     */
    public function handle()
    {
        // Establecer fecha de anclaje si se proporciona
        if ($startDate = $this->option('start-date')) {
            RelativeDate::setAnchor($startDate);
            $this->info("Usando fecha de anclaje: " . RelativeDate::getAnchor()->format('Y-m-d'));
        }

        $tenantId = $this->option('tenant');
        $scenario = $this->option('scenario');

        // Validar escenario si se proporciona
        if ($scenario) {
            $scenarioPath = database_path("demos/{$scenario}.yaml");
            if (!File::exists($scenarioPath)) {
                $this->error("Escenario no encontrado: {$scenario}");
                $this->listAvailableScenarios();
                return Command::FAILURE;
            }
        }

        if ($tenantId) {
            // Ejecutar para un tenant específico
            $this->seedTenant($tenantId, $scenario);
        } else {
            // Si se ejecuta desde la web, usar el tenant actual
            if (app()->runningInConsole()) {
                // Solo preguntar si estamos en consola
                if ($this->confirm('¿Desea generar datos de demostración para todos los tenants?')) {
                    $this->seedAllTenants($scenario);
                } else {
                    // Mostrar lista de tenants para seleccionar
                    $tenants = \App\Models\Space::all();
                    
                    if ($tenants->isEmpty()) {
                        $this->error('No hay tenants disponibles.');
                        return Command::FAILURE;
                    }
                    
                    $choices = $tenants->pluck('name', 'id')->toArray();
                
                    $selectedTenant = $this->choice(
                        '¿Para qué tenant desea generar datos de demostración?',
                        $choices
                    );
                    
                    $tenantId = array_search($selectedTenant, $choices);
                    $this->seedTenant($tenantId, $scenario);
                }
            } else {
                // Si no estamos en consola, usar el tenant actual
                $currentTenant = tenant('id');
                if (!$currentTenant) {
                    $this->error('No se pudo determinar el tenant actual.');
                    return Command::FAILURE;
                }
                $this->seedTenant($currentTenant, $scenario);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Generar datos para un tenant específico.
     */
    protected function seedTenant(string $tenantId, ?string $scenario = null)
    {
        $this->info("Generando datos de demostración para tenant: {$tenantId}");
        
        try {
            // Cargar el Space completo antes de inicializar
            $space = \App\Models\Space::find($tenantId);
            if (!$space) {
                throw new \Exception("Tenant no encontrado: {$tenantId}");
            }
            
            tenancy()->initialize($space);
            
            // Verificar si se debe generar solo la estructura
            if ($this->option('only-structure')) {
                $seeder = new TaskStateSeeder();
                $seeder->setCommand($this);
                $seeder->run(true, $tenantId); // true para isDemo, tenantId explícito
                $this->info("✓ Estructura básica generada para tenant: {$tenantId}");
            } else {
                // Crear seeder con opciones
                $seeder = new DemoProjectSeeder($scenario);
                $seeder->setCommand($this);
                $seeder->run($tenantId);
                
                $this->info("✓ Datos de demostración generados correctamente para tenant: {$tenantId}");
            }
            
            // Mostrar mensaje de éxito con instrucciones para acceder a la UI
            $this->info("Para ver y gestionar los datos demo, visite:");
            $this->line("  https://{$tenantId}.enkiflow.com/settings/developer/demo-data");
            
        } catch (\Exception $e) {
            $this->error("Error al generar datos para tenant {$tenantId}: " . $e->getMessage());
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Generar datos para todos los tenants.
     */
    protected function seedAllTenants(?string $scenario = null)
    {
        $tenants = \App\Models\Space::all();
        $bar = $this->output->createProgressBar(count($tenants));
        $bar->start();
        
        foreach ($tenants as $tenant) {
            $this->seedTenant($tenant->id, $scenario);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info('✓ Datos de demostración generados para todos los tenants');
    }

    /**
     * Listar escenarios disponibles.
     */
    protected function listAvailableScenarios()
    {
        $scenariosPath = database_path('demos');
        
        if (!File::exists($scenariosPath)) {
            $this->line('No hay escenarios disponibles.');
            return;
        }
        
        $files = File::files($scenariosPath);
        
        if (empty($files)) {
            $this->line('No hay escenarios disponibles.');
            return;
        }
        
        $this->line('Escenarios disponibles:');
        
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $this->line("  - {$name}");
        }
        
        $this->line('');
        $this->line('Uso: php artisan demo:seed --scenario=nombre_escenario');
    }
}