<?php

namespace App\Console\Commands;

use App\Models\Space;
use App\Services\TenantBackupService;
use Illuminate\Console\Command;

class TenantBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:backup
                            {--tenant=* : IDs de los tenants a respaldar}
                            {--all : Respaldar todos los tenants}
                            {--keep=5 : Número de backups a mantener por tenant}
                            {--check : Verificar requisitos del sistema}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea backups de las bases de datos de los tenants';

    /**
     * Execute the console command.
     */
    public function handle(TenantBackupService $backupService): int
    {
        // Si se solicita verificar requisitos
        if ($this->option('check')) {
            return $this->checkRequirements($backupService);
        }

        $tenantIds = $this->option('tenant');
        $keepCount = (int) $this->option('keep');

        // Si no se especifican tenants y no se usa --all, mostrar error
        if (empty($tenantIds) && !$this->option('all')) {
            $this->error('Debe especificar al menos un tenant con --tenant o usar --all para todos');
            return 1;
        }

        // Si se usa --all, obtener todos los tenants activos
        if ($this->option('all')) {
            $tenants = Space::where('status', 'active')->get();
        } else {
            $tenants = Space::whereIn('id', $tenantIds)->get();
        }

        if ($tenants->isEmpty()) {
            $this->warn('No se encontraron tenants para respaldar');
            return 0;
        }

        $this->info('Iniciando respaldo de ' . $tenants->count() . ' tenant(s)');
        $this->newLine();
        
        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($tenants as $tenant) {
            try {
                $startTime = microtime(true);
                $backupPath = $backupService->create($tenant);
                $duration = round(microtime(true) - $startTime, 2);
                
                $bar->advance();
                $this->newLine(2);
                $this->info("✓ Tenant {$tenant->name} ({$tenant->id}) respaldado en {$duration}s");
                $this->line("  Archivo: {$backupPath}");
                $this->line("  Tamaño: " . $this->formatBytes(filesize($backupPath)));
                
                // Eliminar backups antiguos si se excede el límite
                $this->pruneOldBackups($tenant, $backupService, $keepCount);
                
                $successful++;
            } catch (\Exception $e) {
                $bar->advance();
                $this->newLine(2);
                $this->error("✗ Error al respaldar tenant {$tenant->name} ({$tenant->id})");
                $this->line("  Error: {$e->getMessage()}");
                $failed++;
                $errors[] = [
                    'tenant' => $tenant->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        $bar->finish();
        $this->newLine(2);
        
        // Resumen
        $this->info('═══════════════════════════════════════════');
        $this->info("Respaldo completado:");
        $this->info("  - Exitosos: {$successful}");
        if ($failed > 0) {
            $this->error("  - Fallidos: {$failed}");
            foreach ($errors as $error) {
                $this->line("    • Tenant {$error['tenant']}: {$error['error']}");
            }
        }
        $this->info('═══════════════════════════════════════════');

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Verifica los requisitos del sistema.
     */
    protected function checkRequirements(TenantBackupService $backupService): int
    {
        $this->info('Verificando requisitos del sistema...');
        $this->newLine();

        $requirements = $backupService->checkRequirements();
        $allGood = true;

        foreach ($requirements as $requirement => $status) {
            $name = str_replace('_', ' ', ucfirst($requirement));
            if ($status['available']) {
                $this->info("✓ {$name}: Disponible");
                if (!empty($status['path'])) {
                    $this->line("  Ruta: {$status['path']}");
                }
            } else {
                $this->error("✗ {$name}: No disponible");
                $allGood = false;
            }
        }

        $this->newLine();
        if ($allGood) {
            $this->info('Todos los requisitos están satisfechos');
            return 0;
        } else {
            $this->error('Algunos requisitos no están satisfechos');
            $this->line('Para usar backups, necesita instalar las herramientas de PostgreSQL:');
            $this->line('  Ubuntu/Debian: sudo apt-get install postgresql-client');
            $this->line('  macOS: brew install postgresql');
            $this->line('  CentOS/RHEL: sudo yum install postgresql');
            return 1;
        }
    }

    /**
     * Elimina backups antiguos si se excede el límite.
     */
    protected function pruneOldBackups(Space $tenant, TenantBackupService $backupService, int $keep): void
    {
        if ($keep <= 0) {
            return;
        }

        $backups = $backupService->list($tenant);
        
        // Si hay más backups que el límite, eliminar los más antiguos
        if (count($backups) > $keep) {
            $toDelete = array_slice($backups, $keep);
            
            foreach ($toDelete as $backup) {
                if ($backupService->delete($backup['path'])) {
                    $this->line("  <comment>Eliminado backup antiguo: {$backup['filename']}</comment>");
                }
            }
        }
    }

    /**
     * Formatea bytes a una unidad legible.
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}