# CLAUDE.md

Este archivo proporciona guía a Claude Code (claude.ai/code) cuando trabaja con código en este repositorio.

## Comandos de Desarrollo

- Iniciar entorno: `composer dev` (servidor web, colas, logs, vite)
- Desarrollo frontend: `npm run dev`
- Compilar: `npm run build` o `npm run build:ssr` (con SSR)
- Formatear código: `npm run format` y `./vendor/bin/pint` (PHP)
- Lint código: `npm run lint`
- Verificación de tipos: `npm run types`
- Ejecutar pruebas: `composer test` o `php artisan test`
- Ejecutar prueba individual: `php artisan test --filter=TestClassName::testMethodName`
- Ejecutar suite de pruebas: `php artisan test --testsuite=Unit`

## Comandos de Verificación para CI/CD y Workflows

**IMPORTANTE**: Antes de hacer commit o crear un PR, siempre ejecutar estos comandos de verificación:

1. **Verificación de tipos TypeScript** (CRÍTICO):
   ```bash
   npm run types
   ```
   - Este comando DEBE pasar sin errores
   - Verifica que todos los tipos estén correctamente definidos
   - No debe haber errores tipo `TS2322`, `TS2339`, `TS2304`, etc.

2. **Linting de código**:
   ```bash
   npm run lint
   ```
   - Verifica estilo de código y mejores prácticas
   - Los warnings de ESLint son aceptables pero deben minimizarse
   - Los errores de ESLint deben corregirse

3. **Formateo de código**:
   ```bash
   npm run format
   ./vendor/bin/pint
   ```
   - Asegura consistencia en el formato del código
   - Ejecutar antes de cada commit

4. **Pruebas**:
   ```bash
   composer test
   ```
   - Todas las pruebas deben pasar
   - Agregar nuevas pruebas para nuevas funcionalidades

5. **Build de producción**:
   ```bash
   npm run build
   ```
   - Verifica que el código compile correctamente para producción
   - No debe haber errores de compilación

## Arquitectura y Estilo

- Laravel 12 + React TypeScript SaaS con multi-tenancy (Stancl Tenancy)
- Base de datos central para usuarios/espacios, bases de datos de tenant para datos específicos de espacio
- PHP: Seguir PSR-12, usar tipado estricto, tipar todos los métodos/propiedades
- TypeScript: Componentes funcionales con hooks, definiciones de tipo explícitas
- UI: Usar componentes del directorio ui/, seguir sistema de apariencia
- Testing: Escribir tests unitarios para modelos/servicios, tests de feature para endpoints
- Seguridad: Validar inputs, usar middleware para validación de tenant

## Organización del Código

- Controladores agrupados por carpetas de funcionalidades
- Componentes React organizados por funcionalidad
- Migraciones de base de datos separadas por tenant/central
- Definir relaciones claramente en los modelos
- Extraer lógica reutilizable a hooks personalizados
- Seguir diseños responsivos para móviles

## Estructura de Multi-Tenancy Implementada

### Modelos Principales

- **Space (Tenant)**: Representa un espacio/organización.
  - Implementa interfaces de Tenant requeridas por Stancl Tenancy
  - Gestiona suscripciones a través de Stripe Cashier
  - Relaciones con usuarios y dominios

- **Project**: Modelo específico del tenant para proyectos.
  - Incluye métodos para cambio de estado (active, completed)
  - Relaciones con tareas y etiquetas (tags)
  - Scopes para filtrar por estado

- **Task**: Modelo específico del tenant para tareas.
  - Estados: pending, in_progress, completed
  - Jerarquía: pertenece a un Project
  - Relaciones con comentarios y etiquetas
  - Métodos para cambios de estado

- **Comment**: Modelo para comentarios en tareas.
  - Pertenece a una Task y a un User
  - Incluye funcionalidad de edición

- **Tag**: Modelo para etiquetado de recursos.
  - Implementa relaciones polimórficas
  - Puede aplicarse a Projects y Tasks

### Patrón Repositorio/Servicio Implementado

#### Repositorios
- **ProjectRepositoryInterface** y **TaskRepositoryInterface**
  - Definen operaciones CRUD y métodos de consulta específicos
  - Abstraen detalles de la implementación de Eloquent

