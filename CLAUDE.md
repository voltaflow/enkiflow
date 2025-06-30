# CLAUDE.md

Este archivo proporciona guía a Claude Code (claude.ai/code) cuando trabaja con código en este repositorio.

## Estado del Proyecto (Mayo 2025)

EnkiFlow es una plataforma SaaS de productividad con time tracking, gestión de proyectos y documentación colaborativa. Actualmente ~40% completado hacia el MVP.

### Stack Tecnológico
- **Backend**: Laravel 12.0, PHP 8.3, MySQL 8.0
- **Servidor**: Laravel Octane con Swoole (alto rendimiento)
- **Frontend**: React 19.0, TypeScript 5.0, Inertia.js, Tailwind CSS 3.4
- **Multi-tenancy**: Stancl/Tenancy 3.9 (bases de datos separadas por tenant)
- **Pagos**: Stripe con Laravel Cashier
- **Real-time**: Laravel Echo + Pusher/Ably (pendiente)
- **Testing**: PHPUnit, Vitest (coverage actual ~20%)

### Arquitectura Implementada
```
- app/Models/          # Space(Tenant), User, Project, Task, TimeEntry, Timer
- app/Services/        # TimerService, TrackingAnalyzer, TenantCreator
- app/Http/Controllers/
  ├── Tenant/         # DashboardController, ProjectController, TimeEntryController, TimerController
  └── Settings/       # ProfileController, PasswordController
- resources/js/
  ├── components/     # UI components, time-tracking widgets
  └── pages/          # Spaces, Tasks, TimeTracking, Settings
```

## Comandos de Desarrollo

### Desarrollo Local con Laravel Octane
```bash
# Iniciar entorno completo con Octane (recomendado)
php artisan octane:start --watch  # Servidor Octane con hot reload
npm run dev                        # Vite para frontend en otra terminal

# O usar el comando composer personalizado
composer dev          # Inicia Octane, colas, logs y vite

# Comandos Octane específicos
php artisan octane:reload  # Recargar workers sin downtime
php artisan octane:stop    # Detener servidor Octane
php artisan octane:status  # Ver estado del servidor
```

### Build y Producción
```bash
npm run build        # Build para producción
npm run build:ssr    # Build con SSR (Octane lo soporta)
php artisan optimize # Optimizar para producción
php artisan octane:start --workers=4 --task-workers=6  # Producción
```

### Calidad de Código
```bash
# Formateo
npm run format       # Prettier para JS/TS
./vendor/bin/pint    # Laravel Pint para PHP

# Linting
npm run lint         # ESLint para JS/TS
npm run lint:fix     # Auto-fix problemas de lint

# Type checking
npm run types        # TypeScript type check (DEBE pasar antes de commit)

# Testing
composer test        # Ejecutar todos los tests
php artisan test --parallel # Tests en paralelo
php artisan test --filter=TimerServiceTest # Test específico
```

### Multi-tenancy
```bash
# Crear nuevo tenant (espacio)
php artisan tinker
$tenant = App\Models\Space::create(['name' => 'Mi Empresa', 'slug' => 'mi-empresa']);
$tenant->domains()->create(['domain' => 'mi-empresa.enkiflow.test']);

# Ejecutar migraciones para un tenant específico
php artisan tenants:artisan "migrate" --tenant=1

# Ejecutar comando en todos los tenants
php artisan tenants:artisan "cache:clear"
```

## Verificaciones Pre-Commit (IMPORTANTE)

Antes de hacer commit, SIEMPRE ejecutar:

```bash
# 1. Type checking (CRÍTICO - no debe tener errores)
npm run types

# 2. Linting
npm run lint

# 3. Formateo
npm run format && ./vendor/bin/pint

# 4. Tests
composer test

# 5. Build de producción
npm run build
```

## Estructura de Base de Datos

### Base Central
- `users` - Usuarios del sistema
- `tenants` (spaces) - Espacios de trabajo/organizaciones
- `domains` - Dominios personalizados
- `space_users` - Relación usuarios-espacios con roles
- `subscriptions` - Suscripciones Stripe

### Base por Tenant
- `projects` - Proyectos (active, completed, archived)
- `tasks` - Tareas con estados y prioridades
- `time_entries` - Registros de tiempo históricos
- `timers` - Timers activos
- `time_categories` - Categorías de tiempo
- `comments` - Sistema de comentarios polimórfico
- `tags` & `taggables` - Sistema de etiquetas
- `application_sessions` - Tracking automático de apps
- `daily_summaries` - Resúmenes diarios de productividad

## Modelos y Servicios Principales

### Timer System
```php
// TimerService - Gestión de timers activos
$timerService->start($user, ['project_id' => 1, 'description' => 'Working on feature']);
$timerService->pause($timer);
$timerService->resume($timer);
$timerService->stop($timer); // Crea TimeEntry automáticamente

// Timer Model
Timer::forUser($userId)->running()->first(); // Timer activo del usuario
$timer->getTotalDurationAttribute(); // Duración total incluyendo pausas
```

