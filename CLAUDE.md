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

## Buenas Prácticas Laravel

### Estructura de Aplicación

- **Patrón Repositorio/Servicio**: Usar repositorios para abstraer el acceso a datos y servicios para encapsular la lógica de negocio.
  ```php
  class ArticleService
  {
      public function handleUploadedImage($image)
      {
          if (!is_null($image)) {
              $image->move(public_path('images') . 'temp');
          }
      }
  }
  ```

- **Single Responsibility Principle**: Cada clase debe tener una única responsabilidad.
  ```php
  // Controlador ligero que delega a servicios
  public function update(UpdateRequest $request)
  {
      $this->logService->logEvents($request->events);
      $this->articleService->updateArticle($request->validated());
      return back()->with('success', 'Actualizado correctamente');
  }
  ```

- **Modelos Eloquent robustos**: Aprovechar scopes locales y métodos de consulta.
  ```php
  // En el modelo
  public function scopeActive($query)
  {
      return $query->where('status', 'active');
  }
  
  // En vez de repetir la condición en diferentes lugares
  Project::active()->get();
  ```

- **Inyección de Dependencias**: Usar el contenedor IoC de Laravel para gestionar dependencias.
  ```php
  public function __construct(
      protected ProjectRepository $projects,
      protected TimeService $timeService
  ) {}
  ```

### Base de Datos y Rendimiento

- **Eager Loading**: Siempre usar Eager Loading para evitar problemas N+1.
  ```php
  // Malo - genera consultas N+1
  $spaces = Space::all();
  foreach ($spaces as $space) {
      echo $space->owner->name; // Consulta adicional por cada iteración
  }
  
  // Bueno - carga relaciones con antelación
  $spaces = Space::with('owner')->get();
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
      'name', 'description', 'user_id', 'status'
  ];
  ```

- **Validación**: Usar Form Requests para validación.
  ```php
  class StoreProjectRequest extends FormRequest
  {
      public function rules()
      {
          return [
              'name' => 'required|max:255',
              'description' => 'nullable|string',
              'status' => 'required|in:active,pending,completed'
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
  // Middleware para asegurar tenant válido
  if (!$tenant || !$tenant->isActive()) {
      return response()->view('errors.invalid-tenant');
  }
  ```

- **Scope Global de Tenant**: Aplicar automáticamente el scope de tenant a consultas.
  ```php
  // En el modelo tenant
  protected static function booted()
  {
      static::addGlobalScope('tenant', function (Builder $builder) {
          $builder->where('tenant_id', tenant('id'));
      });
  }
  ```

### Frontend con Inertia.js

- **Prop Types**: Definir siempre tipos para las props de Inertia.
  ```typescript
  interface Project {
    id: number;
    name: string;
    description: string | null;
    status: 'active' | 'pending' | 'completed';
  }
  
  interface Props {
    projects: Project[];
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
          'flash' => [
              'message' => session('message'),
              'error' => session('error'),
          ],
      ]);
  }
  ```

## Convenciones Importantes

- **Naming**: Seguir convenciones de Laravel (CamelCase para clases, snake_case para métodos)
- **Comentarios**: Documentar métodos complejos y decisiones de diseño importantes
- **Cacheo**: Usar cache cuando sea apropiado para optimizar rendimiento
- **Procesos Asíncronos**: Usar colas para operaciones costosas o no críticas en tiempo
- **Pruebas**: Mantener cobertura de pruebas para funcionalidades críticas del sistema

## Recursos para el Desarrollo

- Documentación Laravel: https://laravel.com/docs/12.x
- Documentación Stancl/Tenancy: https://tenancyforlaravel.com/docs/v3/
- Documentación Inertia.js: https://inertiajs.com/
- Laravel Best Practices: https://github.com/alexeymezenin/laravel-best-practices