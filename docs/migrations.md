

# Sistema de Migraciones Asíncronas para Multitenancy en Laravel

## Introducción

El sistema de migraciones asíncronas para multitenancy implementado en este proyecto permite gestionar de manera eficiente y escalable las migraciones de bases de datos en un entorno con múltiples tenants. Utilizando el paquete `stancl/tenancy`, se ha desarrollado una solución robusta que:

1. Ejecuta migraciones de forma asíncrona mediante colas
2. Registra y monitorea el estado de cada migración
3. Proporciona herramientas para diagnosticar y resolver problemas
4. Ofrece una interfaz de línea de comandos completa para gestionar todo el proceso

## Arquitectura del Sistema

### Componentes Principales

El sistema se compone de los siguientes elementos clave:

#### 1. Estructura de Migraciones Separadas

- **Migraciones centrales**: Ubicadas en `database/migrations/`
  - Afectan a la base de datos principal (landlord)
  - Incluyen tablas como `tenant_migration_states` para el seguimiento

- **Migraciones por tenant**: Ubicadas en `database/migrations/tenant/`
  - Se ejecutan en la base de datos de cada tenant
  - Ejemplos: `create_projects_table.php`, `create_tasks_table.php`, etc.

#### 2. Sistema de Control de Estado

- **Tabla central**: `tenant_migration_states`
  - Almacena el estado de cada migración para cada tenant
  - Estados posibles: `pending`, `migrated`, `failed`
  - Registra timestamps, mensajes de error y número de lote

#### 3. Jobs Asíncronos

- **TenantMigrationJob**: Ejecuta migraciones en segundo plano
  - Configura reintentos automáticos (3 intentos)
  - Utiliza una cola dedicada (`tenant-migrations`)
  - Maneja excepciones y actualiza estados

#### 4. Eventos y Listeners

- **MigratingDatabase**: Capturado por `RegisterMigrationStart`
- **DatabaseMigrated**: Capturado por `RegisterMigrationSuccess`
- **MigrationFailed**: Capturado por `RegisterMigrationFail`

#### 5. Comandos Personalizados

- **ExtendedTenantsMigrate**: Extiende el comando original con seguimiento de estado
- **MonitorTenantMigrations**: Muestra el progreso en tiempo real
- **TenantMigrationStatus**: Muestra el estado detallado de las migraciones
- **RetryFailedTenantMigrations**: Reintenta migraciones fallidas
- **TenantMigrateBack**: Realiza rollback de migraciones hasta un lote específico

#### 6. Sistema de Logging Dedicado

- Canal específico: `tenant_migrations`
- Archivo de log: `storage/logs/tenant-migrations.log`
- Registra eventos importantes del ciclo de vida de las migraciones

## Flujo de Trabajo

### 1. Iniciar el Worker Dedicado para Migraciones

Antes de ejecutar migraciones asíncronas, es **imprescindible** iniciar un worker dedicado para procesar los jobs de migración:

```bash
# En una terminal separada, iniciar el worker dedicado
php artisan queue:work database --queue=tenant-migrations --tries=3 --sleep=3
```

Este worker es un componente **esencial** del sistema, ya que:
- Procesa exclusivamente los jobs de la cola `tenant-migrations`
- Configura 3 reintentos automáticos para migraciones fallidas
- Espera 3 segundos entre jobs para evitar sobrecarga

Sin este worker en ejecución, las migraciones asíncronas quedarán encoladas pero no se procesarán.

### 2. Registro de Migraciones Pendientes

Cuando se ejecuta `php artisan tenants:migrate-extended --async`:

1. Se identifican todas las migraciones pendientes para cada tenant
2. Se registran en la tabla `tenant_migration_states` con estado `pending`
3. Se despacha un job `TenantMigrationJob` para cada tenant

```php
// Fragmento de ExtendedTenantsMigrate
protected function registerPendingMigrations($tenant): void
{
    // Obtener archivos de migración
    $migrationPath = database_path('migrations/tenant');
    $files = glob($migrationPath . '/*.php');

    // Obtener migraciones ya ejecutadas
    $ran = DB::table('migrations')->pluck('migration')->toArray();

    $tenantId = $tenant->getTenantKey();

    foreach ($files as $file) {
        $migration = pathinfo($file, PATHINFO_FILENAME);

        if (!in_array($migration, $ran)) {
            // Registrar como pendiente
            $landlordDb->table('tenant_migration_states')
                ->updateOrInsert(
                    ['tenant_id' => $tenantId, 'migration' => $migration],
                    [
                        'status' => 'pending',
                        'started_at' => now(),
                        'updated_at' => now(),
                    ]
                );
        }
    }
}
```

### 3. Ejecución Asíncrona

El worker de cola procesa los jobs:

