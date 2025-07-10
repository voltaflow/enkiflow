<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\ProjectPermissionResolver;
use App\Models\Project;
use App\Models\User;
use App\Enums\ProjectPermission;

class ValidateProjectPermissionsMigration extends Command
{
    protected $signature = 'project:validate-permissions-migration {--tenant= : ID o slug del tenant} {--sample=50 : Porcentaje de usuarios a validar}';
    protected $description = 'Valida que la migración de permisos de proyecto se haya realizado correctamente';

    protected $resolver;

    public function __construct(ProjectPermissionResolver $resolver)
    {
        parent::__construct();
        $this->resolver = $resolver;
    }

    public function handle()
    {
        $this->info('Iniciando validación de migración de permisos...');
        
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
        
        $samplePercentage = min(100, max(1, (int)$this->option('sample')));
        $this->info("Validando {$samplePercentage}% de los usuarios...");
        
        // 1. Obtener una muestra de usuarios con registros en project_user
        $userIds = DB::connection('tenant')->table('project_user')
            ->select('user_id')
            ->distinct()
            ->get()
            ->pluck('user_id')
            ->toArray();
            
        // Tomar una muestra aleatoria
        $sampleSize = ceil(count($userIds) * $samplePercentage / 100);
        $sampleUserIds = collect($userIds)->random(min($sampleSize, count($userIds)))->toArray();
        
        $this->info("Validando {$sampleSize} usuarios de " . count($userIds) . " totales.");
        
        $inconsistencies = [];
        $validatedPermissions = 0;
        $bar = $this->output->createProgressBar($sampleSize);
        $bar->start();
        
        // 2. Para cada usuario en la muestra, validar sus permisos
        foreach ($sampleUserIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;
            
            // Obtener todos los proyectos del usuario en el sistema antiguo
            $oldPermissions = DB::connection('tenant')->table('project_user')
                ->where('user_id', $userId)
                ->get();
                
            foreach ($oldPermissions as $oldPerm) {
                $project = Project::find($oldPerm->project_id);
                if (!$project) continue;
                
                // Verificar que el usuario tenga un rol equivalente en el nuevo sistema
                $newRole = $this->resolver->getUserRole($user, $project);
                $expectedRole = $this->mapRole($oldPerm->role);
                
                if (!$newRole || $newRole->value !== $expectedRole) {
                    $inconsistencies[] = [
                        'user_id' => $userId,
                        'project_id' => $oldPerm->project_id,
                        'old_role' => $oldPerm->role,
                        'new_role' => $newRole ? $newRole->value : 'ninguno',
                        'expected_role' => $expectedRole,
                        'type' => 'role_mismatch'
                    ];
                }
                
                // Verificar permisos específicos según el rol
                $this->validatePermissionsForRole($user, $project, $oldPerm->role, $inconsistencies);
                $validatedPermissions++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // 3. Mostrar resultados
        $this->info("Validación completada:");
        $this->info("- Permisos validados: {$validatedPermissions}");
        $this->info("- Inconsistencias encontradas: " . count($inconsistencies));
        
        if (count($inconsistencies) > 0) {
            $this->warn("Se encontraron inconsistencias en la migración:");
            
            foreach ($inconsistencies as $i => $inconsistency) {
                if ($i >= 10) {
                    $this->warn("... y " . (count($inconsistencies) - 10) . " más.");
                    break;
                }
                
                $this->warn("- Usuario {$inconsistency['user_id']}, Proyecto {$inconsistency['project_id']}: " . 
                           "Rol antiguo '{$inconsistency['old_role']}', Rol nuevo '{$inconsistency['new_role']}', " . 
                           "Esperado '{$inconsistency['expected_role']}'");
            }
            
            // Guardar inconsistencias en un archivo para análisis
            $filename = storage_path('logs/permission_migration_inconsistencies_' . date('Y-m-d_H-i-s') . '.json');
            file_put_contents($filename, json_encode($inconsistencies, JSON_PRETTY_PRINT));
            $this->info("Se ha guardado un informe detallado en: {$filename}");
            
            return 1;
        } else {
            $this->info("¡La migración se ha validado correctamente! Todos los permisos coinciden.");
            return 0;
        }
    }
    
    /**
     * Mapea un rol del sistema antiguo al nuevo sistema.
     */
    private function mapRole(string $oldRole): string
    {
        return match ($oldRole) {
            'manager' => 'manager',
            'member' => 'member',
            'viewer' => 'viewer',
            default => 'member',
        };
    }
    
    /**
     * Valida los permisos específicos según el rol.
     */
    private function validatePermissionsForRole(User $user, Project $project, string $oldRole, array &$inconsistencies)
    {
        // Definir qué permisos debería tener según su rol antiguo
        $expectedPermissions = match ($oldRole) {
            'manager' => [
                ProjectPermission::MANAGE_MEMBERS,
                ProjectPermission::EDIT_CONTENT,
                ProjectPermission::DELETE_CONTENT,
                ProjectPermission::VIEW_REPORTS,
                ProjectPermission::VIEW_BUDGET,
                ProjectPermission::EXPORT_DATA,
                ProjectPermission::TRACK_TIME,
                ProjectPermission::VIEW_ALL_TIME_ENTRIES,
            ],
            'member' => [
                ProjectPermission::EDIT_CONTENT,
                ProjectPermission::TRACK_TIME,
            ],
            'viewer' => [],
            default => [],
        };
        
        // Verificar cada permiso esperado
        foreach ($expectedPermissions as $permission) {
            if (!$this->resolver->userHasPermission($user, $project, $permission)) {
                $inconsistencies[] = [
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'old_role' => $oldRole,
                    'permission' => $permission->value,
                    'expected' => true,
                    'actual' => false,
                    'type' => 'missing_permission'
                ];
            }
        }
    }
}