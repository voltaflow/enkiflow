<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoCloneCommand extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'demo:clone 
                            {source : ID del tenant de origen}
                            {target : ID del tenant de destino}
                            {--mark-as-demo : Marcar todos los datos clonados como demo}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Clona datos de un tenant a otro';

    /**
     * Tablas a clonar.
     *
     * @var array
     */
    protected $tables = [
        'task_states',
        'projects',
        'tasks',
        'tags',
        'taggables',
        'comments',
        'time_entries',
    ];

    /**
     * Ejecutar el comando.
     */
    public function handle()
    {
        $sourceTenant = $this->argument('source');
        $targetTenant = $this->argument('target');
        $markAsDemo = $this->option('mark-as-demo');

        // Verificar que los tenants existan
        if (!$this->tenantExists($sourceTenant)) {
            $this->error("El tenant de origen '{$sourceTenant}' no existe.");
            return Command::FAILURE;
        }

        if (!$this->tenantExists($targetTenant)) {
            $this->error("El tenant de destino '{$targetTenant}' no existe.");
            return Command::FAILURE;
        }

        // Confirmar la operación
        $this->info("Se clonarán datos del tenant '{$sourceTenant}' al tenant '{$targetTenant}'.");
        
        if ($markAsDemo) {
            $this->info("Todos los datos clonados se marcarán como datos de demostración [DEMO].");
        }
        
        if (!$this->confirm('¿Desea continuar?', true)) {
            $this->info('Operación cancelada.');
            return Command::SUCCESS;
        }

        // Clonar datos
        $this->cloneData($sourceTenant, $targetTenant, $markAsDemo);

        return Command::SUCCESS;
    }

    /**
     * Verificar si un tenant existe.
     */
    protected function tenantExists(string $tenantId): bool
    {
        return \App\Models\Space::where('id', $tenantId)->exists();
    }

    /**
     * Clonar datos de un tenant a otro.
     */
    protected function cloneData(string $sourceTenant, string $targetTenant, bool $markAsDemo): void
    {
        $this->info('Iniciando clonación de datos...');
        
        // Inicializar tenant de origen para obtener datos
        tenancy()->initialize($sourceTenant);
        
        $data = [];
        
        // Recopilar datos de cada tabla
        foreach ($this->tables as $table) {
            $this->info("Exportando tabla: {$table}");
            
            $records = DB::table($table)
                ->where('tenant_id', $sourceTenant)
                ->get()
                ->toArray();
            
            $data[$table] = $records;
            
            $this->info("  - {$table}: " . count($records) . " registros");
        }
        
        // Finalizar tenant de origen
        tenancy()->end();
        
        // Inicializar tenant de destino para insertar datos
        tenancy()->initialize($targetTenant);
        
        // Insertar datos en cada tabla
        $bar = $this->output->createProgressBar(count($this->tables));
        $bar->start();
        
        foreach ($this->tables as $table) {
            $this->cloneTable($table, $data[$table], $targetTenant, $markAsDemo);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Finalizar tenant de destino
        tenancy()->end();
        
        $this->info("✓ Datos clonados correctamente del tenant '{$sourceTenant}' al tenant '{$targetTenant}'");
    }

    /**
     * Clonar una tabla específica.
     */
    protected function cloneTable(string $table, array $records, string $targetTenant, bool $markAsDemo): void
    {
        if (empty($records)) {
            return;
        }
        
        // Convertir registros a arrays
        $rows = [];
        
        foreach ($records as $record) {
            $row = (array) $record;
            
            // Cambiar tenant_id
            $row['tenant_id'] = $targetTenant;
            
            // Marcar como demo si se solicita
            if ($markAsDemo && isset($row['is_demo'])) {
                $row['is_demo'] = true;
            }
            
            // Modificar nombres si se marcan como demo
            if ($markAsDemo && isset($row['name']) && !str_starts_with($row['name'], '[DEMO_CLONE]')) {
                $row['name'] = '[DEMO_CLONE] ' . $row['name'];
            }
            
            if ($markAsDemo && isset($row['title']) && !str_starts_with($row['title'], '[DEMO_CLONE]')) {
                $row['title'] = '[DEMO_CLONE] ' . $row['title'];
            }
            
            $rows[] = $row;
        }
        
        // Insertar en chunks para evitar problemas de memoria
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}