1. Inicializa el contexto del tenant
2. Ejecuta las migraciones pendientes
3. Dispara eventos según el resultado
4. Finaliza el contexto del tenant

```php
// Fragmento de TenantMigrationJob
public function handle(): void
{
    tenancy()->initialize($this->tenant);
    $id = $this->tenant->getTenantKey();

    try {
        event(new MigratingDatabase($this->tenant));

        $this->migration
            ? $this->runSingle($this->migration)
            : $this->runAll();

        event(new DatabaseMigrated($this->tenant));
        Log::channel('tenant_migrations')->info("✅ Migración OK para {$id}");
    } catch (\Throwable $e) {
        event(new MigrationFailed($this->tenant, $this->migration ?? 'all', $e));
        Log::channel('tenant_migrations')->error("❌ Error migrando {$id}: {$e->getMessage()}");
        throw $e;
    } finally {
        tenancy()->end();
    }
}
```

### 4. Actualización de Estados

Los listeners actualizan los estados en la tabla central:

- **RegisterMigrationSuccess**: Actualiza a `migrated` cuando una migración se completa
- **RegisterMigrationFail**: Actualiza a `failed` cuando una migración falla

```php
// Fragmento de RegisterMigrationSuccess
public function handle(DatabaseMigrated $event): void
{
    $tenant = $event->tenant;

    // Obtener el batch actual
    $batch = DB::table('migrations')->max('batch') ?: 1;

    // Salir del contexto del tenant
    tenancy()->end();

    // Actualizar estados
    DB::connection(config('tenancy.database.central_connection'))
        ->table('tenant_migration_states')
        ->where('tenant_id', $tenant->getTenantKey())
        ->where('status', 'pending')
        ->update([
            'status' => 'migrated',
            'completed_at' => now(),
            'batch' => $batch,
            'updated_at' => now(),
        ]);
}
```

### 5. Monitoreo en Tiempo Real

El comando `tenants:migrations-monitor --watch` permite visualizar el progreso:

```php
// Fragmento de MonitorTenantMigrations
private function printStatus(): void
{
    $rows = DB::table('tenant_migration_states')
        ->select('tenant_id','status',DB::raw('count(*) as total'))
        ->groupBy('tenant_id','status')
        ->get()
        ->groupBy('tenant_id');

    $table = $rows->map(fn($g,$id)=>[
        'Tenant'=>$id,
        'Pendientes'=>$g->firstWhere('status','pending')->total??0,
        'Migradas'=>$g->firstWhere('status','migrated')->total??0,
        'Fallidas'=>$g->firstWhere('status','failed')->total??0,
    ])->values()->toArray();

    $this->table(['Tenant','Pendientes','Migradas','Fallidas'],$table);
    $this->info('Jobs en cola: '.Queue::size('tenant-migrations'));
}
```

### 6. Rollback de Migraciones

El sistema incluye capacidades robustas de rollback que permiten revertir migraciones de manera controlada y con seguimiento de estado. Esta funcionalidad es esencial para:

- Corregir problemas introducidos por migraciones recientes
- Realizar pruebas de despliegue y rollback
- Gestionar versiones de la estructura de datos
- Recuperarse de migraciones fallidas

#### Funcionamiento del Rollback

El comando `tenants:migrate-back` permite revertir migraciones hasta un número de lote específico. Por ejemplo, si tienes migraciones en los lotes 1, 2, 3 y 4, y ejecutas un rollback hasta el lote 2, se revertirán todas las migraciones de los lotes 3 y 4, dejando intactas las de los lotes 1 y 2.

**Proceso interno:**

1. El sistema identifica las migraciones a revertir basándose en el número de lote
2. Ejecuta el método `down()` de cada migración en orden inverso
3. Elimina los registros correspondientes de la tabla `migrations` del tenant
4. Actualiza la tabla central `tenant_migration_states` para reflejar el nuevo estado

#### Comando de Rollback

```bash
php artisan tenants:migrate-back {batch} [--tenant=ID]
```

**Argumentos:**
- `batch` (requerido): El número de lote hasta el cual revertir. Se revertirán todas las migraciones con número de lote mayor o igual al especificado.

**Opciones:**
- `--tenant=ID` (opcional): El ID del tenant específico para el cual realizar el rollback. Si no se especifica, se aplicará a todos los tenants.

#### Ejemplos de Uso

##### 1. Ver los lotes actuales antes de hacer rollback

Antes de ejecutar un rollback, es recomendable verificar los lotes existentes:

```bash
# Ver los lotes de migraciones para un tenant específico
php artisan tenants:migration-status --tenant=demo-space
```

Esto mostrará una tabla con todas las migraciones y sus respectivos números de lote.

##### 2. Rollback para un tenant específico

```bash
# Revertir hasta el lote 3 para un tenant específico
# (Esto revertirá los lotes 4, 5, etc., dejando los lotes 1, 2 y 3)
php artisan tenants:migrate-back 3 --tenant=demo-space
```

