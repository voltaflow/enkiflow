<?php

namespace App\Services;

use App\Models\Space;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantBackupService
{
    /**
     * Crea un backup de la base de datos de un tenant.
     *
     * @param Space $tenant
     * @return string Ruta del archivo de backup
     * @throws \RuntimeException
     */
    public function create(Space $tenant): string
    {
        // Crear directorio si no existe
        $backupDir = storage_path('app/backups/tenants/' . $tenant->id);
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Generar nombre de archivo con timestamp
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "tenant_{$tenant->id}_{$timestamp}.sql.gz";
        $filePath = "{$backupDir}/{$filename}";

        // Obtener configuración de base de datos
        $dbName = 'tenant' . $tenant->id; // Según config/tenancy.php
        $dbUser = config('database.connections.pgsql.username');
        $dbPassword = config('database.connections.pgsql.password');
        $dbHost = config('database.connections.pgsql.host');
        $dbPort = config('database.connections.pgsql.port');

        // Comando pg_dump para PostgreSQL con compresión gzip
        $command = [
            'pg_dump',
            '--host=' . $dbHost,
            '--port=' . $dbPort,
            '--username=' . $dbUser,
            '--format=custom',
            '--compress=9',
            '--file=' . $filePath,
            $dbName
        ];

        // Ejecutar comando
        $result = Process::run($command, env: [
            'PGPASSWORD' => $dbPassword
        ]);

        // Verificar resultado
        if ($result->failed()) {
            Log::error('Error al crear backup de tenant', [
                'tenant_id' => $tenant->id,
                'error' => $result->errorOutput(),
                'command' => implode(' ', $command),
            ]);
            throw new \RuntimeException('Error al crear backup: ' . $result->errorOutput());
        }

        // Verificar que el archivo se creó
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            throw new \RuntimeException('El archivo de backup no se creó correctamente');
        }

        Log::info('Backup de tenant creado exitosamente', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'file' => $filePath,
            'size' => filesize($filePath),
            'size_human' => $this->formatBytes(filesize($filePath)),
        ]);

        return $filePath;
    }

    /**
     * Restaura un backup en la base de datos de un tenant.
     *
     * @param Space $tenant
     * @param string $backupPath Ruta completa al archivo de backup
     * @return bool
     * @throws \RuntimeException
     */
    public function restore(Space $tenant, string $backupPath): bool
    {
        // Verificar que el archivo existe
        if (!file_exists($backupPath)) {
            throw new \RuntimeException("El archivo de backup no existe: {$backupPath}");
        }

        // Obtener configuración de base de datos
        $dbName = 'tenant' . $tenant->id;
        $dbUser = config('database.connections.pgsql.username');
        $dbPassword = config('database.connections.pgsql.password');
        $dbHost = config('database.connections.pgsql.host');
        $dbPort = config('database.connections.pgsql.port');

        try {
            // Primero, limpiar la base de datos existente
            DB::statement("DROP DATABASE IF EXISTS \"{$dbName}\"");
            DB::statement("CREATE DATABASE \"{$dbName}\"");

            // Comando pg_restore para PostgreSQL
            $command = [
                'pg_restore',
                '--host=' . $dbHost,
                '--port=' . $dbPort,
                '--username=' . $dbUser,
                '--dbname=' . $dbName,
                '--no-owner',
                '--role=' . $dbUser,
                '--clean',
                '--if-exists',
                $backupPath
            ];

            // Ejecutar comando
            $result = Process::run($command, env: [
                'PGPASSWORD' => $dbPassword
            ]);

            // pg_restore puede retornar warnings que no son errores críticos
            // Solo considerar como error si el código de salida es mayor a 1
            if ($result->exitCode() > 1) {
                Log::error('Error al restaurar backup de tenant', [
                    'tenant_id' => $tenant->id,
                    'file' => $backupPath,
                    'error' => $result->errorOutput(),
                    'exit_code' => $result->exitCode(),
                ]);
                throw new \RuntimeException('Error al restaurar backup: ' . $result->errorOutput());
            }

            Log::info('Backup de tenant restaurado exitosamente', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'file' => $backupPath,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Excepción al restaurar backup', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Lista todos los backups disponibles para un tenant.
     *
     * @param Space $tenant
     * @return array
     */
    public function list(Space $tenant): array
    {
        $backupDir = storage_path('app/backups/tenants/' . $tenant->id);
        
        if (!file_exists($backupDir)) {
            return [];
        }

        // Obtener archivos y ordenarlos por fecha (más reciente primero)
        $files = glob("{$backupDir}/tenant_{$tenant->id}_*.sql.gz");
        if ($files === false) {
            return [];
        }
        
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Formatear la información de cada backup
        return array_map(function($file) {
            $filename = basename($file);
            $size = filesize($file);
            $created = filemtime($file);
            
            // Extraer timestamp del nombre del archivo
            preg_match('/tenant_.*_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sql\.gz/', $filename, $matches);
            $backupDate = isset($matches[1]) ? str_replace('_', ' ', $matches[1]) : date('Y-m-d H:i:s', $created);
            
            return [
                'filename' => $filename,
                'path' => $file,
                'size' => $size,
                'size_human' => $this->formatBytes($size),
                'created_at' => date('Y-m-d H:i:s', $created),
                'backup_date' => $backupDate,
            ];
        }, $files);
    }

    /**
     * Elimina un backup específico.
     *
     * @param string $backupPath
     * @return bool
     */
    public function delete(string $backupPath): bool
    {
        if (!file_exists($backupPath)) {
            return false;
        }

        $result = unlink($backupPath);
        
        if ($result) {
            Log::info('Backup eliminado', [
                'file' => $backupPath,
            ]);
        }
        
        return $result;
    }

    /**
     * Obtiene estadísticas de backups para un tenant.
     *
     * @param Space $tenant
     * @return array
     */
    public function getStats(Space $tenant): array
    {
        $backups = $this->list($tenant);
        $totalSize = array_sum(array_column($backups, 'size'));
        
        return [
            'total_backups' => count($backups),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'latest_backup' => $backups[0] ?? null,
            'oldest_backup' => end($backups) ?: null,
        ];
    }

    /**
     * Formatea bytes a una unidad legible por humanos.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Verifica si las herramientas de PostgreSQL están disponibles.
     *
     * @return array
     */
    public function checkRequirements(): array
    {
        $requirements = [];
        
        // Verificar pg_dump
        $pgDumpResult = Process::run(['which', 'pg_dump']);
        $requirements['pg_dump'] = [
            'available' => $pgDumpResult->successful(),
            'path' => trim($pgDumpResult->output()),
        ];
        
        // Verificar pg_restore
        $pgRestoreResult = Process::run(['which', 'pg_restore']);
        $requirements['pg_restore'] = [
            'available' => $pgRestoreResult->successful(),
            'path' => trim($pgRestoreResult->output()),
        ];
        
        // Verificar directorio de backups
        $backupDir = storage_path('app/backups/tenants');
        $requirements['backup_directory'] = [
            'available' => is_dir($backupDir) && is_writable($backupDir),
            'path' => $backupDir,
        ];
        
        return $requirements;
    }
}