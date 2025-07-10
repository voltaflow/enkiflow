<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Enums\ProjectRole;
use App\Models\User;
use App\Models\Project;
use Carbon\Carbon;

class MigrateProjectPermissions extends Command
{
    protected $signature = 'project:migrate-permissions {--tenant= : ID o slug del tenant} {--dry-run : Ejecutar sin realizar cambios} {--batch-size=100 : Número de registros a procesar por lote}';
    protected $description = 'Migra los permisos de proyecto del sistema antiguo al nuevo';

    public function handle()
    {
        $this->info('Iniciando migración de permisos de proyecto...');
        
        // Inicializar tenant si es necesario
        $tenantOption = $this->option('tenant');
        if ($tenantOption) {
            $space = \App\Models\Space::where('id', $tenantOption)
                ->orWhere('slug', $tenantOption)
                ->first();
                
            if (!$space) {
                $this->error("No se encontró el tenant: {$tenantOption}");
                return 1;
            }
            
            tenancy()->initialize($space);
            $this->info("Trabajando en el tenant: {$space->name}");
        }
        
        $dryRun = $this->option('dry-run');
        $batchSize = $this->option('batch-size');
        
        if ($dryRun) {
            $this->warn('Ejecutando en modo simulación (dry-run). No se realizarán cambios.');
        }
        
        // 1. Obtener todos los registros de project_user que no tienen entrada en project_permissions
        $this->info('Obteniendo registros a migrar...');
        
        $totalRecords = DB::connection('tenant')->table('project_user')
            ->leftJoin('project_permissions', function ($join) {
                $join->on('project_user.project_id', '=', 'project_permissions.project_id')
                     ->on('project_user.user_id', '=', 'project_permissions.user_id');
            })
            ->whereNull('project_permissions.id')
            ->count();
            
        $this->info("Se encontraron {$totalRecords} registros para migrar.");
        
        if ($totalRecords === 0) {
            $this->info('No hay registros para migrar. Finalizando.');
            return 0;
        }
        
        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();
        
        $processed = 0;
        $errors = 0;
        // Buscar un admin del espacio actual
        $currentAdmin = null;
        if (tenant()) {
            $spaceId = tenant('id');
            // Obtener el owner del espacio
            $space = tenant();
            $currentAdmin = User::find($space->owner_id);
            
            // Si no hay owner, buscar un admin
            if (!$currentAdmin) {
                $adminSpaceUser = \App\Models\SpaceUser::where('tenant_id', $spaceId)
                    ->where('role', 'admin')
                    ->first();
                if ($adminSpaceUser) {
                    $currentAdmin = User::find($adminSpaceUser->user_id);
                }
            }
        }
        
        // 2. Procesar en lotes para evitar problemas de memoria
        DB::connection('tenant')->table('project_user')
            ->leftJoin('project_permissions', function ($join) {
                $join->on('project_user.project_id', '=', 'project_permissions.project_id')
                     ->on('project_user.user_id', '=', 'project_permissions.user_id');
            })
            ->whereNull('project_permissions.id')
            ->select('project_user.*')
            ->orderBy('project_user.project_id')
            ->chunk($batchSize, function ($records) use (&$processed, &$errors, $bar, $dryRun, $currentAdmin) {
                $inserts = [];
                
                foreach ($records as $record) {
                    try {
                        // Mapear el rol antiguo al nuevo
                        $newRole = $this->mapRole($record->role);
                        
                        // Preparar el registro para inserción
                        $inserts[] = [
                            'project_id' => $record->project_id,
                            'user_id' => $record->user_id,
                            'role' => $newRole,
                            'created_by' => $currentAdmin ? $currentAdmin->id : null,
                            'updated_by' => $currentAdmin ? $currentAdmin->id : null,
                            'is_active' => true,
                            'notes' => 'Migrado automáticamente desde project_user',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                        
                        $processed++;
                    } catch (\Exception $e) {
                        $this->error("Error procesando registro {$record->id}: " . $e->getMessage());
                        $errors++;
                    }
                    
                    $bar->advance();
                }
                
                // Insertar los registros en project_permissions
                if (!$dryRun && count($inserts) > 0) {
                    DB::connection('tenant')->table('project_permissions')->insert($inserts);
                }
            });
            
        $bar->finish();
        $this->newLine(2);
        
        // 3. Mostrar resumen
        $this->info("Migración completada:");
        $this->info("- Registros procesados: {$processed}");
        $this->info("- Errores: {$errors}");
        
        if ($dryRun) {
            $this->warn('Esta fue una simulación. Ejecute sin --dry-run para aplicar los cambios.');
        } else {
            $this->info('Los permisos han sido migrados correctamente.');
        }
        
        return 0;
    }
    
    /**
     * Mapea un rol del sistema antiguo al nuevo sistema.
     */
    private function mapRole(string $oldRole): string
    {
        return match ($oldRole) {
            'manager' => ProjectRole::MANAGER->value,
            'member' => ProjectRole::MEMBER->value,
            'viewer' => ProjectRole::VIEWER->value,
            default => ProjectRole::MEMBER->value, // Valor por defecto
        };
    }
}