##### 3. Rollback para todos los tenants

```bash
# Revertir hasta el lote 2 para todos los tenants
# (Esto revertirá los lotes 3, 4, 5, etc., dejando los lotes 1 y 2)
php artisan tenants:migrate-back 2
```

##### 4. Rollback completo (revertir todas las migraciones)

```bash
# Revertir todas las migraciones (hasta el lote 1)
php artisan tenants:migrate-back 1
```

#### Verificación del Rollback

Después de ejecutar un rollback, es importante verificar que se haya completado correctamente:

```bash
# Verificar el estado de las migraciones después del rollback
php artisan tenants:migration-status --tenant=demo-space
```

Las migraciones revertidas ya no aparecerán en la lista, o si se mantiene un historial, aparecerán con un estado diferente.

#### Escenarios Comunes

##### Revertir una migración problemática reciente

Si acabas de desplegar una migración que está causando problemas:

1. Identifica el número de lote de la migración problemática:
   ```bash
   php artisan tenants:migration-status --tenant=demo-space
   ```

2. Realiza el rollback hasta el lote anterior:
   ```bash
   php artisan tenants:migrate-back [lote_anterior] --tenant=demo-space
   ```

3. Corrige la migración problemática y vuelve a ejecutarla:
   ```bash
   php artisan tenants:migrate-extended --tenant=demo-space --async
   ```

##### Rollback y migración en un solo paso

Para situaciones donde necesitas revertir y volver a aplicar migraciones:

```bash
# Revertir hasta el lote 3
php artisan tenants:migrate-back 3 --tenant=demo-space

# Volver a ejecutar las migraciones
php artisan tenants:migrate-extended --tenant=demo-space --async
```

#### Buenas Prácticas para Rollback

1. **Siempre verifica el estado antes y después** del rollback para confirmar que se haya completado correctamente.

2. **Ten cuidado con las dependencias de datos**: El rollback puede eliminar tablas que contienen datos importantes. Considera hacer respaldos antes de operaciones de rollback.

3. **Implementa métodos `down()` robustos**: Asegúrate de que tus migraciones tengan métodos `down()` bien implementados que reviertan completamente los cambios realizados por `up()`.

4. **Prueba los rollbacks en entornos de desarrollo**: Antes de realizar rollbacks en producción, pruébalos en entornos de desarrollo para verificar que funcionan como se espera.

5. **Considera el impacto en las aplicaciones**: Si las aplicaciones están en uso, un rollback puede causar problemas de compatibilidad. Planifica ventanas de mantenimiento si es necesario.

## Guía Práctica: Añadir una Nueva Migración

### Paso 1: Iniciar el Worker Dedicado

Antes de comenzar cualquier operación de migración asíncrona, es **obligatorio** iniciar el worker dedicado:

```bash
# En una terminal separada
php artisan queue:work database --queue=tenant-migrations --tries=3 --sleep=3
```

Este worker debe mantenerse en ejecución durante todo el proceso de migración.

### Paso 2: Crear la Migración

```bash
php artisan make:migration create_example_table --path=database/migrations/tenant
```

Esto creará un archivo en `database/migrations/tenant/YYYY_MM_DD_HHMMSS_create_example_table.php`.

### Paso 3: Implementar la Migración

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examples', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examples');
    }
};
```

### Paso 4: Ejecutar la Migración Asíncrona

```bash
# Para todos los tenants
php artisan tenants:migrate-extended --async

# O para un tenant específico
php artisan tenants:migrate-extended --tenants=demo-space --async
```

### Paso 5: Monitorear el Progreso

```bash
# Monitoreo en tiempo real
php artisan tenants:migrations-monitor --watch

# O verificar el estado una sola vez
php artisan tenants:migrations-monitor
```

### Paso 6: Verificar el Estado Final

```bash
# Ver estado detallado
php artisan tenants:migration-status --tenant=demo-space

# Verificar logs
tail -f storage/logs/tenant-migrations.log
```

## Verificación del Funcionamiento

Para verificar que el sistema funciona correctamente, debemos comprobar tres aspectos clave:

### 1. Asignación Correcta de Estados

Después de ejecutar `tenants:migrate-extended --async`, verificar:

```bash
php artisan tinker
>>> DB::table('tenant_migration_states')
...     ->where('migration', 'YYYY_MM_DD_HHMMSS_create_example_table')
...     ->first();
```

Debería mostrar un registro con `status` inicialmente como `pending`.

### 2. Ejecución Correcta del Job Asíncrono

Verificar que el worker está procesando los jobs:

```bash
# El worker debe estar ejecutándose en una terminal separada
php artisan queue:work database --queue=tenant-migrations --tries=3
```

Deberías ver mensajes indicando que los jobs se están procesando.

### 3. Actualización del Estado Tras la Ejecución

Después de que el job se complete, verificar nuevamente:

```bash
php artisan tinker
>>> DB::table('tenant_migration_states')
...     ->where('migration', 'YYYY_MM_DD_HHMMSS_create_example_table')
...     ->first();
```

Ahora debería mostrar `status` como `migrated`, con `completed_at` y `batch` establecidos.

## Solución de Problemas Comunes

### Migraciones Atascadas en Estado "Pending"

**Posibles causas**:
- El worker no está ejecutándose
- El job falló sin lanzar una excepción
- Problemas de conexión a la base de datos

**Solución**:
```bash
# Verificar jobs en cola
php artisan tinker
>>> DB::table('jobs')->where('queue', 'tenant-migrations')->count();

