# Guía Práctica: Sistema Multi-Tenant en EnkiFlow

## Tabla de Contenidos

- [Introducción](#introducción)
- [1. Crear Nuevos Tenants](#1-crear-nuevos-tenants)
- [2. Acceder a Tenants Existentes](#2-acceder-a-tenants-existentes)
- [3. Ejecutar Operaciones en Contexto de Tenant](#3-ejecutar-operaciones-en-contexto-de-tenant)
- [4. Gestionar Datos Aislados por Tenant](#4-gestionar-datos-aislados-por-tenant)
- [5. Escalar a Múltiples Tenants](#5-escalar-a-múltiples-tenants)
- [Comandos de Referencia Rápida](#comandos-de-referencia-rápida)
- [Solución de Problemas](#solución-de-problemas)

## Introducción

EnkiFlow utiliza un sistema multi-tenant que permite que múltiples organizaciones (espacios) compartan la misma aplicación mientras mantienen sus datos completamente aislados. Cada tenant tiene:

- **Base de datos separada**: `tenant{tenant-id}`
- **Dominio único**: `{subdominio}.enkiflow.test`
- **Datos aislados**: proyectos, tareas, tiempo, etc.
- **Configuración propia**: plan, usuarios, permisos

### Arquitectura del Sistema

```
Base de Datos Central (laravel):
├── tenants (espacios)
├── domains (subdominios)
├── users (usuarios globales)
└── space_users (relaciones usuario-espacio)

Base de Datos de Tenant (tenant{id}):
├── projects
├── tasks
├── time_entries
├── comments
└── tags
```

## 1. Crear Nuevos Tenants

### 1.1 Usando Tinker (Método Recomendado)

```bash
php artisan tinker
```

#### Crear Tenant Completo

```php
// 1. Crear o encontrar usuario propietario
$user = \App\Models\User::firstOrCreate([
    'email' => 'admin@empresa.com'
], [
    'name' => 'Administrador Empresa',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);

// 2. Crear el tenant (Space)
$space = \App\Models\Space::create([
    'name' => 'Mi Nueva Empresa',
    'owner_id' => $user->id,
    'data' => [
        'plan' => 'free', // free, basic, professional, enterprise
        'company_name' => 'Mi Nueva Empresa S.A.',
        'industry' => 'Tecnología',
        'timezone' => 'America/Mexico_City',
    ],
]);

echo "Tenant creado: {$space->id}\n";

// 3. Crear dominio para el tenant
$domain = $space->domains()->create([
    'domain' => 'empresa', // Solo el subdominio
]);

echo "Dominio creado: {$domain->domain}.enkiflow.test\n";

// 4. Crear base de datos y ejecutar migraciones
event(new \Stancl\Tenancy\Events\TenantCreated($space));

echo "Base de datos creada y migraciones ejecutadas\n";

// 5. Añadir el usuario como miembro del espacio
\DB::table('space_users')->insert([
    'tenant_id' => $space->id,
    'user_id' => $user->id,
    'role' => 'owner',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Usuario añadido como propietario del espacio\n";
echo "Acceso: http://{$domain->domain}.enkiflow.test\n";
```

#### Crear Tenant Mínimo

```php
// Versión simplificada para desarrollo/testing
$space = \App\Models\Space::create([
    'data' => ['plan' => 'free']
]);

$space->domains()->create(['domain' => 'test-' . \Str::random(5)]);
event(new \Stancl\Tenancy\Events\TenantCreated($space));

echo "Tenant de prueba: http://{$space->domains->first()->domain}.enkiflow.test\n";
```

### 1.2 Usando Comando Artisan Personalizado

Crear el comando:

```bash
php artisan make:command CreateTenant
```

```php
<?php
// app/Console/Commands/CreateTenant.php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Space;
use Illuminate\Console\Command;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create {name} {domain} {--owner-email=} {--plan=free}';
    protected $description = 'Create a new tenant with database and domain';

    public function handle()
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');
        $ownerEmail = $this->option('owner-email');
        $plan = $this->option('plan');

        // Crear usuario si se especifica
        $user = null;
        if ($ownerEmail) {
            $user = User::firstOrCreate(['email' => $ownerEmail], [
                'name' => $name . ' Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Crear tenant
        $space = Space::create([
            'name' => $name,
            'owner_id' => $user?->id,
            'data' => ['plan' => $plan],
        ]);

        // Crear dominio
        $space->domains()->create(['domain' => $domain]);

        // Crear base de datos
        event(new \Stancl\Tenancy\Events\TenantCreated($space));

        $this->info("Tenant creado exitosamente:");
        $this->table(['Propiedad', 'Valor'], [
            ['ID', $space->id],
            ['Nombre', $name],
            ['Dominio', "http://{$domain}.enkiflow.test"],
            ['Plan', $plan],
            ['Propietario', $user?->email ?? 'Sin propietario'],
        ]);
    }
}
```

Uso del comando:

```bash
php artisan tenant:create "Empresa Nueva" "nueva-empresa" --owner-email="admin@nuevaempresa.com" --plan="professional"
```

### 1.3 Propiedades del Tenant

#### Propiedades Obligatorias

- **ID**: Generado automáticamente (UUID)
- **data**: JSON con configuración mínima

#### Propiedades Recomendadas

```php
$tenantData = [
    'plan' => 'free|basic|professional|enterprise',
    'name' => 'Nombre de la empresa',
    'company_name' => 'Nombre legal de la empresa',
    'industry' => 'Industria/sector',
    'timezone' => 'America/Mexico_City',
    'locale' => 'es',
    'trial_ends_at' => '2025-06-01',
    'features' => [
        'time_tracking' => true,
        'invoicing' => false,
        'integrations' => true,
    ],
];
```

## 2. Acceder a Tenants Existentes

### 2.1 Configuración de Dominio Local

#### Verificar Laravel Herd

Laravel Herd maneja automáticamente dominios wildcard. Verificar configuración:

```bash
# Verificar que Herd está ejecutándose
herd status

# Listar sitios configurados
herd list
```

#### Verificar Resolución DNS

```bash
# Probar resolución de dominio
ping prueba.enkiflow.test

# Debería resolver a 127.0.0.1
nslookup prueba.enkiflow.test
```

#### Configurar Hosts (si es necesario)

Si Laravel Herd no maneja subdominios automáticamente:

```bash
# macOS/Linux
sudo echo "127.0.0.1   subdominio.enkiflow.test" >> /etc/hosts

# Windows (como administrador)
echo 127.0.0.1   subdominio.enkiflow.test >> C:\Windows\System32\drivers\etc\hosts
```

### 2.2 Listar Tenants Disponibles

```bash
# Usando comando integrado
php artisan tenants:list

# Usando Tinker
php artisan tinker
```

```php
// Ver todos los tenants
$spaces = \App\Models\Space::all();
foreach ($spaces as $space) {
    $domains = $space->domains->pluck('domain')->join(', ');
    echo "ID: {$space->id}\n";
    echo "Nombre: {$space->name}\n";
    echo "Dominios: {$domains}\n";
    echo "URL: http://{$space->domains->first()?->domain}.enkiflow.test\n";
    echo "---\n";
}

// Ver tenants con sus estadísticas
\App\Models\Space::with('domains', 'owner')->get()->map(function ($space) {
    return [
        'id' => $space->id,
        'name' => $space->name,
        'domains' => $space->domains->pluck('domain'),
        'owner' => $space->owner?->email,
        'plan' => $space->data['plan'] ?? 'unknown',
    ];
});
```

### 2.3 Validar Configuración de Tenant

```php
// En Tinker - validar tenant específico
$tenantId = 'test-space-682fa2483831f';
$space = \App\Models\Space::find($tenantId);

if (!$space) {
    echo "❌ Tenant no encontrado\n";
    exit;
}

echo "✅ Tenant encontrado: {$space->name}\n";

// Verificar dominios
$domains = $space->domains;
if ($domains->count() > 0) {
    echo "✅ Dominios configurados:\n";
    foreach ($domains as $domain) {
        echo "  - {$domain->domain}.enkiflow.test\n";
    }
} else {
    echo "❌ Sin dominios configurados\n";
}

// Verificar base de datos
$dbName = 'tenant' . str_replace('-', '', $space->id);
$exists = \DB::select("SELECT datname FROM pg_database WHERE datname = ?", [$dbName]);
echo $exists ? "✅ Base de datos existe: {$dbName}\n" : "❌ Base de datos no existe\n";

// Verificar propietario
$owner = $space->owner;
echo $owner ? "✅ Propietario: {$owner->email}\n" : "⚠️ Sin propietario asignado\n";
```

### 2.4 Acceso vía Navegador

```bash
# Acceso directo al tenant
http://subdominio.enkiflow.test

# Las rutas disponibles incluyen:
http://subdominio.enkiflow.test/dashboard     # Dashboard principal
http://subdominio.enkiflow.test/projects     # Gestión de proyectos
http://subdominio.enkiflow.test/tasks        # Gestión de tareas
http://subdominio.enkiflow.test/time         # Seguimiento de tiempo
```

## 3. Ejecutar Operaciones en Contexto de Tenant

### 3.1 Inicializar Contexto de Tenant

```php
// En Tinker
$space = \App\Models\Space::find('tenant-id');

// Método 1: Usando tenancy manager
tenancy()->initialize($space);

// Método 2: Usando el helper (alternativo)
app(\Stancl\Tenancy\TenancyManager::class)->initialize($space);

// Verificar inicialización
echo "Tenant activo: " . (tenant() ? tenant()->id : 'ninguno') . "\n";
echo "Base de datos: " . \DB::connection()->getDatabaseName() . "\n";

// Finalizar contexto de tenant
tenancy()->end();
```

### 3.2 Usando Comando tenancy:run

```bash
# Ejecutar comando en contexto de tenant específico
php artisan tenancy:run {tenant-id} --command="migrate:status"
php artisan tenancy:run {tenant-id} --command="db:seed"
php artisan tenancy:run {tenant-id} --command="cache:clear"

# Ejecutar comando personalizado
php artisan tenancy:run {tenant-id} --command="custom:command --option=value"

# Ejecutar Tinker en contexto de tenant
php artisan tenancy:run {tenant-id} --command="tinker"
```

### 3.3 Ejecutar Closure en Contexto

```php
// Ejecutar código en contexto de tenant
$space = \App\Models\Space::find('tenant-id');

tenancy()->runFor($space, function () {
    // Todo el código aquí se ejecuta en contexto del tenant
    $projectCount = \DB::table('projects')->count();
    echo "Proyectos en este tenant: {$projectCount}\n";
    
    // Crear datos de prueba
    \DB::table('projects')->insert([
        'name' => 'Proyecto de Prueba',
        'description' => 'Creado desde Tinker',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "Proyecto creado\n";
});
```

### 3.4 Migraciones Específicas de Tenant

```bash
# Ejecutar migraciones para tenant específico
php artisan tenants:migrate --tenants={tenant-id}

# Ejecutar migraciones para todos los tenants
php artisan tenants:migrate

# Rollback para tenant específico
php artisan tenants:rollback --tenants={tenant-id} --step=1

# Ver estado de migraciones
php artisan tenancy:run {tenant-id} --command="migrate:status"
```

## 4. Gestionar Datos Aislados por Tenant

### 4.1 Consultar Datos en Contexto de Tenant

```php
// En Tinker - trabajar con datos de tenant específico
$space = \App\Models\Space::find('tenant-id');

tenancy()->runFor($space, function () {
    // Consultar tablas del tenant
    $projects = \DB::table('projects')->get();
    $tasks = \DB::table('tasks')->get();
    $timeEntries = \DB::table('time_entries')->get();
    
    echo "Estadísticas del tenant:\n";
    echo "- Proyectos: " . $projects->count() . "\n";
    echo "- Tareas: " . $tasks->count() . "\n";
    echo "- Entradas de tiempo: " . $timeEntries->count() . "\n";
    
    // Consultas más complejas
    $activeProjects = \DB::table('projects')
        ->where('status', 'active')
        ->get();
    
    $completedTasks = \DB::table('tasks')
        ->join('task_states', 'tasks.task_state_id', '=', 'task_states.id')
        ->where('task_states.is_completed', true)
        ->count();
    
    echo "- Proyectos activos: " . $activeProjects->count() . "\n";
    echo "- Tareas completadas: {$completedTasks}\n";
});
```

### 4.2 Crear Datos de Prueba

```php
// Función helper para crear datos de prueba
function createTestData($tenantId) {
    $space = \App\Models\Space::find($tenantId);
    
    tenancy()->runFor($space, function () {
        // Crear proyecto
        $projectId = \DB::table('projects')->insertGetId([
            'name' => 'Proyecto Demo',
            'description' => 'Proyecto de demostración',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Crear estado de tarea por defecto
        $stateId = \DB::table('task_states')->insertGetId([
            'name' => 'To Do',
            'color' => '#e74c3c',
            'position' => 1,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Crear tareas
        for ($i = 1; $i <= 5; $i++) {
            \DB::table('tasks')->insert([
                'project_id' => $projectId,
                'task_state_id' => $stateId,
                'name' => "Tarea {$i}",
                'description' => "Descripción de la tarea {$i}",
                'priority' => 'medium',
                'position' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Crear categoría de tiempo
        $categoryId = \DB::table('time_categories')->insertGetId([
            'name' => 'Desarrollo',
            'color' => '#3498db',
            'is_billable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "✅ Datos de prueba creados:\n";
        echo "- 1 proyecto\n";
        echo "- 5 tareas\n";
        echo "- 1 categoría de tiempo\n";
    });
}

// Usar la función
createTestData('tenant-id');
```

### 4.3 Transferir Datos Entre Tenants

```php
// Copiar proyectos de un tenant a otro
function copyProjectsBetweenTenants($sourceTenantId, $targetTenantId) {
    $sourceSpace = \App\Models\Space::find($sourceTenantId);
    $targetSpace = \App\Models\Space::find($targetTenantId);
    
    $projects = [];
    
    // Obtener proyectos del tenant origen
    tenancy()->runFor($sourceSpace, function () use (&$projects) {
        $projects = \DB::table('projects')->get()->toArray();
    });
    
    // Insertar en tenant destino
    tenancy()->runFor($targetSpace, function () use ($projects) {
        foreach ($projects as $project) {
            unset($project->id); // Remover ID para auto-incremento
            $project->created_at = now();
            $project->updated_at = now();
            
            \DB::table('projects')->insert((array) $project);
        }
    });
    
    echo "✅ Copiados " . count($projects) . " proyectos\n";
}
```

### 4.4 Reportes de Datos por Tenant

```php
function generateTenantReport($tenantId) {
    $space = \App\Models\Space::find($tenantId);
    
    return tenancy()->runFor($space, function () use ($space) {
        $report = [
            'tenant_info' => [
                'id' => $space->id,
                'name' => $space->name,
                'plan' => $space->data['plan'] ?? 'unknown',
            ],
            'statistics' => [
                'projects' => \DB::table('projects')->count(),
                'active_projects' => \DB::table('projects')->where('status', 'active')->count(),
                'tasks' => \DB::table('tasks')->count(),
                'time_entries' => \DB::table('time_entries')->count(),
                'total_time' => \DB::table('time_entries')->sum('duration'),
            ],
            'recent_activity' => [
                'recent_projects' => \DB::table('projects')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->pluck('name'),
                'recent_tasks' => \DB::table('tasks')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->pluck('name'),
            ],
        ];
        
        return $report;
    });
}

// Generar reporte
$report = generateTenantReport('tenant-id');
print_r($report);
```

## 5. Escalar a Múltiples Tenants

### 5.1 Operaciones Masivas con runForMultiple

```php
// Ejecutar acción para todos los tenants
tenancy()->runForMultiple(null, function ($tenant) {
    echo "Procesando tenant: {$tenant->id}\n";
    
    // Limpiar logs antiguos
    \DB::table('activity_logs')
        ->where('created_at', '<', now()->subDays(30))
        ->delete();
        
    echo "- Logs limpiados\n";
});

// Ejecutar para tenants específicos
$tenants = \App\Models\Space::where('data->plan', 'professional')->get();
tenancy()->runForMultiple($tenants, function ($tenant) {
    // Activar características premium
    \DB::table('projects')->update(['premium_features' => true]);
    echo "Características premium activadas para {$tenant->id}\n";
});
```

### 5.2 Mantenimiento Masivo

```php
// Script de mantenimiento para todos los tenants
function runMaintenanceForAllTenants() {
    $tenants = \App\Models\Space::all();
    $processed = 0;
    $errors = 0;
    
    foreach ($tenants as $tenant) {
        try {
            tenancy()->runFor($tenant, function () use ($tenant) {
                // Optimizar tablas
                \DB::statement('VACUUM ANALYZE projects');
                \DB::statement('VACUUM ANALYZE tasks');
                \DB::statement('VACUUM ANALYZE time_entries');
                
                // Actualizar estadísticas
                $projectCount = \DB::table('projects')->count();
                $taskCount = \DB::table('tasks')->count();
                
                // Guardar estadísticas en tabla central (cambiar a conexión central)
                \DB::connection('central')->table('tenant_stats')->updateOrInsert(
                    ['tenant_id' => $tenant->id],
                    [
                        'project_count' => $projectCount,
                        'task_count' => $taskCount,
                        'last_maintenance' => now(),
                        'updated_at' => now(),
                    ]
                );
                
                echo "✅ Mantenimiento completado para {$tenant->id}\n";
            });
            
            $processed++;
        } catch (\Exception $e) {
            echo "❌ Error en tenant {$tenant->id}: {$e->getMessage()}\n";
            $errors++;
        }
    }
    
    echo "\n📊 Resumen del mantenimiento:\n";
    echo "- Tenants procesados: {$processed}\n";
    echo "- Errores: {$errors}\n";
}

runMaintenanceForAllTenants();
```

### 5.3 Monitoreo de Rendimiento

```php
// Función para monitorear uso de recursos por tenant
function monitorTenantUsage() {
    $report = [];
    
    \App\Models\Space::chunk(10, function ($tenants) use (&$report) {
        foreach ($tenants as $tenant) {
            try {
                $usage = tenancy()->runFor($tenant, function () {
                    return [
                        'database_size' => \DB::selectOne("
                            SELECT pg_size_pretty(pg_database_size(?)) as size
                        ", [\DB::connection()->getDatabaseName()])->size,
                        'table_counts' => [
                            'projects' => \DB::table('projects')->count(),
                            'tasks' => \DB::table('tasks')->count(),
                            'time_entries' => \DB::table('time_entries')->count(),
                        ],
                        'last_activity' => \DB::table('time_entries')
                            ->max('created_at'),
                    ];
                });
                
                $report[$tenant->id] = [
                    'name' => $tenant->name,
                    'plan' => $tenant->data['plan'] ?? 'unknown',
                    'usage' => $usage,
                ];
            } catch (\Exception $e) {
                $report[$tenant->id] = ['error' => $e->getMessage()];
            }
        }
    });
    
    return $report;
}

// Ejecutar monitoreo
$usage = monitorTenantUsage();
foreach ($usage as $tenantId => $data) {
    echo "Tenant: {$tenantId}\n";
    if (isset($data['error'])) {
        echo "  ❌ Error: {$data['error']}\n";
    } else {
        echo "  Plan: {$data['plan']}\n";
        echo "  Tamaño BD: {$data['usage']['database_size']}\n";
        echo "  Proyectos: {$data['usage']['table_counts']['projects']}\n";
        echo "  Última actividad: {$data['usage']['last_activity']}\n";
    }
    echo "---\n";
}
```

### 5.4 Buenas Prácticas para Escalabilidad

#### Procesamiento por Lotes

```php
// Procesar tenants en lotes para evitar sobrecarga de memoria
function processTenantsBatch($batchSize = 10) {
    \App\Models\Space::chunk($batchSize, function ($tenants) {
        foreach ($tenants as $tenant) {
            tenancy()->runFor($tenant, function () {
                // Operación específica
            });
        }
        
        // Pausa entre lotes para reducir carga
        sleep(1);
    });
}
```

#### Manejo de Conexiones

```php
// Liberar conexiones después de cada tenant para evitar memory leaks
function processTenantsWithCleanup() {
    $tenants = \App\Models\Space::all();
    
    foreach ($tenants as $tenant) {
        tenancy()->runFor($tenant, function () {
            // Operaciones del tenant
        });
        
        // Limpiar conexiones
        \DB::purge('tenant');
        gc_collect_cycles();
    }
}
```

#### Queue Jobs para Operaciones Pesadas

```php
// Crear job para operaciones que requieren mucho tiempo
class ProcessTenantMaintenanceJob implements ShouldQueue
{
    public function handle($tenantId)
    {
        $tenant = \App\Models\Space::find($tenantId);
        
        tenancy()->runFor($tenant, function () {
            // Operaciones pesadas aquí
        });
    }
}

// Encolar jobs para todos los tenants
foreach (\App\Models\Space::all() as $tenant) {
    ProcessTenantMaintenanceJob::dispatch($tenant->id);
}
```

## Comandos de Referencia Rápida

### Gestión de Tenants

```bash
# Listar tenants
php artisan tenants:list

# Crear tenant (comando personalizado)
php artisan tenant:create "Nombre" "dominio" --owner-email="email@test.com"

# Ejecutar en contexto de tenant
php artisan tenancy:run {tenant-id} --command="comando"

# Migraciones
php artisan tenants:migrate --tenants={tenant-id}
php artisan tenants:migrate  # Todos los tenants
```

### Tinker Utilities

```php
// Listar todos los tenants con dominios
\App\Models\Space::with('domains')->get()->map(fn($s) => [
    'id' => $s->id, 
    'name' => $s->name, 
    'domains' => $s->domains->pluck('domain')
]);

// Inicializar tenant
tenancy()->initialize(\App\Models\Space::find('tenant-id'));

// Verificar tenant actual
tenant()?->id;

// Finalizar contexto
tenancy()->end();

// Stats rápidas de tenant
tenancy()->runFor($tenant, fn() => [
    'projects' => \DB::table('projects')->count(),
    'tasks' => \DB::table('tasks')->count()
]);
```

## Solución de Problemas

### Error: "Tenant could not be identified"

1. Verificar que el dominio existe en la tabla `domains`
2. Verificar que el tenant existe en la tabla `tenants`
3. Limpiar cache: `php artisan cache:clear`

### Error: "Database does not exist"

1. Verificar nombre de la base de datos: `tenant{tenant-id-sin-guiones}`
2. Crear manualmente: `CREATE DATABASE "tenant{id}"`
3. Ejecutar migraciones: `php artisan tenants:migrate --tenants={id}`

### Problemas de Resolución DNS

1. Verificar Laravel Herd: `herd status`
2. Verificar hosts file: `cat /etc/hosts | grep enkiflow`
3. Probar ping: `ping subdominio.enkiflow.test`

### Lentitud en Operaciones Masivas

1. Usar procesamiento por lotes
2. Añadir pausas entre operaciones
3. Usar queue jobs para operaciones pesadas
4. Monitorear uso de memoria con `memory_get_usage()`

---

**Nota**: Este documento asume un entorno de desarrollo local con Laravel Herd. Para producción, ajustar configuraciones de DNS, SSL y performance según el entorno específico.