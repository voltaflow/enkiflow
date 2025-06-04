<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Space;
use App\Services\TenantBackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TenantMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:maintenance
                            {--tenant=* : IDs de los tenants a mantener}
                            {--all : Ejecutar en todos los tenants}
                            {--backup : Crear backup antes del mantenimiento}
                            {--optimize : Optimizar base de datos}
                            {--cleanup : Limpiar datos antiguos}
                            {--vacuum : Ejecutar VACUUM en la base de datos}
                            {--analyze : Ejecutar ANALYZE para actualizar estadísticas}
                            {--days=90 : Días de antigüedad para limpiar datos (por defecto 90)}
                            {--dry-run : Mostrar qué se haría sin ejecutar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta tareas de mantenimiento en los tenants';

    /**
     * Execute the console command.
     */
    public function handle(TenantBackupService $backupService): int
    {
        $tenantIds = $this->option('tenant');
        $isDryRun = $this->option('dry-run');

        // Si no se especifican tenants y no se usa --all, mostrar error
        if (empty($tenantIds) && !$this->option('all')) {
            $this->error('Debe especificar al menos un tenant con --tenant o usar --all para todos');
            return 1;
        }

        // Si no se especifica ninguna acción, mostrar error
        if (!$this->option('backup') && !$this->option('optimize') && 
            !$this->option('cleanup') && !$this->option('vacuum') && 
            !$this->option('analyze')) {
            $this->error('Debe especificar al menos una acción: --backup, --optimize, --cleanup, --vacuum, --analyze');
            return 1;
        }

        // Si se usa --all, obtener todos los tenants activos
        if ($this->option('all')) {
            $tenants = Space::where('status', 'active')->get();
        } else {
            $tenants = Space::whereIn('id', $tenantIds)->get();
        }

        if ($tenants->isEmpty()) {
            $this->warn('No se encontraron tenants para mantener');
            return 0;
        }

        if ($isDryRun) {
            $this->warn('MODO DRY-RUN: No se realizarán cambios reales');
            $this->newLine();
        }

        $this->info('Iniciando mantenimiento de ' . $tenants->count() . ' tenant(s)');
        $this->newLine();

        $successful = 0;
        $failed = 0;
        
        foreach ($tenants as $tenant) {
            $this->info("═══════════════════════════════════════════");
            $this->info("Mantenimiento para: {$tenant->name} ({$tenant->id})");
            $this->info("═══════════════════════════════════════════");
            
            try {
                // Inicializar el contexto del tenant
                tenancy()->initialize($tenant);
                
                // Crear backup si se solicita
                if ($this->option('backup')) {
                    $this->performBackup($tenant, $backupService, $isDryRun);
                }
                
                // Optimizar base de datos si se solicita
                if ($this->option('optimize')) {
                    $this->performOptimization($tenant, $isDryRun);
                }
                
                // Ejecutar ANALYZE si se solicita
                if ($this->option('analyze')) {
                    $this->performAnalyze($tenant, $isDryRun);
                }
                
                // Ejecutar VACUUM si se solicita
                if ($this->option('vacuum')) {
                    $this->performVacuum($tenant, $isDryRun);
                }
                
                // Limpiar datos antiguos si se solicita
                if ($this->option('cleanup')) {
                    $this->performCleanup($tenant, $isDryRun);
                }
                
                $this->newLine();
                $this->info("✓ Mantenimiento completado para {$tenant->name}");
                $successful++;
                
            } catch (\Exception $e) {
                $this->error("✗ Error en mantenimiento para {$tenant->name}: {$e->getMessage()}");
                Log::error("Error en mantenimiento de tenant", [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failed++;
            } finally {
                // Finalizar el contexto del tenant
                tenancy()->end();
            }
            
            $this->newLine();
        }
        
        // Resumen final
        $this->info("═══════════════════════════════════════════");
        $this->info("Proceso de mantenimiento finalizado");
        $this->info("  - Exitosos: {$successful}");
        if ($failed > 0) {
            $this->error("  - Fallidos: {$failed}");
        }
        $this->info("═══════════════════════════════════════════");
        
        return $failed > 0 ? 1 : 0;
    }
    
    /**
     * Realiza backup del tenant.
     */
    protected function performBackup(Space $tenant, TenantBackupService $backupService, bool $isDryRun): void
    {
        $this->task("Creando backup", function () use ($tenant, $backupService, $isDryRun) {
            if ($isDryRun) {
                $this->line(" → Se crearía backup en: storage/app/backups/tenants/{$tenant->id}/");
                return true;
            }
            
            $backupPath = $backupService->create($tenant);
            $this->line(" → Backup creado: " . basename($backupPath));
            return true;
        });
    }
    
    /**
     * Optimiza la base de datos del tenant.
     */
    protected function performOptimization(Space $tenant, bool $isDryRun): void
    {
        $this->task("Optimizando base de datos", function () use ($isDryRun) {
            if ($isDryRun) {
                $this->line(" → Se ejecutaría: REINDEX DATABASE");
                return true;
            }
            
            // Reindexar tablas
            DB::statement('REINDEX DATABASE CURRENT');
            $this->line(" → Índices reconstruidos");
            
            return true;
        });
    }
    
    /**
     * Ejecuta ANALYZE en la base de datos.
     */
    protected function performAnalyze(Space $tenant, bool $isDryRun): void
    {
        $this->task("Actualizando estadísticas de la base de datos", function () use ($isDryRun) {
            if ($isDryRun) {
                $this->line(" → Se ejecutaría: ANALYZE");
                return true;
            }
            
            // Analizar tablas para mejorar el planificador de consultas
            DB::statement('ANALYZE');
            $this->line(" → Estadísticas actualizadas");
            
            return true;
        });
    }
    
    /**
     * Ejecuta VACUUM en la base de datos del tenant.
     */
    protected function performVacuum(Space $tenant, bool $isDryRun): void
    {
        $this->task("Ejecutando VACUUM", function () use ($isDryRun) {
            if ($isDryRun) {
                $this->line(" → Se ejecutaría: VACUUM (sin FULL para evitar bloqueos)");
                return true;
            }
            
            // VACUUM sin FULL para operaciones más ligeras en producción
            DB::statement('VACUUM');
            $this->line(" → Espacio recuperado y estadísticas actualizadas");
            
            return true;
        });
    }
    
    /**
     * Limpia datos antiguos del tenant.
     */
    protected function performCleanup(Space $tenant, bool $isDryRun): void
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days)->toDateTimeString();
        
        $this->task("Limpiando datos antiguos (> {$days} días)", function () use ($tenant, $cutoffDate, $isDryRun) {
            $totalDeleted = 0;
            
            // Limpiar time_entries antiguos y completados
            $timeEntriesQuery = DB::table('time_entries')
                ->where('created_at', '<', $cutoffDate)
                ->whereNotNull('ended_at');
            
            $timeEntriesCount = $timeEntriesQuery->count();
            
            if (!$isDryRun && $timeEntriesCount > 0) {
                $timeEntriesQuery->delete();
            }
            
            $this->line(" → Time entries antiguos: " . ($isDryRun ? "Se eliminarían " : "Eliminados ") . $timeEntriesCount);
            $totalDeleted += $timeEntriesCount;
            
            // Limpiar application_sessions antiguos
            $sessionsQuery = DB::table('application_sessions')
                ->where('created_at', '<', $cutoffDate);
            
            $sessionsCount = $sessionsQuery->count();
            
            if (!$isDryRun && $sessionsCount > 0) {
                $sessionsQuery->delete();
            }
            
            $this->line(" → Sesiones de aplicación antiguas: " . ($isDryRun ? "Se eliminarían " : "Eliminadas ") . $sessionsCount);
            $totalDeleted += $sessionsCount;
            
            // Limpiar daily_summaries antiguos
            $summariesQuery = DB::table('daily_summaries')
                ->where('date', '<', now()->subDays($days)->toDateString());
            
            $summariesCount = $summariesQuery->count();
            
            if (!$isDryRun && $summariesCount > 0) {
                $summariesQuery->delete();
            }
            
            $this->line(" → Resúmenes diarios antiguos: " . ($isDryRun ? "Se eliminarían " : "Eliminados ") . $summariesCount);
            $totalDeleted += $summariesCount;
            
            // Limpiar archivos temporales del storage del tenant
            if (!$isDryRun) {
                $this->cleanupTempFiles($tenant);
            } else {
                $this->line(" → Se limpiarían archivos temporales del storage");
            }
            
            // Limpiar caché del tenant
            if (!$isDryRun) {
                Artisan::call('cache:clear', [], $this->output);
                $this->line(" → Caché del tenant limpiado");
            } else {
                $this->line(" → Se limpiaría el caché del tenant");
            }
            
            $this->line(" → Total de registros " . ($isDryRun ? "a eliminar" : "eliminados") . ": {$totalDeleted}");
            
            return true;
        });
    }
    
    /**
     * Limpia archivos temporales del tenant.
     */
    protected function cleanupTempFiles(Space $tenant): void
    {
        $tempPath = storage_path("app/tenants/{$tenant->id}/temp");
        
        if (!is_dir($tempPath)) {
            return;
        }
        
        $files = glob($tempPath . '/*');
        $deletedCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && time() - filemtime($file) > 86400) { // Más de 1 día
                unlink($file);
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            $this->line(" → Archivos temporales eliminados: {$deletedCount}");
        }
    }
}