### Time Tracking
```php
// TimeEntry Model
TimeEntry::forUser($userId)
    ->billable()
    ->between($startDate, $endDate)
    ->with(['project', 'task', 'category'])
    ->get();

// TrackingAnalyzer Service
$analyzer->processTrackingData($user, $data); // Procesa datos de tracking externo
$analyzer->getProductivityStats($user, $start, $end); // Estadísticas de productividad
```

### Projects & Tasks
```php
// Project scopes
Project::active()->owned()->withTaskCount()->get();

// Task estados
Task::pending()->inProject($projectId)->get();
$task->markAsCompleted(); // Cambia estado y registra timestamp
```

## Componentes React Principales

### Timer Widget
```typescript
// Ubicación: resources/js/components/time-tracking/timer-widget.tsx
<TimerWidget 
  projects={projects} 
  tasks={tasks} 
  onTimerStop={(timeEntry) => handleNewEntry(timeEntry)} 
/>
```

### Tipos TypeScript Importantes
```typescript
interface Timer {
  id: number;
  description: string;
  project_id: number | null;
  task_id: number | null;
  started_at: string;
  is_running: boolean;
  total_duration: number;
  project?: Project;
  task?: Task;
}

interface TimeEntry {
  id: number;
  user_id: number;
  project_id: number | null;
  task_id: number | null;
  started_at: string;
  ended_at: string | null;
  duration: number;
  description: string;
  is_billable: boolean;
  created_via: 'manual' | 'timer' | 'import';
}
```

## Features Implementadas vs Pendientes

### ✅ Completado
- Multi-tenancy con subdominios
- Autenticación y autorización
- Gestión de espacios y usuarios
- CRUD de proyectos
- Timer funcional (start/stop/pause)
- Modelos de time tracking
- Integración con Stripe
- Sistema de tags y comentarios

### 🚧 En Progreso
- UI completa de tareas
- Dashboard con métricas
- Reportes de tiempo
- Sistema de notificaciones

### ❌ Pendiente para MVP
- Timesheet semanal (como la versión Node.js)
- Exportación de reportes (CSV/PDF)
- Entrada manual de tiempo (UI)
- Filtros avanzados y búsqueda
- Onboarding de usuarios
- Documentación de API

### 🎯 Post-MVP
- Integración con calendarios (Google/Outlook)
- API REST pública
- Tracking automático de aplicaciones
- IA para categorización automática
- Editor de documentos tipo Notion
- Mobile apps

## Convenciones del Proyecto

### PHP/Laravel
- PSR-12 para estilo de código
- Tipado estricto en todos los archivos
- Form Requests para validación
- Servicios para lógica de negocio compleja
- Repositorios para queries complejas (opcional)
- Policies para autorización

### TypeScript/React
- Componentes funcionales con hooks
- Props tipadas con interfaces
- Evitar `any` - usar tipos específicos
- Componentes en PascalCase
- Hooks personalizados en camelCase con prefijo `use`

### Testing
- Mínimo un test por servicio/feature nueva
- Usar factories para datos de prueba
- Tests de integración para flujos críticos
- Mocks para servicios externos

### Git
- Commits en inglés
- Mensajes descriptivos (qué y por qué)
- Una feature por PR
- Code review obligatorio

## Configuración de Dominios Dinámica

La aplicación detecta automáticamente el dominio base desde `APP_URL`:

### Helper Functions
- `get_base_domain()` - Extrae el dominio base de `APP_URL`
- `get_main_domains()` - Retorna array con dominios principales

### Comportamiento por Ambiente
```bash
# Local
APP_URL=https://enkiflow.test
# Resultado: tenant.enkiflow.test

# Producción
APP_URL=https://enkiflow.com
# Resultado: tenant.enkiflow.com
```

No se requiere configuración adicional. Los dominios se detectan automáticamente desde `APP_URL`.

## Problemas Conocidos

1. **Coverage de tests bajo (~20%)** - Priorizar tests para features críticas
2. **Falta documentación de API** - Considerar Laravel Scribe o similar
3. **Performance con muchos time entries** - Implementar paginación/lazy loading
4. **No hay rate limiting** - Agregar throttling a APIs
5. **Falta validación de límites de plan** - Implementar checks de suscripción
6. **Octane memory leaks** - Monitorear uso de memoria en workers largos
7. **Tenancy con Octane** - Asegurar limpieza de contexto entre requests

## Consideraciones con Laravel Octane

### Mejores Prácticas
```php
// Evitar estado compartido entre requests
// MAL
class TimerService {
    private static $activeTimers = []; // NO - se compartirá entre requests
}

// BIEN
class TimerService {
    public function getActiveTimers($userId) {
        return Cache::remember("timers:$userId", 60, function() use ($userId) {
            return Timer::forUser($userId)->running()->get();
        });
    }
}
```

### Configuración Octane para Multi-tenancy
```php
// En config/octane.php agregar a 'flush'
'flush' => [
    'tenancy', // Limpiar contexto de tenant entre requests
],
```

## Enlaces Útiles

- Documentación Laravel 12: https://laravel.com/docs/12.x
- Stancl/Tenancy: https://tenancyforlaravel.com/docs/v3/
- Inertia.js: https://inertiajs.com/
- Radix UI: https://www.radix-ui.com/
- Stripe Laravel Cashier: https://laravel.com/docs/12.x/billing