- **ProjectRepository** y **TaskRepository**
  - Implementaciones concretas usando Eloquent
  - Ejecutan consultas optimizadas con eager loading
  - Implementan métodos específicos para filtrado

#### Servicios
- **ProjectService** y **TaskService**
  - Encapsulan lógica de negocio
  - Usan repositorios para acceso a datos
  - Proporcionan una API clara para los controladores
  - Manejan operaciones como cambios de estado y relaciones

### Form Requests para Validación

- **StoreProjectRequest**, **UpdateProjectRequest**
- **StoreTaskRequest**, **UpdateTaskRequest**
  - Implementan reglas de validación
  - Autorizan operaciones basadas en relaciones de usuario
  - Preparan datos antes de la validación

### Controladores

- **ProjectController** y **TaskController**
  - Controladores delgados que delegan a servicios
  - Implementan respuestas de API consistentes
  - Usan patrón de Resource Controller
  - Integran con Inertia.js para respuestas

### Tests

- **Unitarios**: Para modelos, repositorios y servicios
- **Feature**: Para controladores y endpoints
- **TenancyTestCase**: Base personalizada para tests en entorno multi-tenant

## Buenas Prácticas Laravel

### Estructura de Aplicación

- **Patrón Repositorio/Servicio**: Usar repositorios para abstraer el acceso a datos y servicios para encapsular la lógica de negocio.
  ```php
  class TaskService
  {
      public function __construct(
          protected TaskRepositoryInterface $taskRepository
      ) {}
      
      public function markTaskAsCompleted(int $id): Task
      {
          $task = $this->taskRepository->find($id);
          $task->markAsCompleted();
          return $task;
      }
  }
  ```

- **Single Responsibility Principle**: Cada clase debe tener una única responsabilidad.
  ```php
  // Controlador ligero que delega a servicios
  public function update(UpdateTaskRequest $request, Task $task)
  {
      $validated = $request->validated();
      
      $this->taskService->updateTask($task->id, $validated);
      
      if ($request->has('tags')) {
          $this->taskService->syncTags($task->id, $request->tags);
      }
      
      return redirect()->route('tasks.show', $task)
          ->with('success', 'Task updated successfully.');
  }
  ```

- **Modelos Eloquent robustos**: Aprovechar scopes locales y métodos de consulta.
  ```php
  // En el modelo Task
  public function scopePending($query)
  {
      return $query->where('status', 'pending');
  }
  
  public function scopeInProgress($query)
  {
      return $query->where('status', 'in_progress');
  }
  
  // Uso en repositorio
  public function pending(): Collection
  {
      return Task::pending()->get();
  }
  ```

- **Inyección de Dependencias**: Usar el contenedor IoC de Laravel para gestionar dependencias.
  ```php
  public function __construct(
      protected ProjectRepositoryInterface $projectRepository
  ) {}
  ```

### Base de Datos y Rendimiento

- **Eager Loading**: Siempre usar Eager Loading para evitar problemas N+1.
  ```php
  // En TaskController::show
  $task->load(['project', 'user', 'comments.user', 'tags']);
  ```

- **Chunking**: Procesar conjuntos grandes de datos en fragmentos.
  ```php
  Project::where('status', 'active')->chunk(100, function ($projects) {
      foreach ($projects as $project) {
          // Procesar datos en lotes manejables
      }
  });
  ```

- **Transacciones**: Usar transacciones para operaciones que involucran múltiples cambios.
  ```php
  DB::transaction(function () {
      // Operaciones en múltiples tablas que deben ser atómicas
  });
  ```

### Seguridad

- **Mass Assignment**: Usar siempre `$fillable` o `$guarded` en los modelos.
  ```php
  protected $fillable = [
      'title', 'description', 'project_id', 'user_id',
      'status', 'priority', 'due_date', 'completed_at',
      'settings',
  ];
  ```

- **Validación**: Usar Form Requests para validación.
  ```php
  class StoreTaskRequest extends FormRequest
  {
      public function rules(): array
      {
          return [
              'title' => 'required|string|max:255',
              'description' => 'nullable|string',
              'project_id' => 'required|exists:projects,id',
              'status' => 'required|in:pending,in_progress,completed',
              'priority' => 'required|integer|min:0|max:5',
              'due_date' => 'nullable|date',
          ];
      }
  }
  ```

- **Autorización**: Usar Policies para lógica de autorización.
  ```php
  public function update(User $user, Project $project)
  {
      return $user->id === $project->user_id || 
             $user->hasRole('admin');
  }
  ```

