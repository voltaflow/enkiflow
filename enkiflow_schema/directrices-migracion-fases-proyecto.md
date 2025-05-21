# Directrices de migración para cada fase del proyecto

## Tabla de Contenidos

- [Introducción](#introducción)
- [Principios Generales de Migración](#principios-generales-de-migración)
- [Herramientas y Tecnologías](#herramientas-y-tecnologías)
- [Estándares para la Creación de Migraciones](#estándares-para-la-creación-de-migraciones)
- [Directrices por Fase del Proyecto](#directrices-por-fase-del-proyecto)
  - [Fase 1: Preparación MVP (Semanas 1-2)](#fase-1-preparación-mvp-semanas-1-2)
  - [Fase 2: Desarrollo Core MVP (Semanas 3-6)](#fase-2-desarrollo-core-mvp-semanas-3-6)
  - [Fase 3: Refinamiento MVP y Preparación Alpha (Semanas 7-8)](#fase-3-refinamiento-mvp-y-preparación-alpha-semanas-7-8)
  - [Fase 4: Lanzamiento Alpha y Desarrollo Beta (Semanas 9-12)](#fase-4-lanzamiento-alpha-y-desarrollo-beta-semanas-9-12)
  - [Fase 5: MVP Comercial (Semanas 13-16)](#fase-5-mvp-comercial-semanas-13-16)
  - [Fase 6: Lanzamiento al Mercado (Semanas 17-20)](#fase-6-lanzamiento-al-mercado-semanas-17-20)
- [Políticas de Versionado y Control de Cambios](#políticas-de-versionado-y-control-de-cambios)
- [Procedimientos de Rollback](#procedimientos-de-rollback)
- [Consideraciones de Rendimiento](#consideraciones-de-rendimiento)
- [Monitoreo y Validación](#monitoreo-y-validación)
- [Casos de Uso y Ejemplos](#casos-de-uso-y-ejemplos)

## Introducción

Las migraciones de base de datos son fundamentales para el éxito de EnkiFlow, especialmente dado su diseño multi-tenant y la complejidad de sus funcionalidades. Esta guía proporciona directrices detalladas para gestionar las migraciones durante todo el ciclo de vida del proyecto.

### Stack Tecnológico de EnkiFlow

- **Framework**: Laravel 12 con PHP 8.4
- **Base de Datos**: PostgreSQL 16+
- **Arquitectura**: Multi-tenant con stancl/tenancy v3.x
- **Frontend**: Inertia.js con React 18 y TypeScript
- **Autenticación**: Laravel Fortify con soporte OAuth
- **Pagos**: Laravel Cashier (Stripe)
- **Construcción**: Vite 5

### Arquitectura Multi-Tenant y Migraciones

EnkiFlow utiliza una arquitectura multi-tenant donde:

- **Base de datos central**: Almacena usuarios, tenants, dominios y configuraciones globales
- **Bases de datos de tenant**: Cada tenant tiene su propia base de datos para proyectos, tareas, tiempo y reportes
- **Migraciones separadas**: Se requieren migraciones independientes para tablas centrales y específicas de tenant

## Principios Generales de Migración

### Filosofía de Migraciones en EnkiFlow

1. **Seguridad primero**: Toda migración debe incluir respaldos y validaciones
2. **Reversibilidad**: Todas las migraciones deben ser reversibles
3. **Atomicidad**: Las migraciones deben ser atómicas o dividirse en pasos seguros
4. **Compatibilidad**: Mantener compatibilidad hacia atrás durante transiciones
5. **Documentación**: Cada migración debe ser auto-documentada

### Consideraciones de Seguridad

```php
<?php

// Ejemplo de migración segura con validaciones
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSecureProjectsTable extends Migration
{
    public function up()
    {
        // Verificar que la tabla no existe
        if (Schema::hasTable('projects')) {
            throw new Exception('La tabla projects ya existe');
        }

        // Crear tabla con transacción
        DB::transaction(function () {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id')->index();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('budget', 10, 2)->nullable();
                $table->enum('budget_type', ['fixed', 'hourly'])->nullable();
                $table->enum('status', ['active', 'completed', 'on_hold'])->default('active');
                $table->date('start_date')->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                // Índices para rendimiento
                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'due_date']);
            });
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
}
```

### Estrategias para Minimizar Tiempo de Inactividad

1. **Migraciones en línea**: Usar ADD COLUMN en lugar de ALTER TABLE cuando sea posible
2. **Migraciones por lotes**: Procesar grandes datasets en chunks
3. **Ventanas de mantenimiento**: Planificar migraciones complejas fuera de horas pico
4. **Blue-Green Deployments**: Para migraciones críticas

## Herramientas y Tecnologías

### Laravel Migrations en Laravel 12

Laravel 12 introduce mejoras significativas en el sistema de migraciones:

```php
<?php

// Nuevo en Laravel 12: Soporte mejorado para PostgreSQL
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvancedTimeEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('time_category_id')->nullable()->constrained()->nullOnDelete();
            
            // Campos específicos de PostgreSQL
            $table->text('description')->nullable();
            $table->timestampTz('start_time');
            $table->timestampTz('end_time')->nullable();
            $table->integer('duration')->nullable(); // En segundos
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_running')->default(false);
            
            // Campos JSON para metadata
            $table->jsonb('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Índices específicos de PostgreSQL
            $table->index(['tenant_id', 'user_id', 'start_time']);
            $table->index(['tenant_id', 'is_running']);
            $table->index('start_time');
            
            // Índice parcial para entradas en ejecución
            $table->rawIndex('(tenant_id, user_id) WHERE is_running = true', 'running_entries_idx');
            
            // Índice GIN para búsquedas en JSON
            $table->rawIndex('metadata gin_trgm_ops', 'time_entries_metadata_gin_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('time_entries');
    }
}
```

### Configuración Multi-Tenant con stancl/tenancy

```php
<?php

// config/tenancy.php - Configuración para migraciones
return [
    'migration_parameters' => [
        '--force' => true,
        '--realpath' => true,
    ],
    
    'seeder_parameters' => [
        '--force' => true,
    ],
    
    // Configuración específica para PostgreSQL
    'database' => [
        'drivers' => ['pgsql'],
        'suffix' => '', // Sin sufijo para bases de datos de tenant
        'prefix' => 'tenant_',
        'template_tenant_connection' => 'tenant_template',
    ],
];
```

## Estándares para la Creación de Migraciones

### Nomenclatura de Archivos

```
// Formato: YYYY_MM_DD_HHMMSS_descripcion_table.php
2025_01_15_100000_create_projects_table.php
2025_01_15_100001_add_budget_fields_to_projects_table.php
2025_01_15_100002_create_tasks_table.php
2025_01_15_100003_add_polymorphic_indexes_to_taggables_table.php
```

### Estructura y Organización

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTaggablesTable extends Migration
{
    /**
     * Crear tabla polimórfica para etiquetas
     * 
     * Esta migración crea la tabla intermedia para relaciones polimórficas
     * entre tags y entidades etiquetables (projects, tasks, etc.)
     */
    public function up()
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable'); // Crea taggable_id y taggable_type
            $table->timestamps();

            // Índices para rendimiento en relaciones polimórficas
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
            $table->index(['taggable_id', 'taggable_type']);
        });
    }

    /**
     * Reversar la migración
     */
    public function down()
    {
        Schema::dropIfExists('taggables');
    }
}
```

### Manejo de Campos JSON y Tipos Complejos

```php
<?php

class CreateUserProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('avatar')->nullable();
            $table->string('job_title')->nullable();
            $table->string('phone')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('locale')->default('en');
            $table->enum('theme_preference', ['light', 'dark', 'auto'])->default('light');
            
            // Campo JSON para preferencias de notificación
            $table->jsonb('notification_preferences')->default('{}');
            
            $table->timestamps();

            // Índice para búsquedas en campos JSON
            $table->rawIndex(
                "((notification_preferences->>'email_enabled')::boolean)", 
                'user_profiles_email_notifications_idx'
            );
        });

        // Insertar valores por defecto para preferencias de notificación
        DB::statement("
            ALTER TABLE user_profiles 
            ALTER COLUMN notification_preferences 
            SET DEFAULT '{\"email_enabled\": true, \"push_enabled\": true, \"digest_frequency\": \"daily\"}'::jsonb
        ");
    }

    public function down()
    {
        Schema::dropIfExists('user_profiles');
    }
}
```

### Consideraciones para PostgreSQL

```php
<?php

class CreateAdvancedSearchIndexes extends Migration
{
    public function up()
    {
        // Extensiones de PostgreSQL para búsqueda avanzada
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');

        // Índices GIN para búsqueda de texto completo
        DB::statement('
            CREATE INDEX projects_search_gin_idx ON projects 
            USING gin(to_tsvector(\'spanish\', name || \' \' || coalesce(description, \'\')))
        ');

        DB::statement('
            CREATE INDEX tasks_search_gin_idx ON tasks 
            USING gin(to_tsvector(\'spanish\', name || \' \' || coalesce(description, \'\')))
        ');

        // Índice para búsqueda fuzzy en nombres
        DB::statement('
            CREATE INDEX projects_name_trgm_idx ON projects 
            USING gin(name gin_trgm_ops)
        ');
    }

    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS projects_search_gin_idx');
        DB::statement('DROP INDEX IF EXISTS tasks_search_gin_idx');
        DB::statement('DROP INDEX IF EXISTS projects_name_trgm_idx');
    }
}
```

## Directrices por Fase del Proyecto

### Fase 1: Preparación MVP (Semanas 1-2)

**Objetivo**: Establecer la base sólida para el sistema multi-tenant y las funcionalidades core.

#### Migraciones Iniciales Multi-Tenant

```php
<?php

// 1. Migración base para tenancy
class CreateTenancyTables extends Migration
{
    public function up()
    {
        // Tabla de tenants
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->enum('plan', ['free', 'basic', 'professional', 'enterprise'])->default('free');
            $table->timestamp('trial_ends_at')->nullable();
            $table->jsonb('data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('plan');
            $table->index('trial_ends_at');
        });

        // Tabla de dominios
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'is_primary']);
        });

        // Tabla de usuarios en espacios
        Schema::create('space_users', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'member', 'guest']);
            $table->jsonb('permissions')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('space_users');
        Schema::dropIfExists('domains');
        Schema::dropIfExists('tenants');
    }
}
```

#### Configuración de Migraciones de Tenant

```php
<?php

// Migración para crear la estructura base de cada tenant
class CreateTenantProjectsTable extends Migration
{
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->enum('budget_type', ['fixed', 'hourly'])->nullable();
            $table->enum('status', ['active', 'completed', 'on_hold'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices importantes para rendimiento
            $table->index('status');
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
}
```

### Fase 2: Desarrollo Core MVP (Semanas 3-6)

**Objetivo**: Implementar las funcionalidades principales de gestión de proyectos y tareas.

#### Migraciones para Funcionalidades Core

```php
<?php

// Migración para sistema de estados de tareas personalizables
class CreateTaskStatesTable extends Migration
{
    public function up()
    {
        Schema::create('task_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->default('#3498db'); // Hex color
            $table->integer('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index(['position', 'is_default']);
        });

        // Insertar estados por defecto
        DB::table('task_states')->insert([
            ['name' => 'To Do', 'color' => '#e74c3c', 'position' => 1, 'is_default' => true],
            ['name' => 'In Progress', 'color' => '#f39c12', 'position' => 2],
            ['name' => 'Review', 'color' => '#9b59b6', 'position' => 3],
            ['name' => 'Done', 'color' => '#27ae60', 'position' => 4, 'is_completed' => true],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('task_states');
    }
}
```

```php
<?php

// Migración para tareas con jerarquía y asignaciones
class CreateTasksTable extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('task_state_id')->constrained();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->integer('estimated_time')->nullable(); // En minutos
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->jsonb('recurrence_pattern')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para rendimiento y consultas comunes
            $table->index(['project_id', 'task_state_id']);
            $table->index(['parent_id', 'position']);
            $table->index(['due_date', 'priority']);
            $table->index('is_recurring');
        });

        // Tabla de asignaciones de tareas
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('tasks');
    }
}
```

#### Migración para Sistema de Etiquetas Polimórfico

```php
<?php

class CreateTaggingSystem extends Migration
{
    public function up()
    {
        // Tabla de etiquetas
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->default('#3498db');
            $table->timestamps();

            $table->unique('name');
            $table->index('color');
        });

        // Tabla polimórfica para etiquetas
        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->timestamps();

            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
            $table->index(['taggable_id', 'taggable_type']);
        });

        // Tabla de comentarios polimórfica
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable');
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_id', 'commentable_type']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
}
```

### Fase 3: Refinamiento MVP y Preparación Alpha (Semanas 7-8)

**Objetivo**: Optimizar rendimiento y preparar para usuarios alpha.

#### Migraciones de Optimización

```php
<?php

class OptimizeExistingTables extends Migration
{
    public function up()
    {
        // Agregar índices de rendimiento basados en patrones de uso
        Schema::table('projects', function (Blueprint $table) {
            // Índice compuesto para dashboard de proyectos
            $table->index(['status', 'due_date', 'created_at']);
            
            // Índice para búsqueda de texto
            $table->index(['name', 'status']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            // Índice para vista de tareas por usuario
            $table->rawIndex(
                '(SELECT user_id FROM task_assignees WHERE task_assignees.task_id = tasks.id LIMIT 1), task_state_id, due_date',
                'tasks_assignee_state_due_idx'
            );
        });

        // Crear vistas materializadas para reportes comunes
        DB::statement('
            CREATE MATERIALIZED VIEW project_summary AS
            SELECT 
                p.id,
                p.name,
                p.status,
                COUNT(t.id) as total_tasks,
                COUNT(CASE WHEN ts.is_completed THEN 1 END) as completed_tasks,
                SUM(te.duration) as total_time
            FROM projects p
            LEFT JOIN tasks t ON p.id = t.project_id
            LEFT JOIN task_states ts ON t.task_state_id = ts.id
            LEFT JOIN time_entries te ON t.id = te.task_id
            GROUP BY p.id, p.name, p.status
        ');

        // Crear índice en la vista materializada
        DB::statement('CREATE INDEX project_summary_status_idx ON project_summary (status)');
    }

    public function down()
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS project_summary');
        
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_assignee_state_due_idx');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status', 'due_date', 'created_at']);
            $table->dropIndex(['name', 'status']);
        });
    }
}
```

### Fase 4: Lanzamiento Alpha y Desarrollo Beta (Semanas 9-12)

**Objetivo**: Gestionar migraciones con usuarios reales y datos de producción.

#### Estrategias para Migraciones con Impacto Mínimo

```php
<?php

class AddAdvancedTimeTracking extends Migration
{
    public function up()
    {
        // Agregar columnas de manera no destructiva
        Schema::table('time_entries', function (Blueprint $table) {
            $table->jsonb('metadata')->nullable()->after('is_running');
            $table->boolean('is_automatic')->default(false)->after('is_running');
            $table->string('source')->default('manual')->after('is_automatic');
        });

        // Crear tabla de logs de actividad de manera independiente
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('time_entry_id')->constrained()->cascadeOnDelete();
            $table->string('activity_type');
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('timestamp');
            $table->timestamps();

            $table->index(['user_id', 'timestamp']);
            $table->index(['time_entry_id', 'activity_type']);
        });

        // Migrar datos existentes en lotes para evitar timeouts
        $this->migrateExistingTimeEntries();
    }

    private function migrateExistingTimeEntries()
    {
        DB::table('time_entries')
            ->whereNull('metadata')
            ->chunkById(1000, function ($timeEntries) {
                foreach ($timeEntries as $entry) {
                    DB::table('time_entries')
                        ->where('id', $entry->id)
                        ->update([
                            'metadata' => json_encode(['migrated_at' => now()]),
                            'source' => 'manual'
                        ]);
                }
            });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
        
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn(['metadata', 'is_automatic', 'source']);
        });
    }
}
```

#### Procedimientos de Rollback para Producción

```php
<?php

