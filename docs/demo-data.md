# Datos de Prueba en Enkiflow

Este documento describe los datos de prueba disponibles en Enkiflow y cómo generarlos, gestionarlos y eliminarlos.

## Tipos de Datos Disponibles

### Estados de Tareas
- **Pendiente**: Estado predeterminado para nuevas tareas (gris)
- **En progreso**: Tareas que se están trabajando actualmente (azul)
- **En revisión**: Tareas que necesitan ser revisadas (naranja)
- **Bloqueado**: Tareas que no pueden avanzar por algún impedimento (rojo)
- **Completado**: Tareas finalizadas (verde)

### Escenarios Predefinidos
- **Marketing Campaign**: Proyecto de campaña de marketing con tareas para planificación, ejecución y análisis
- **Sprint Planning**: Proyecto de desarrollo de software con tareas organizadas en un sprint

### Proyectos de Demostración Predeterminados
- **Rediseño de Sitio Web**: Proyecto con tareas relacionadas con diseño web
- **Aplicación Móvil v2.0**: Proyecto con tareas relacionadas con desarrollo móvil

### Relaciones Incluidas
- Tareas con diferentes estados
- Tareas con etiquetas
- Tareas con comentarios
- Tareas con entradas de tiempo
- Tareas con subtareas

## Cómo Generar Datos de Prueba

### Desde la Interfaz de Usuario

1. Accede a **Configuración** > **Desarrollador** > **Datos Demo**
2. Selecciona un escenario (opcional)
3. Configura las opciones:
   - **Fecha de inicio**: Para fechas relativas
   - **Omitir entradas de tiempo**: Para no generar registros de tiempo
   - **Solo estructura**: Para crear solo la estructura básica sin datos
4. Haz clic en **Generar Datos Demo**

### Usando el Comando Artisan

Para generar datos de demostración, puedes usar el comando `demo:seed`:

```bash
# Generar datos para todos los tenants
php artisan demo:seed

# Generar datos para un tenant específico
php artisan demo:seed --tenant=acme

# Usar un escenario específico
php artisan demo:seed --scenario=marketing_campaign

# Establecer fecha de inicio para fechas relativas
php artisan demo:seed --start-date="2023-01-01"

# Omitir generación de entradas de tiempo
php artisan demo:seed --skip-time-entries

# Generar solo estructura básica
php artisan demo:seed --only-structure
```

### Usando Factories Directamente

Si necesitas crear datos específicos en tu código, puedes usar las factories directamente:

```php
// Crear un proyecto demo con tareas
$project = Project::factory()
    ->demo()
    ->has(Task::factory()->count(5)->demo())
    ->create();

// Crear un estado de tarea específico
$pendingState = TaskState::factory()
    ->pending()
    ->demo()
    ->create();

// Crear una tarea demo con subtareas
$task = Task::factory()
    ->demo()
    ->withSubtasks(3)
    ->create();
```

## Cómo Eliminar Datos de Prueba

### Desde la Interfaz de Usuario

1. Accede a **Configuración** > **Desarrollador** > **Datos Demo**
2. Haz clic en **Eliminar Datos Demo**
3. Confirma la acción

### Usando el Comando Artisan

```bash
# Eliminar datos demo de todos los tenants
php artisan demo:reset

# Eliminar datos demo de un tenant específico
php artisan demo:reset --tenant=acme
```

## Cómo Clonar Datos Entre Tenants

### Desde la Interfaz de Usuario

1. Accede a **Configuración** > **Desarrollador** > **Datos Demo**
2. En la sección **Clonar Datos**, selecciona el tenant de destino
3. Marca la opción **Marcar como demo** si deseas que los datos clonados se marquen como demo
4. Haz clic en **Clonar Datos**

### Usando el Comando Artisan

```bash
# Clonar datos de un tenant a otro
php artisan demo:clone source_tenant target_tenant

# Clonar y marcar como demo
php artisan demo:clone source_tenant target_tenant --mark-as-demo
```