### Multi-tenancy

- **Middleware de Tenant**: Asegurar que las solicitudes tengan un tenant válido.
  ```php
  // En EnsureValidTenant middleware
  public function handle(Request $request, Closure $next)
  {
      if (!tenant() || !tenant()->exists) {
          return redirect()->route('spaces.index')
              ->with('error', 'Espacio no válido o inactivo.');
      }
      
      return $next($request);
  }
  ```

- **Scope Global de Tenant**: Aplicar automáticamente el scope de tenant a consultas.
  ```php
  // En el modelo tenant Project
  protected static function booted()
  {
      static::addGlobalScope('tenant', function (Builder $builder) {
          // Stancl Tenancy maneja esto automáticamente 
          // al usar la base de datos del tenant
      });
  }
  ```

### Frontend con Inertia.js

- **Prop Types**: Definir siempre tipos para las props de Inertia.
  ```typescript
  interface Task {
    id: number;
    title: string;
    description: string | null;
    project_id: number;
    status: 'pending' | 'in_progress' | 'completed';
    priority: number;
    due_date: string | null;
  }
  
  interface Props {
    task: Task;
    project: Project;
  }
  ```

- **Compartir Datos Globales**: Usar middleware para compartir datos comunes.
  ```php
  // En HandleInertiaRequests.php
  public function share(Request $request)
  {
      return array_merge(parent::share($request), [
          'auth' => [
              'user' => $request->user() ? [
                  'id' => $request->user()->id,
                  'name' => $request->user()->name,
              ] : null,
          ],
          'tenant' => tenant() ? [
              'id' => tenant()->id,
              'name' => tenant()->name,
          ] : null,
      ]);
  }
  ```

## Convenciones Importantes

- **Naming**: Seguir convenciones de Laravel (CamelCase para clases, snake_case para métodos)
- **Comentarios**: Documentar métodos complejos y decisiones de diseño importantes
- **Cacheo**: Usar cache cuando sea apropiado para optimizar rendimiento
- **Procesos Asíncronos**: Usar colas para operaciones costosas o no críticas en tiempo
- **Pruebas**: Mantener cobertura de pruebas para funcionalidades críticas del sistema

## Mejores Prácticas de TypeScript

### Tipos y Interfaces

1. **PageProps genérico**: Usar `PageProps<T>` para componentes de página:
   ```typescript
   interface MyPageProps {
     users: User[];
     canEdit: boolean;
   }
   
   export default function MyPage({ users, canEdit }: PageProps<MyPageProps>) {
     // ...
   }
   ```

2. **Componentes con forwardRef**: Para componentes que usan `Slot` de Radix UI:
   ```typescript
   const MyComponent = React.forwardRef<HTMLDivElement, ComponentProps>(
     ({ className, asChild, ...props }, ref) => {
       const Comp = asChild ? Slot : "div";
       return <Comp ref={ref as any} {...props} />;
     }
   );
   MyComponent.displayName = "MyComponent";
   ```

3. **Formularios con Inertia**: Para formularios complejos, manejar el estado localmente:
   ```typescript
   const [items, setItems] = useState<Item[]>([]);
   const { post } = useForm({});
   
   const submit = () => {
     router.post(route('endpoint'), { items }, {
       onSuccess: () => { /* ... */ }
     });
   };
   ```

4. **Importaciones necesarias**: Siempre importar hooks y tipos necesarios:
   ```typescript
   import { useState, useEffect, useCallback } from 'react';
   import type { LucideIcon } from 'lucide-react';
   ```

### Errores Comunes a Evitar

1. **No asumir propiedades opcionales**: Usar optional chaining (`?.`)
2. **Tipar explícitamente parámetros de callbacks**: Evitar `any` implícito
3. **Exportar interfaces necesarias**: Como `PageProps`, `Task`, `User`, etc.
4. **Verificar imports**: Asegurar que todos los hooks y componentes estén importados

## Recursos para el Desarrollo

- Documentación Laravel: https://laravel.com/docs/12.x
- Documentación Stancl/Tenancy: https://tenancyforlaravel.com/docs/v3/
- Documentación Inertia.js: https://inertiajs.com/
- Laravel Best Practices: https://github.com/alexeymezenin/laravel-best-practices