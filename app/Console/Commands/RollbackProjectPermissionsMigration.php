<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RollbackProjectPermissionsMigration extends Command
{
    protected $signature = 'project:rollback-permissions-migration {--before=null : Fecha límite (formato: Y-m-d H:i:s)}';
    protected $description = 'Revierte la migración de permisos de proyecto';

    public function handle()
    {
        if (!$this->confirm('¿Está seguro de que desea revertir la migración de permisos? Esta acción eliminará registros de project_permissions.')) {
            $this->info('Operación cancelada.');
            return 0;
        }
        
        $beforeDate = $this->option('before');
        if ($beforeDate === 'null') {
            $beforeDate = null;
        }
        
        $query = DB::connection('tenant')->table('project_permissions')
            ->where('notes', 'like', '%Migrado automáticamente desde project_user%');
            
        if ($beforeDate) {
            $date = Carbon::parse($beforeDate);
            $query->where('created_at', '<=', $date);
            $this->info("Eliminando registros migrados antes de {$beforeDate}");
        } else {
            $this->info("Eliminando todos los registros migrados automáticamente");
        }
        
        $count = $query->count();
        
        if ($count === 0) {
            $this->info("No se encontraron registros para eliminar.");
            return 0;
        }
        
        $this->info("Se eliminarán {$count} registros.");
        
        if ($this->confirm('¿Desea continuar?')) {
            $deleted = $query->delete();
            $this->info("Se han eliminado {$deleted} registros.");
            
            // Limpiar caché de permisos
            app(\App\Services\ProjectPermissionResolver::class)->clearAllCache();
            $this->info("Caché de permisos limpiada.");
            
            return 0;
        } else {
            $this->info('Operación cancelada.');
            return 0;
        }
    }
}