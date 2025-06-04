# Documentación de Implementaciones del Sistema de Gestión de Ciclo de Vida de Tenants

## Tabla de Contenidos
1. [Introducción](#introducción)
2. [Arquitectura General](#arquitectura-general)
3. [TenantProvisioningService](#tenantprovisioningservice)
4. [TenantBackupService](#tenantbackupservice)
5. [Comandos Artisan](#comandos-artisan)
6. [Scheduler y Tareas Programadas](#scheduler-y-tareas-programadas)
7. [Consideraciones de Seguridad](#consideraciones-de-seguridad)
8. [Consideraciones de Rendimiento](#consideraciones-de-rendimiento)
9. [Guía de Uso](#guía-de-uso)
10. [Troubleshooting](#troubleshooting)
11. [Mejoras Futuras](#mejoras-futuras)

## Introducción

Este documento describe la implementación completa del sistema de gestión del ciclo de vida de tenants para el proyecto Enkiflow. El sistema proporciona capacidades integrales para:

- **Provisionamiento**: Creación y configuración inicial de tenants
- **Respaldo y Restauración**: Sistema completo de backups por tenant
- **Mantenimiento**: Optimización y limpieza automatizada
- **Monitoreo**: Seguimiento del estado y salud de los tenants

### Componentes Implementados

1. **TenantProvisioningService**: Servicio centralizado para la creación y configuración de tenants
2. **TenantBackupService**: Sistema de respaldo y restauración de bases de datos
3. **TenantBackupCommand**: Comando para crear backups manualmente o programados
4. **TenantRestoreCommand**: Comando para restaurar backups
5. **TenantMaintenanceCommand**: Comando para tareas de mantenimiento
6. **Kernel.php**: Configuración del scheduler para automatización

## Arquitectura General

### Diagrama de Flujo del Sistema

```
┌─────────────────────┐
│   Usuario/Admin     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐     ┌──────────────────────┐
│ TenantProvisioning  │────▶│  Eventos y Jobs      │
│      Service        │     │ - TenantProvisioned  │
└──────────┬──────────┘     │ - SendWelcomeMail    │
           │                └──────────────────────┘
           ▼
┌─────────────────────┐
│   Space (Tenant)    │
│   - Base de datos   │
│   - Configuración   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────────────────────┐
│            Ciclo de Vida                     │
│                                              │
│  ┌─────────────┐  ┌──────────────┐         │
│  │   Backup    │  │ Maintenance  │         │
│  │   Service   │  │   Command    │         │
│  └─────────────┘  └──────────────┘         │
│         │                 │                  │
│         ▼                 ▼                  │
│  ┌─────────────┐  ┌──────────────┐         │
│  │  pg_dump/   │  │   VACUUM     │         │
│  │  pg_restore │  │   ANALYZE    │         │
│  └─────────────┘  └──────────────┘         │
└──────────────────────────────────────────────┘
           │
           ▼
┌─────────────────────┐
│    Scheduler        │
│  (Tareas Auto.)     │
└─────────────────────┘
```

### Integración con el Sistema Existente

Los componentes se integran perfectamente con:

- **Modelo Space**: Representa los tenants en el sistema
- **TenantCreator**: Servicio base para la creación de tenants
- **Stancl/Tenancy**: Package de multi-tenancy utilizado
- **Laravel Queue**: Para procesamiento asíncrono
- **PostgreSQL**: Base de datos principal

## TenantProvisioningService

### Problema que Resuelve

El servicio de provisionamiento centraliza y estandariza el proceso completo de creación de tenants, asegurando que:
- Todos los pasos necesarios se ejecuten en orden
- Las transacciones mantengan la integridad de datos
- Los eventos y notificaciones se disparen correctamente
- Se validen los límites según el plan del usuario

### Funcionamiento

```php
// Flujo de provisionamiento
1. Validación de límites del plan
2. Inicio de transacción
3. Creación del tenant (TenantCreator)
4. Registro en logs
5. Disparo de eventos
6. Envío de emails de bienvenida (async)
7. Commit de transacción
```

### Métodos Principales

#### provision($data, $owner)
Crea un nuevo tenant con toda la configuración necesaria.

```php
$service = app(TenantProvisioningService::class);
$tenant = $service->provision([
    'name' => 'Mi Empresa',
    'plan' => 'pro',
    'auto_tracking_enabled' => true,
], $userId);
```

#### updateSettings($tenant, $settings)
Actualiza la configuración de un tenant existente.

```php
$tenant = $service->updateSettings($tenant, [
    'timezone' => 'America/Mexico_City',
    'language' => 'es',
    'features' => ['advanced_reports' => true]
]);
```

#### setActive($tenant, $active)
Activa o desactiva un tenant.

```php
// Desactivar temporalmente
$service->setActive($tenant, false);

// Reactivar
$service->setActive($tenant, true);
```

#### suspend($tenant, $reason)
Suspende un tenant por razones específicas.

```php
$service->suspend($tenant, 'payment_failed');
```

#### canProvision($owner, $plan)
Verifica si un usuario puede crear más tenants según su plan.

```php
if ($service->canProvision($user, 'free')) {
    // Permitir creación
}
```

### Eventos Disparados

- **TenantProvisioned**: Se dispara cuando un tenant es creado exitosamente
- **TenantStatusChanged**: Se dispara cuando cambia el estado del tenant

## TenantBackupService

### Problema que Resuelve

Proporciona un sistema robusto de respaldo y restauración que:
- Protege los datos de cada tenant individualmente
- Permite recuperación ante desastres
- Facilita migraciones y copias de seguridad
- Mantiene un historial de backups

### Funcionamiento

#### Proceso de Backup

```
1. Verificar directorio de backups
2. Generar nombre único con timestamp
3. Ejecutar pg_dump con compresión
4. Verificar integridad del archivo
5. Registrar en logs
6. Retornar ruta del backup
```

#### Proceso de Restauración

```
1. Verificar existencia del backup
2. Eliminar base de datos actual
3. Crear base de datos nueva
4. Ejecutar pg_restore
5. Verificar restauración
6. Limpiar caché del tenant
```

### Métodos Principales

#### create($tenant)
Crea un backup comprimido de la base de datos del tenant.

```php
$backupService = app(TenantBackupService::class);
$backupPath = $backupService->create($tenant);
// Retorna: /storage/app/backups/tenants/{id}/tenant_{id}_2024-01-15_10-30-00.sql.gz
```

#### restore($tenant, $backupPath)
Restaura un backup en la base de datos del tenant.

```php
$success = $backupService->restore($tenant, $backupPath);
```

#### list($tenant)
Lista todos los backups disponibles para un tenant.

```php
$backups = $backupService->list($tenant);
// Retorna array con información de cada backup
```

#### getStats($tenant)
Obtiene estadísticas de los backups.

```php
$stats = $backupService->getStats($tenant);
// ['total_backups' => 5, 'total_size_human' => '125 MB', ...]
```

### Estructura de Archivos de Backup

```
storage/app/backups/tenants/
├── tenant-id-1/
│   ├── tenant_tenant-id-1_2024-01-15_02-00-00.sql.gz
│   ├── tenant_tenant-id-1_2024-01-14_02-00-00.sql.gz
│   └── tenant_tenant-id-1_2024-01-13_02-00-00.sql.gz
└── tenant-id-2/
    └── tenant_tenant-id-2_2024-01-15_02-00-00.sql.gz
```

## Comandos Artisan

### TenantBackupCommand

```bash
# Backup de un tenant específico
php artisan tenants:backup --tenant=mi-empresa

# Backup de todos los tenants activos
php artisan tenants:backup --all

# Mantener solo los últimos 10 backups
php artisan tenants:backup --all --keep=10

# Verificar requisitos del sistema
php artisan tenants:backup --check
```

### TenantRestoreCommand

```bash
# Listar backups disponibles
php artisan tenants:restore mi-empresa --list

# Restaurar el backup más reciente
php artisan tenants:restore mi-empresa --latest

# Restaurar un backup específico
php artisan tenants:restore mi-empresa --backup=tenant_mi-empresa_2024-01-15_02-00-00.sql.gz
```

### TenantMaintenanceCommand

```bash
# Mantenimiento completo
php artisan tenants:maintenance --all --backup --optimize --cleanup --vacuum

# Solo optimización
php artisan tenants:maintenance --tenant=mi-empresa --optimize --analyze

# Limpieza de datos antiguos (más de 180 días)
php artisan tenants:maintenance --all --cleanup --days=180

# Modo dry-run (ver qué se haría sin ejecutar)
php artisan tenants:maintenance --all --cleanup --dry-run
```

## Scheduler y Tareas Programadas

### Configuración del Cron

Agregar al crontab del servidor:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Tareas Programadas

#### Backups Diarios (2:00 AM)
- Respalda todos los tenants activos
- Mantiene los últimos 7 backups
- Registra en `storage/logs/tenant-backups.log`

#### Mantenimiento Semanal (Domingos 4:00 AM)
- Optimiza bases de datos
- Limpia datos antiguos
- Actualiza estadísticas

#### VACUUM Mensual (Día 1, 3:00 AM)
- Recupera espacio en disco
- Actualiza estadísticas internas de PostgreSQL

#### Limpieza Diaria (5:00 AM)
- Elimina datos de más de 365 días
- Limpia archivos temporales

### Monitoreo de Tareas

```bash
# Ver próximas ejecuciones
php artisan schedule:list

# Ver logs de backups
tail -f storage/logs/tenant-backups.log

# Ver logs de mantenimiento
tail -f storage/logs/tenant-maintenance.log
```

## Consideraciones de Seguridad

### 1. Permisos de Archivos

```bash
# Asegurar permisos correctos
chmod -R 755 storage/app/backups
chown -R www-data:www-data storage/app/backups
```

### 2. Encriptación de Backups

Para ambientes de producción, considerar encriptar los backups:

```php
// En TenantBackupService::create()
// Después de crear el backup
$encryptedPath = $filePath . '.enc';
Process::run(['openssl', 'enc', '-aes-256-cbc', '-in', $filePath, '-out', $encryptedPath, '-k', config('app.key')]);
```

### 3. Almacenamiento Externo

Considerar mover backups antiguos a almacenamiento externo (S3, etc.):

```php
// Ejemplo con S3
Storage::disk('s3')->put(
    "backups/tenants/{$tenant->id}/{$filename}",
    file_get_contents($filePath)
);
```

### 4. Validación de Acceso

Siempre verificar permisos antes de operaciones sensibles:

```php
// En controladores
$this->authorize('manage', $tenant);
```

## Consideraciones de Rendimiento

### 1. Backups Asíncronos

Para tenants grandes, considerar backups asíncronos:

```php
// Job para backup asíncrono
dispatch(new BackupTenantJob($tenant));
```

### 2. Compresión Optimizada

El nivel de compresión 9 es el máximo pero puede ser lento:

```php
// Para mejor rendimiento, usar nivel 6
'--compress=6',
```

### 3. VACUUM Cuidadoso

Evitar VACUUM FULL en producción:

```php
// Usar VACUUM simple
DB::statement('VACUUM');

// NO usar en horario pico
DB::statement('VACUUM FULL'); // ¡Bloquea la BD!
```

### 4. Límites de Recursos

Configurar límites para evitar consumo excesivo:

```php
// En Process::run()
Process::timeout(300)->run($command); // 5 minutos máximo
```

## Guía de Uso

### Para Desarrolladores Junior

#### 1. Crear un Nuevo Tenant

```php
// En un controlador
public function createTenant(Request $request)
{
    $validated = $request->validate([
        'company_name' => 'required|string|max:255',
        'plan' => 'required|in:free,pro,business'
    ]);

    $provisioningService = app(TenantProvisioningService::class);
    
    try {
        $tenant = $provisioningService->provision([
            'name' => $validated['company_name'],
            'plan' => $validated['plan']
        ], auth()->user());
        
        return response()->json([
            'success' => true,
            'tenant' => $tenant,
            'message' => 'Espacio creado exitosamente'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al crear el espacio: ' . $e->getMessage()
        ], 500);
    }
}
```

#### 2. Programar Backup Manual

```php
// En un controlador de administración
public function backupTenant($tenantId)
{
    $tenant = Space::findOrFail($tenantId);
    $this->authorize('manage', $tenant);
    
    $backupService = app(TenantBackupService::class);
    
    try {
        $backupPath = $backupService->create($tenant);
        
        return response()->json([
            'success' => true,
            'backup_path' => basename($backupPath),
            'message' => 'Backup creado exitosamente'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al crear backup: ' . $e->getMessage()
        ], 500);
    }
}
```

#### 3. Verificar Estado de un Tenant

```php
// Método helper
public function getTenantHealth($tenantId)
{
    $tenant = Space::findOrFail($tenantId);
    $backupService = app(TenantBackupService::class);
    
    $stats = $backupService->getStats($tenant);
    
    return [
        'tenant' => [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'status' => $tenant->status,
            'created_at' => $tenant->created_at,
        ],
        'backups' => $stats,
        'database_size' => $this->getDatabaseSize($tenant),
        'last_maintenance' => $this->getLastMaintenance($tenant),
    ];
}
```

## Troubleshooting

### Problemas Comunes y Soluciones

#### 1. Error: "pg_dump: command not found"

**Problema**: Las herramientas de PostgreSQL no están instaladas.

**Solución**:
```bash
# Ubuntu/Debian
sudo apt-get install postgresql-client

# macOS
brew install postgresql

# CentOS/RHEL
sudo yum install postgresql
```

#### 2. Error: "Permission denied" al crear backups

**Problema**: Permisos incorrectos en el directorio de backups.

**Solución**:
```bash
sudo chown -R www-data:www-data storage/app/backups
sudo chmod -R 755 storage/app/backups
```

#### 3. Backup falla con "out of memory"

**Problema**: Tenant muy grande para backup en memoria.

**Solución**:
```php
// Usar streaming en lugar de cargar todo en memoria
// Modificar el comando pg_dump para usar --file directamente
```

#### 4. Scheduler no ejecuta tareas

**Problema**: Cron no está configurado correctamente.

**Solución**:
```bash
# Verificar que el cron esté corriendo
sudo service cron status

# Verificar el crontab
crontab -l

# Probar manualmente
cd /path-to-project && php artisan schedule:run
```

#### 5. Restauración falla con "database in use"

**Problema**: Conexiones activas a la base de datos.

**Solución**:
```php
// Terminar conexiones antes de restaurar
DB::statement("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'tenant{$id}' AND pid <> pg_backend_pid()");
```

### Logs y Debugging

#### Ubicación de Logs

```
storage/logs/
├── laravel.log           # Log general de Laravel
├── tenant-backups.log    # Log de backups programados
├── tenant-maintenance.log # Log de mantenimiento
└── tenant-vacuum.log     # Log de VACUUM mensual
```

#### Habilitar Debug Detallado

```php
// En .env
TENANCY_DEBUG=true
DB_LOG_QUERIES=true

// En el código
\DB::enableQueryLog();
// ... operación ...
$queries = \DB::getQueryLog();
\Log::debug('Queries ejecutadas', $queries);
```

## Mejoras Futuras

### 1. Backups Incrementales

Implementar backups incrementales para ahorrar espacio:

```php
class IncrementalBackupService extends TenantBackupService
{
    public function createIncremental($tenant, $baseBackup)
    {
        // Usar pg_basebackup con WAL
        // Solo respaldar cambios desde $baseBackup
    }
}
```

### 2. Replicación en Tiempo Real

Configurar streaming replication para alta disponibilidad:

```php
class TenantReplicationService
{
    public function setupReplica($tenant, $replicaHost)
    {
        // Configurar replicación maestro-esclavo
        // Monitorear lag de replicación
    }
}
```

### 3. Métricas y Alertas

Sistema de monitoreo proactivo:

```php
class TenantMetricsService
{
    public function collectMetrics($tenant)
    {
        return [
            'database_size' => $this->getDatabaseSize($tenant),
            'query_performance' => $this->getSlowQueries($tenant),
            'connection_count' => $this->getActiveConnections($tenant),
            'backup_health' => $this->getBackupHealth($tenant),
        ];
    }
    
    public function checkAlerts($metrics)
    {
        // Alertar si:
        // - No hay backup en 48h
        // - DB > 80% del límite
        // - Queries lentas > umbral
    }
}
```

### 4. Backup a la Nube

Integración con servicios cloud:

```php
class CloudBackupService
{
    public function uploadToS3($tenant, $backupPath)
    {
        return Storage::disk('s3')->putFileAs(
            "backups/{$tenant->id}",
            new File($backupPath),
            basename($backupPath)
        );
    }
    
    public function uploadToGoogleCloud($tenant, $backupPath)
    {
        // Implementar con Google Cloud Storage
    }
}
```

### 5. UI de Administración

Panel web para gestión visual:

```php
// Rutas para panel de admin
Route::prefix('admin/tenants')->group(function () {
    Route::get('/', [TenantAdminController::class, 'index']);
    Route::get('/{tenant}/backups', [TenantAdminController::class, 'backups']);
    Route::post('/{tenant}/backup', [TenantAdminController::class, 'createBackup']);
    Route::post('/{tenant}/restore', [TenantAdminController::class, 'restore']);
    Route::post('/{tenant}/maintenance', [TenantAdminController::class, 'runMaintenance']);
});
```

### 6. Exportación/Importación de Tenants

Facilitar migración entre ambientes:

```php
class TenantExportService
{
    public function export($tenant)
    {
        return [
            'metadata' => $this->exportMetadata($tenant),
            'database' => $this->exportDatabase($tenant),
            'files' => $this->exportFiles($tenant),
            'configuration' => $this->exportConfig($tenant),
        ];
    }
    
    public function import($exportData, $newTenantId)
    {
        // Importar en nuevo ambiente
    }
}
```

## Referencias y Recursos

### Documentación Oficial

- [Laravel Docs - Task Scheduling](https://laravel.com/docs/10.x/scheduling)
- [PostgreSQL - Backup and Restore](https://www.postgresql.org/docs/current/backup.html)
- [Stancl/Tenancy Documentation](https://tenancyforlaravel.com/docs/v3/)

### Herramientas Recomendadas

- **pgAdmin**: Para gestión visual de PostgreSQL
- **Laravel Telescope**: Para debugging en desarrollo
- **Laravel Horizon**: Para gestión de colas
- **Sentry**: Para monitoreo de errores en producción

### Mejores Prácticas

1. **Siempre probar restauraciones**: Un backup no probado no es un backup
2. **Documentar cambios**: Mantener un log de cambios en la estructura de BD
3. **Monitorear espacio**: Los backups pueden consumir mucho disco
4. **Rotar logs**: Implementar logrotate para los logs de tenants
5. **Auditar accesos**: Registrar quién accede a backups y cuándo

---

## Conclusión

El sistema implementado proporciona una solución completa y robusta para la gestión del ciclo de vida de los tenants en Enkiflow. Con capacidades de provisionamiento automatizado, respaldo y restauración confiables, y mantenimiento programado, el sistema está preparado para escalar y mantener la integridad de los datos de cada tenant.

La arquitectura modular permite extensiones futuras sin afectar la funcionalidad existente, mientras que la integración con Laravel y sus mejores prácticas asegura mantenibilidad a largo plazo.

Para soporte adicional o preguntas, consultar la documentación de Laravel y PostgreSQL, o contactar al equipo de desarrollo.