class SafeProductionMigration extends Migration
{
    public function up()
    {
        // Crear tabla temporal para validación
        Schema::create('temp_validation_table', function (Blueprint $table) {
            $table->id();
            $table->string('validation_key');
            $table->jsonb('validation_data');
            $table->timestamps();
        });

        try {
            // Realizar migración principal
            $this->executeMainMigration();
            
            // Validar integridad de datos
            $this->validateDataIntegrity();
            
            // Limpiar tabla temporal
            Schema::dropIfExists('temp_validation_table');
            
        } catch (Exception $e) {
            // Rollback automático en caso de error
            $this->rollbackSafely();
            throw $e;
        }
    }

    private function executeMainMigration()
    {
        DB::transaction(function () {
            // Operaciones de migración aquí
            Schema::table('projects', function (Blueprint $table) {
                $table->decimal('hourly_rate', 8, 2)->nullable()->after('budget');
            });
        });
    }

    private function validateDataIntegrity()
    {
        $projectCount = DB::table('projects')->count();
        $validationCount = DB::table('temp_validation_table')
            ->where('validation_key', 'project_count')
            ->value('validation_data->count');

        if ($projectCount !== $validationCount) {
            throw new Exception('Data integrity validation failed');
        }
    }

    private function rollbackSafely()
    {
        Schema::dropIfExists('temp_validation_table');
        
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'hourly_rate')) {
                $table->dropColumn('hourly_rate');
            }
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('hourly_rate');
        });
    }
}
```

### Fase 5: MVP Comercial (Semanas 13-16)

**Objetivo**: Implementar funcionalidades avanzadas manteniendo estabilidad.

#### Migraciones para Facturación e Integraciones

```php
<?php