# Reiniciar el worker
php artisan queue:restart

# Forzar actualización de estado
php artisan tenants:migrate-retry --tenant=ID --migration=NOMBRE
```

### Migraciones Fallidas

**Posibles causas**:
- Error de sintaxis en la migración
- Conflictos con la estructura existente
- Problemas de permisos

**Solución**:
```bash
# Ver detalles del error
php artisan tenants:migration-status --tenant=ID

# Verificar logs específicos
grep "NOMBRE_MIGRACIÓN" storage/logs/tenant-migrations.log

# Corregir y reintentar
php artisan tenants:migrate-retry --tenant=ID --migration=NOMBRE
```

### Problemas con Rollback

#### El rollback no revierte todas las migraciones esperadas

**Posible causa**: Los números de lote no son consecutivos o hay inconsistencias en la tabla `migrations`.

**Solución**: Verifica la tabla `migrations` directamente:
```bash
php artisan tinker --tenant=demo-space
>>> DB::table('migrations')->orderBy('batch')->get();
```

#### Error "Cannot drop table" durante el rollback

**Posible causa**: Restricciones de clave foránea impiden eliminar la tabla.

**Solución**: Asegúrate de que el método `down()` elimine las restricciones antes de eliminar las tablas:
```php
public function down(): void
{
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('mi_tabla');
    Schema::enableForeignKeyConstraints();
}
```

#### Los estados no se actualizan después del rollback

**Posible causa**: Problemas con los listeners o la conexión a la base de datos central.

**Solución**: Verifica los logs y, si es necesario, actualiza manualmente los estados:
```bash
php artisan tinker
>>> DB::table('tenant_migration_states')
...     ->where('tenant_id', 'demo-space')
...     ->where('batch', '>=', 3)
...     ->delete();
```

### Worker No Procesa Jobs

**Posibles causas**:
- Configuración incorrecta de la cola
- Memoria insuficiente
- Timeout del proceso

**Solución**:
```bash
# Verificar configuración
php artisan queue:work database --queue=tenant-migrations --verbose

# Aumentar memoria y tiempo límite
php artisan queue:work database --queue=tenant-migrations --memory=512 --timeout=300
```

## Buenas Prácticas

1. **Monitorear regularmente** el estado de las migraciones:
   ```bash
   php artisan tenants:migrations-monitor
   ```

2. **Revisar los logs** para detectar problemas temprano:
   ```bash
   tail -f storage/logs/tenant-migrations.log
   ```

3. **Implementar migraciones idempotentes** que puedan ejecutarse múltiples veces sin error:
   ```php
   if (!Schema::hasTable('examples')) {
       Schema::create('examples', function (Blueprint $table) {
           // ...
       });
   }
   ```

4. **Usar transacciones** para migraciones complejas:
   ```php
   DB::transaction(function () {
       // Operaciones de migración
   });
   ```

5. **Configurar supervisord** para mantener el worker en producción:
   ```ini
   [program:tenant-migrations-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/artisan queue:work database --queue=tenant-migrations --tries=3 --sleep=3
   autostart=true
   autorestart=true
   user=www-data
   numprocs=1
   redirect_stderr=true
   stdout_logfile=/path/to/worker.log
   ```

## Conclusión

El sistema de migraciones asíncronas para multitenancy implementado proporciona una solución robusta y escalable para gestionar bases de datos en entornos con múltiples tenants. Mediante el uso de colas, eventos y un sistema de seguimiento de estado, se logra:

1. **Escalabilidad**: Las migraciones se ejecutan en segundo plano sin bloquear el proceso principal
2. **Confiabilidad**: Los reintentos automáticos y el registro de errores facilitan la recuperación
3. **Observabilidad**: El monitoreo en tiempo real y los logs detallados permiten diagnosticar problemas
4. **Flexibilidad**: Comandos específicos para diferentes escenarios de migración

Esta implementación establece un estándar sólido para la gestión de migraciones en proyectos Laravel multitenancy, facilitando el mantenimiento y la evolución de la estructura de datos a medida que el proyecto crece.