## Crear Escenarios Personalizados

Puedes crear tus propios escenarios definiendo archivos YAML en el directorio `database/demos/`:

1. Crea un archivo YAML con el nombre del escenario (ej. `mi_escenario.yaml`)
2. Define la estructura según el siguiente formato:

```yaml
description: "Descripción del escenario"

projects:
  - name: "Nombre del Proyecto"
    description: "Descripción del proyecto"
    status: "active"
    tasks:
      - title: "Título de la tarea"
        priority: "high"
        estimated_hours: 8
        tags: ["Etiqueta1", "Etiqueta2"]
        state: "Pendiente"
        subtasks:
          - title: "Subtarea 1"
            priority: "medium"
            estimated_hours: 4
            state: "Pendiente"
```

## Consideraciones Importantes

- **Identificación**: Todos los datos de demostración se marcan con `is_demo = true` y se muestran con el prefijo `[DEMO]` en la interfaz.
- **Entorno de Desarrollo**: Es seguro usar todos los comandos y seeders.
- **Entorno de Staging/Demo**: Usar con precaución, considerar limpiar datos después.
- **Entorno de Producción**: Los comandos `demo:*` están protegidos para no ejecutarse en producción.
- **Rendimiento**: Los seeders están optimizados para generar hasta 1,000 tareas en menos de 5 segundos.

## Implementación de la UI para Datos Demo (Estilo Harvest)

Para implementar la funcionalidad de Harvest para añadir/eliminar datos de muestra, necesitamos:

1. Añadir una ruta en el panel de administración
2. Crear un controlador para manejar las acciones
3. Implementar la vista con botones para añadir/eliminar datos

### Rutas

**Archivo:** `routes/web.php` (añadir)

```php
Route::middleware(['auth', 'tenant'])->group(function () {
    // Rutas para datos demo
    Route::prefix('settings/developer')->name('settings.')->group(function () {
        Route::get('/demo-data', [DemoDataController::class, 'index'])->name('demo-data');
        Route::post('/demo-data/generate', [DemoDataController::class, 'generate'])->name('demo-data.generate');
        Route::post('/demo-data/reset', [DemoDataController::class, 'reset'])->name('demo-data.reset');
        Route::get('/demo-data/snapshot', [DemoDataController::class, 'snapshot'])->name('demo-data.snapshot');
        Route::post('/demo-data/clone', [DemoDataController::class, 'clone'])->name('demo-data.clone');
    });
});
```

## Checklist de Implementación

- [x] Crear trait HasDemoFlag
- [x] Crear migración para añadir columna is_demo a las tablas
- [x] Crear/actualizar modelo TaskState
- [x] Implementar TaskStateFactory
- [x] Actualizar ProjectFactory y TaskFactory
- [x] Crear helper RelativeDate
- [x] Implementar TaskStateSeeder y DemoProjectSeeder
- [x] Actualizar TenantSeeder
- [x] Crear comandos DemoSeedCommand, DemoResetCommand y DemoCloneCommand
- [x] Crear estructura de directorios para escenarios YAML
- [x] Implementar DemoDataController y DemoDataService
- [x] Crear vistas para la UI de administración de datos demo
- [x] Crear documentación en docs/demo-data.md

## Conclusión

Esta implementación proporciona un sistema completo para generar, gestionar y eliminar datos de prueba en Enkiflow, siguiendo las mejores prácticas de SaaS líderes como Harvest. El sistema permite:

1. Marcar claramente los datos de demostración con [DEMO]
2. Añadir/eliminar datos de prueba desde la UI o mediante comandos
3. Usar fechas relativas para mantener la relevancia de los datos
4. Clonar datos entre tenants
5. Crear escenarios personalizados mediante archivos YAML
6. Generar snapshots para respaldo o migración

La implementación es flexible, respeta la arquitectura multi-tenant y proporciona una experiencia similar a la de Harvest para la gestión de datos de muestra.