<?php

namespace App\Console\Commands;

use App\Models\Space;
use App\Services\TenantBackupService;
use Illuminate\Console\Command;

class TenantRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:restore
                            {tenant : ID del tenant a restaurar}
                            {--backup= : Nombre del archivo de backup a restaurar}
                            {--latest : Restaurar el backup más reciente}
                            {--list : Listar backups disponibles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaura un backup de la base de datos de un tenant';

    /**
     * Execute the console command.
     */
    public function handle(TenantBackupService $backupService): int
    {
        $tenantId = $this->argument('tenant');
        $tenant = Space::find($tenantId);

        if (!$tenant) {
            $this->error("No se encontró el tenant con ID: {$tenantId}");
            return 1;
        }

        // Si se solicita listar backups
        if ($this->option('list')) {
            return $this->listBackups($tenant, $backupService);
        }

        // Determinar qué backup restaurar
        $backupFile = $this->option('backup');
        $useLatest = $this->option('latest');

        if (!$backupFile && !$useLatest) {
            $this->error('Debe especificar --backup con el nombre del archivo o usar --latest');
            $this->line('Use --list para ver los backups disponibles');
            return 1;
        }

        // Obtener la ruta del backup
        $backupPath = null;
        $backups = $backupService->list($tenant);

        if ($useLatest) {
            if (empty($backups)) {
                $this->error("No hay backups disponibles para el tenant {$tenant->name}");
                return 1;
            }
            $backupPath = $backups[0]['path'];
            $backupFile = $backups[0]['filename'];
        } else {
            // Buscar el archivo especificado
            foreach ($backups as $backup) {
                if ($backup['filename'] === $backupFile) {
                    $backupPath = $backup['path'];
                    break;
                }
            }

            if (!$backupPath) {
                $this->error("No se encontró el archivo de backup: {$backupFile}");
                $this->line('Use --list para ver los backups disponibles');
                return 1;
            }
        }

        // Confirmar la restauración
        $this->warn('⚠️  ADVERTENCIA: Esta operación eliminará todos los datos actuales del tenant');
        $this->info("Tenant: {$tenant->name} ({$tenant->id})");
        $this->info("Backup: {$backupFile}");
        
        if (!$this->confirm('¿Está seguro de que desea continuar?')) {
            $this->info('Operación cancelada');
            return 0;
        }

        // Realizar la restauración
        $this->info('Restaurando backup...');
        
        try {
            $startTime = microtime(true);
            $result = $backupService->restore($tenant, $backupPath);
            $duration = round(microtime(true) - $startTime, 2);
            
            if ($result) {
                $this->newLine();
                $this->info("✓ Backup restaurado exitosamente en {$duration}s");
                $this->info("  Tenant: {$tenant->name}");
                $this->info("  Archivo: {$backupFile}");
                
                // Limpiar caché del tenant
                tenancy()->initialize($tenant);
                \Artisan::call('cache:clear');
                tenancy()->end();
                
                $this->info("  Caché del tenant limpiado");
                
                return 0;
            } else {
                $this->error('Error al restaurar el backup');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Error al restaurar el backup: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Lista los backups disponibles para un tenant.
     */
    protected function listBackups(Space $tenant, TenantBackupService $backupService): int
    {
        $backups = $backupService->list($tenant);
        
        if (empty($backups)) {
            $this->warn("No hay backups disponibles para el tenant {$tenant->name}");
            return 0;
        }

        $this->info("Backups disponibles para {$tenant->name} ({$tenant->id}):");
        $this->newLine();

        $headers = ['Archivo', 'Fecha del backup', 'Tamaño', 'Creado'];
        $rows = [];

        foreach ($backups as $index => $backup) {
            $rows[] = [
                $backup['filename'],
                $backup['backup_date'],
                $backup['size_human'],
                $backup['created_at'],
            ];
        }

        $this->table($headers, $rows);
        
        // Mostrar estadísticas
        $stats = $backupService->getStats($tenant);
        $this->newLine();
        $this->info("Total de backups: {$stats['total_backups']}");
        $this->info("Tamaño total: {$stats['total_size_human']}");

        return 0;
    }
}