class CreateBillingSystem extends Migration
{
    public function up()
    {
        // Tabla de clientes
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'is_active']);
            $table->index('email');
        });

        // Plantillas de factura
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->text('content'); // HTML template
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Facturas
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('invoice_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_date']);
            $table->index(['client_id', 'status']);
        });

        // Items de factura
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->decimal('quantity', 8, 2);
            $table->decimal('unit_price', 8, 2);
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        // Actualizar tabla de proyectos para referenciar clientes
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });

        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_templates');
        Schema::dropIfExists('clients');
    }
}
```

### Fase 6: Lanzamiento al Mercado (Semanas 17-20)

**Objetivo**: Optimizar para escala y preparar para crecimiento.

#### Migraciones para Escalabilidad

```php
<?php

class OptimizeForScale extends Migration
{
    public function up()
    {
        // Particionamiento de tabla de time_entries por fecha
        DB::statement('
            CREATE TABLE time_entries_partitioned (
                LIKE time_entries INCLUDING ALL
            ) PARTITION BY RANGE (start_time)
        ');

        // Crear particiones para los próximos 12 meses
        $startDate = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $partitionStart = $startDate->copy()->addMonths($i);
            $partitionEnd = $partitionStart->copy()->addMonth();
            $partitionName = 'time_entries_' . $partitionStart->format('Y_m');

            DB::statement("
                CREATE TABLE {$partitionName} PARTITION OF time_entries_partitioned
                FOR VALUES FROM ('{$partitionStart->toDateString()}') TO ('{$partitionEnd->toDateString()}')
            ");
        }

        // Migrar datos existentes
        DB::statement('INSERT INTO time_entries_partitioned SELECT * FROM time_entries');

        // Rename tables
        DB::statement('ALTER TABLE time_entries RENAME TO time_entries_old');
        DB::statement('ALTER TABLE time_entries_partitioned RENAME TO time_entries');

        // Crear función para mantenimiento automático de particiones
        DB::statement('
            CREATE OR REPLACE FUNCTION maintain_time_entries_partitions()
            RETURNS void AS $$
            DECLARE
                partition_date date;
                partition_name text;
                start_date text;
                end_date text;
            BEGIN
                partition_date := date_trunc(\'month\', CURRENT_DATE + interval \'1 month\');
                partition_name := \'time_entries_\' || to_char(partition_date, \'YYYY_MM\');
                start_date := partition_date::text;
                end_date := (partition_date + interval \'1 month\')::text;
                
                IF NOT EXISTS (SELECT 1 FROM pg_tables WHERE tablename = partition_name) THEN
                    EXECUTE format(\'CREATE TABLE %I PARTITION OF time_entries FOR VALUES FROM (%L) TO (%L)\',
                                 partition_name, start_date, end_date);
                END IF;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Crear job para mantenimiento mensual
        DB::statement('
            SELECT cron.schedule(\'maintain-partitions\', \'0 0 1 * *\', \'SELECT maintain_time_entries_partitions()\')
        ');
    }

    public function down()
    {
        // Eliminar job programado
        DB::statement('SELECT cron.unschedule(\'maintain-partitions\')');
        
        // Eliminar función
        DB::statement('DROP FUNCTION IF EXISTS maintain_time_entries_partitions()');
        
        // Restaurar tabla original
        DB::statement('ALTER TABLE time_entries RENAME TO time_entries_partitioned');
        DB::statement('ALTER TABLE time_entries_old RENAME TO time_entries');
        
        // Limpiar particiones
        DB::statement('DROP TABLE time_entries_partitioned CASCADE');
    }
}
```

## Políticas de Versionado y Control de Cambios

### Estrategia de Versionado

```bash
# Estructura de branches para migraciones
main/
├── release/v1.0.0
├── release/v1.1.0
├── hotfix/critical-migration-fix
└── feature/new-billing-system

# Nomenclatura de tags para migraciones
v1.0.0-migration-001  # Primera migración de la versión 1.0.0
v1.0.0-migration-002  # Segunda migración de la versión 1.0.0
v1.1.0-migration-001  # Primera migración de la versión 1.1.0
```

### Integración con Git

```php
<?php

// Comando personalizado para gestionar migraciones con Git
class MigrationVersionCommand extends Command
{
    protected $signature = 'migration:version {action} {--tag=}';
    
    public function handle()
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'tag':
                $this->tagMigrations();
                break;
            case 'rollback-to-tag':
                $this->rollbackToTag();
                break;
        }
    }
    
    private function tagMigrations()
    {
        $tag = $this->option('tag') ?: $this->ask('Enter migration tag');
        
        // Obtener migraciones pendientes
        $migrations = $this->getMigrationFiles();
        
        // Crear commit con migraciones
        exec("git add database/migrations/");
        exec("git commit -m 'Migrations for {$tag}'");
        exec("git tag {$tag}");
        
        $this->info("Tagged migrations with {$tag}");
    }
}
```

## Procedimientos de Rollback

### Rollback Seguro en Producción

```php
<?php

class SafeRollbackCommand extends Command
{
    protected $signature = 'migration:safe-rollback {steps=1} {--dry-run}';
    
    public function handle()
    {
        $steps = $this->argument('steps');
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }
        
        // Crear backup antes del rollback
        $this->createBackup();
        
        try {
            // Ejecutar rollback
            if (!$dryRun) {
                Artisan::call('migrate:rollback', ['--step' => $steps]);
            }
            
            $this->info("Successfully rolled back {$steps} migrations");
            
        } catch (Exception $e) {
            $this->error('Rollback failed: ' . $e->getMessage());
            
            if (!$dryRun) {
                $this->restoreBackup();
            }
        }
    }
    
    private function createBackup()
    {
        $timestamp = now()->format('Y_m_d_H_i_s');
        $command = "pg_dump " . config('database.connections.pgsql.database') . 
                   " > storage/backups/backup_{$timestamp}.sql";
        
        exec($command);
        $this->info("Backup created: backup_{$timestamp}.sql");
    }
}
```

## Consideraciones de Rendimiento

### Migraciones por Lotes

```php
<?php

class LargeDataMigration extends Migration
{
    public function up()
    {
        // Migrar datos en lotes para evitar timeouts
        DB::table('time_entries')
            ->where('migrated', false)
            ->orderBy('id')
            ->chunk(1000, function ($timeEntries) {
                foreach ($timeEntries as $entry) {
                    // Procesar cada entrada
                    $this->processTimeEntry($entry);
                }
                
                // Marcar como procesado
                DB::table('time_entries')
                    ->whereIn('id', $timeEntries->pluck('id'))
                    ->update(['migrated' => true]);
                
                // Pausa para evitar sobrecarga
                usleep(100000); // 100ms
            });
    }
    
    private function processTimeEntry($entry)
    {
        // Lógica de procesamiento
        if ($entry->end_time && $entry->start_time) {
            $duration = Carbon::parse($entry->end_time)
                ->diffInSeconds(Carbon::parse($entry->start_time));
            
            DB::table('time_entries')
                ->where('id', $entry->id)
                ->update(['duration' => $duration]);
        }
    }
}
```

### Optimizaciones Específicas para PostgreSQL

```sql
-- Configuración para migraciones grandes
SET maintenance_work_mem = '1GB';
SET max_parallel_workers = 8;
SET max_parallel_workers_per_gather = 4;

-- Índices concurrentes para evitar bloqueos
CREATE INDEX CONCURRENTLY idx_projects_status_date ON projects (status, created_at);

-- Vacuum y analyze después de migraciones grandes
VACUUM ANALYZE projects;
VACUUM ANALYZE tasks;
VACUUM ANALYZE time_entries;
```

## Monitoreo y Validación

### Herramientas de Monitoreo

```php
<?php

class MigrationMonitor
{
    public function monitorMigration(string $migrationName)
    {
        $startTime = microtime(true);
        $initialMemory = memory_get_usage(true);
        
        try {
            // Ejecutar migración
            Artisan::call('migrate', ['--path' => $migrationName]);
            
            $endTime = microtime(true);
            $finalMemory = memory_get_usage(true);
            
            // Log de rendimiento
            Log::info('Migration completed', [
                'migration' => $migrationName,
                'duration' => $endTime - $startTime,
                'memory_used' => $finalMemory - $initialMemory,
                'peak_memory' => memory_get_peak_usage(true)
            ]);
            
        } catch (Exception $e) {
            Log::error('Migration failed', [
                'migration' => $migrationName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
```

### Validación de Integridad Post-Migración

```php
<?php

class MigrationValidator
{
    public function validateMigration(string $migrationName)
    {
        $checks = [
            'validateTableStructure',
            'validateDataIntegrity',
            'validateIndexes',
            'validateConstraints'
        ];
        
        foreach ($checks as $check) {
            if (!$this->$check()) {
                throw new Exception("Validation failed: {$check}");
            }
        }
        
        return true;
    }
    
    private function validateTableStructure()
    {
        // Verificar que todas las tablas esperadas existen
        $expectedTables = ['projects', 'tasks', 'time_entries', 'users'];
        
        foreach ($expectedTables as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function validateDataIntegrity()
    {
        // Verificar integridad referencial
        $orphanTasks = DB::table('tasks')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->whereNotNull('tasks.project_id')
            ->whereNull('projects.id')
            ->count();
            
        return $orphanTasks === 0;
    }
}
```

## Casos de Uso y Ejemplos

### Migración Compleja: Reestructuración de Estados de Tareas

```php
<?php

class RestructureTaskStates extends Migration
{
    public function up()
    {
        // Paso 1: Crear nueva estructura
        Schema::create('task_states_new', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#3498db');
            $table->integer('position')->default(0);
            $table->enum('type', ['todo', 'doing', 'done'])->default('todo');
            $table->boolean('is_default')->default(false);
            $table->jsonb('settings')->nullable();
            $table->timestamps();
        });
        
        // Paso 2: Migrar datos existentes con mapeo
        $this->migrateExistingStates();
        
        // Paso 3: Actualizar referencias en tareas
        $this->updateTaskReferences();
        
        // Paso 4: Validar migración
        $this->validateMigration();
        
        // Paso 5: Intercambiar tablas
        Schema::rename('task_states', 'task_states_old');
        Schema::rename('task_states_new', 'task_states');
        
        // Paso 6: Limpiar tabla antigua después de validación
        // Schema::dropIfExists('task_states_old');
    }
    
    private function migrateExistingStates()
    {
        $stateMapping = [
            'pending' => ['name' => 'To Do', 'type' => 'todo', 'slug' => 'todo'],
            'in_progress' => ['name' => 'In Progress', 'type' => 'doing', 'slug' => 'doing'],
            'completed' => ['name' => 'Done', 'type' => 'done', 'slug' => 'done'],
        ];
        
        DB::table('task_states')->chunk(100, function ($states) use ($stateMapping) {
            foreach ($states as $state) {
                $newState = $stateMapping[$state->name] ?? [
                    'name' => $state->name,
                    'type' => 'todo',
                    'slug' => Str::slug($state->name)
                ];
                
                DB::table('task_states_new')->insert([
                    'id' => $state->id,
                    'name' => $newState['name'],
                    'slug' => $newState['slug'],
                    'color' => $state->color,
                    'position' => $state->position,
                    'type' => $newState['type'],
                    'is_default' => $state->is_default,
                    'settings' => json_encode(['legacy_name' => $state->name]),
                    'created_at' => $state->created_at,
                    'updated_at' => now(),
                ]);
            }
        });
    }
    
    private function updateTaskReferences()
    {
        // Actualizar foreign keys en tareas
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['task_state_id']);
        });
        
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('task_state_id')->references('id')->on('task_states_new');
        });
    }
    
    private function validateMigration()
    {
        $originalCount = DB::table('task_states')->count();
        $newCount = DB::table('task_states_new')->count();
        
        if ($originalCount !== $newCount) {
            throw new Exception('State migration validation failed: count mismatch');
        }
        
        $tasksWithInvalidStates = DB::table('tasks')
            ->leftJoin('task_states_new', 'tasks.task_state_id', '=', 'task_states_new.id')
            ->whereNull('task_states_new.id')
            ->count();
            
        if ($tasksWithInvalidStates > 0) {
            throw new Exception('Task state references validation failed');
        }
    }
    
    public function down()
    {
        // Rollback process
        if (Schema::hasTable('task_states_old')) {
            Schema::dropIfExists('task_states');
            Schema::rename('task_states_old', 'task_states');
        }
    }
}
```

### Migración de Datos Sensibles

```php
<?php

class MigrateSensitiveData extends Migration
{
    public function up()
    {
        // Migrar datos sensibles con encriptación
        Schema::table('integrations', function (Blueprint $table) {
            $table->text('credentials_encrypted')->nullable()->after('credentials');
        });
        
        DB::table('integrations')->chunk(100, function ($integrations) {
            foreach ($integrations as $integration) {
                if ($integration->credentials) {
                    $encryptedCredentials = encrypt($integration->credentials);
                    
                    DB::table('integrations')
                        ->where('id', $integration->id)
                        ->update(['credentials_encrypted' => $encryptedCredentials]);
                }
            }
        });
        
        // Verificar que todos los datos fueron migrados
        $unmigrated = DB::table('integrations')
            ->whereNotNull('credentials')
            ->whereNull('credentials_encrypted')
            ->count();
            
        if ($unmigrated > 0) {
            throw new Exception("Failed to migrate {$unmigrated} integration credentials");
        }
        
        // Eliminar columna original
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn('credentials');
        });
        
        // Renombrar columna nueva
        Schema::table('integrations', function (Blueprint $table) {
            $table->renameColumn('credentials_encrypted', 'credentials');
        });
    }
    
    public function down()
    {
        // Proceso de rollback (sin desencriptar por seguridad)
        Schema::table('integrations', function (Blueprint $table) {
            $table->text('credentials_plain')->nullable()->after('credentials');
        });
        
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn('credentials');
        });
        
        Schema::table('integrations', function (Blueprint $table) {
            $table->renameColumn('credentials_plain', 'credentials');
        });
    }
}
```

---

Esta guía proporciona un framework completo para gestionar las migraciones de base de datos en EnkiFlow durante todas las fases del proyecto. Las directrices aseguran que las migraciones sean seguras, reversibles y optimizadas para el rendimiento, mientras mantienen la integridad de los datos en un entorno multi-tenant complejo.