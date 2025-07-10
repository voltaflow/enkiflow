# Sistema de Roles y Permisos - Guía de Uso

## Resumen de la Implementación

EnkiFlow implementa un sistema dual de roles y permisos que opera en dos niveles:

1. **Nivel de Espacio (Space)**: Permisos globales que aplican a todo el espacio de trabajo
2. **Nivel de Proyecto (Project)**: Permisos específicos que pueden heredar, ampliar o restringir los permisos de espacio

### Características Principales

- **Sistema Dual**: Roles y permisos separados para espacios y proyectos
- **Herencia con Override**: Los permisos de proyecto pueden modificar los comportamientos heredados del espacio
- **Personalización Granular**: Permisos individuales pueden ser otorgados, revocados o reseteados
- **Permisos Temporales**: Soporte para accesos con fecha de expiración
- **Auditoría Completa**: Registro de quién y cuándo se modificaron los permisos
- **Caché Inteligente**: Sistema de caché para optimizar el rendimiento

## 🏢 Roles a Nivel de Espacio

### 1. **OWNER** (Propietario)
- Control total del espacio
- Bypass automático de todos los permisos de proyecto
- Único rol que puede eliminar el espacio y gestionar facturación
- Tiene todos los permisos (24 en total)

### 2. **ADMIN** (Administrador)
- Administración completa excepto facturación y eliminación del espacio
- Puede gestionar usuarios, proyectos y configuraciones
- Tiene 20 permisos

### 3. **MANAGER** (Gerente)
- Gestión de proyectos y tareas
- Acceso a estadísticas y reportes
- Tiene 15 permisos

### 4. **MEMBER** (Miembro)
- Trabajo en tareas asignadas
- Puede crear tareas y editar solo las propias
- Tiene 9 permisos

### 5. **GUEST** (Invitado)
- Acceso de solo lectura
- Puede comentar pero no modificar contenido
- Tiene 6 permisos

## 📁 Roles a Nivel de Proyecto

### 1. **ADMIN** (Administrador de Proyecto)
- Control total sobre el proyecto específico
- Puede gestionar miembros del proyecto
- Acceso completo a todas las funcionalidades del proyecto

### 2. **MANAGER** (Gerente de Proyecto)
- Gestión del proyecto y sus miembros
- Puede ver reportes y presupuestos
- Puede exportar datos del proyecto

### 3. **EDITOR** (Editor)
- Puede editar contenido del proyecto
- Acceso a reportes básicos
- Puede trackear tiempo en todas las tareas

### 4. **MEMBER** (Miembro)
- Participación activa en el proyecto
- Puede editar contenido y trackear su propio tiempo
- Acceso limitado a funcionalidades

### 5. **VIEWER** (Observador)
- Solo visualización del proyecto
- No puede modificar ningún contenido
- Acceso de solo lectura

## 🔐 Permisos de Espacio (24 total)

### Gestión del Espacio
- `manage_space` - Administrar configuración del espacio
- `view_space` - Ver información del espacio
- `delete_space` - Eliminar el espacio (solo OWNER)

### Gestión de Usuarios
- `invite_users` - Invitar nuevos usuarios
- `remove_users` - Eliminar usuarios del espacio
- `manage_user_roles` - Cambiar roles de usuarios

### Facturación
- `manage_billing` - Gestionar suscripción y pagos
- `view_invoices` - Ver facturas

### Proyectos
- `create_projects` - Crear nuevos proyectos
- `edit_projects` - Editar proyectos existentes
- `delete_projects` - Eliminar proyectos
- `view_all_projects` - Ver todos los proyectos

### Tareas
- `create_tasks` - Crear nuevas tareas
- `edit_any_task` - Editar cualquier tarea
- `edit_own_tasks` - Editar solo tareas propias
- `delete_any_task` - Eliminar cualquier tarea
- `delete_own_tasks` - Eliminar solo tareas propias
- `view_all_tasks` - Ver todas las tareas

### Comentarios
- `create_comments` - Crear comentarios
- `edit_any_comment` - Editar cualquier comentario
- `edit_own_comments` - Editar comentarios propios
- `delete_any_comment` - Eliminar cualquier comentario
- `delete_own_comments` - Eliminar comentarios propios

### Otros
- `manage_tags` - Gestionar etiquetas
- `view_statistics` - Ver estadísticas y reportes

## 🔐 Permisos de Proyecto (10 total)

### Gestión del Proyecto
- `can_manage_project` - Control total sobre la configuración del proyecto
- `can_manage_members` - Gestionar miembros del proyecto

### Contenido
- `can_edit_content` - Crear y editar contenido del proyecto
- `can_delete_content` - Eliminar contenido del proyecto

### Visualización y Reportes
- `can_view_reports` - Ver reportes y analytics del proyecto
- `can_view_budget` - Ver información de presupuesto
- `can_export_data` - Exportar datos del proyecto

### Time Tracking
- `can_track_time` - Registrar tiempo propio
- `can_view_all_time_entries` - Ver registros de tiempo de todos

### Integraciones
- `can_manage_integrations` - Gestionar integraciones del proyecto

## 🔄 Resolución de Permisos

El sistema determina los permisos efectivos de un usuario mediante el siguiente proceso:

### 1. **Verificación de Propietario**
Si el usuario es OWNER del espacio, automáticamente tiene TODOS los permisos en TODOS los proyectos.

### 2. **Herencia de Espacio**
Si el proyecto tiene `inherit_space_permissions = true`:
- Se verifican primero los permisos del espacio
- Si el permiso existe a nivel de espacio, se usa ese valor

### 3. **Permisos de Proyecto**
Se evalúan los permisos específicos del proyecto:
- **Rol Base**: Permisos por defecto según el rol del usuario en el proyecto
- **Overrides Explícitos**: Permisos específicamente otorgados o revocados
- **Permisos Temporales**: Se verifica que no hayan expirado

### 4. **Precedencia**
1. Owner del espacio (bypass total)
2. Permisos explícitos del proyecto
3. Permisos del rol del proyecto
4. Permisos heredados del espacio (si aplica)

## 💻 Uso del Sistema

### 1. Proteger Rutas con Roles de Espacio

```php
// Requiere rol de admin o superior en el espacio
Route::middleware(['tenant.role:role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/invite', [UserController::class, 'invite']);
});
```

### 2. Proteger Rutas con Permisos de Proyecto

```php
// Requiere permiso específico en el proyecto
Route::middleware(['auth', 'can:can_manage_members,project'])->group(function () {
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store']);
});

// Múltiples permisos (requiere todos)
Route::middleware(['auth', 'can:can_view_reports,project', 'can:can_export_data,project'])->group(function () {
    Route::get('/projects/{project}/export', [ProjectExportController::class, 'export']);
});
```

### 3. Verificar Permisos en Controladores

```php
// Para permisos de espacio
use App\Traits\HasSpacePermissions;
use App\Enums\SpacePermission;

class ProjectController extends Controller
{
    use HasSpacePermissions;

    public function store(Request $request)
    {
        if (!$this->userHasPermission($request->user(), SpacePermission::CREATE_PROJECTS)) {
            abort(403, 'No tienes permiso para crear proyectos');
        }
    }
}

// Para permisos de proyecto
class TaskController extends Controller
{
    public function update(Request $request, Project $project, Task $task)
    {
        // Verificar permiso de proyecto
        if (!$request->user()->hasProjectPermission($project, 'can_edit_content')) {
            abort(403, 'No tienes permiso para editar contenido en este proyecto');
        }
    }
}
```

### 4. Gestión de Permisos de Proyecto via API

```php
// Asignar usuario a proyecto con rol
$projectPermissionService->addUserToProject(
    $project->id,
    $user->id,
    'editor',
    '2024-12-31', // opcional: fecha de expiración
    'Acceso temporal para revisión' // opcional: notas
);

// Actualizar rol de usuario en proyecto
$projectPermissionService->updateUserRole($project->id, $user->id, 'manager');

// Otorgar permisos específicos
$projectPermissionService->updateUserPermissions(
    $project->id,
    $user->id,
    ['can_view_budget', 'can_export_data'],
    'grant'
);

// Revocar permisos específicos
$projectPermissionService->updateUserPermissions(
    $project->id,
    $user->id,
    ['can_delete_content'],
    'revoke'
);

// Resetear permisos a los del rol
$projectPermissionService->updateUserPermissions(
    $project->id,
    $user->id,
    ['can_manage_members'],
    'reset'
);
```

### 5. Comando Artisan para Gestión

```bash
# Asignar rol de espacio
php artisan space:assign-role usuario@ejemplo.com 1 admin

# Asignar rol de proyecto
php artisan project:assign-role usuario@ejemplo.com 1 editor

# Listar permisos de un usuario en un proyecto
php artisan project:list-permissions usuario@ejemplo.com 1
```

## 🎯 Personalización de Permisos

### Nivel de Espacio

El sistema ofrece tres formas de personalizar permisos de espacio:

1. **Permisos Personalizados** (`custom_permissions`)
   - Reemplaza completamente los permisos del rol
   
2. **Permisos Adicionales** (`additional_permissions`)
   - Añade permisos extra al rol base
   
3. **Permisos Revocados** (`revoked_permissions`)
   - Quita permisos específicos del rol

### Nivel de Proyecto

Los permisos de proyecto usan un sistema de overrides explícitos:

```php
// Estructura en base de datos
user_project_permissions: {
    user_id: 1,
    project_id: 1,
    role: 'member',
    explicit_permissions: {
        'can_view_budget': true,  // Otorgado explícitamente
        'can_delete_content': false, // Revocado explícitamente
        'can_export_data': null  // Hereda del rol
    }
}
```

## 🔧 Consideraciones Técnicas

### Caché de Permisos
- Los permisos de espacio se cachean por 30 minutos
- Los permisos de proyecto se cachean por 15 minutos
- El caché se invalida automáticamente al actualizar permisos

### Permisos Temporales
- Campo `expires_at` para accesos con fecha límite
- Se verifican automáticamente en cada request
- Útil para consultores o accesos de revisión

### Auditoría
- `created_by`: Usuario que otorgó el permiso
- `updated_by`: Último usuario en modificar
- `notes`: Campo opcional para documentar razones
- Todos los cambios quedan registrados en la base de datos

### Performance
- Eager loading de relaciones para minimizar queries
- Índices en campos clave (user_id, project_id, role)
- Caché multinivel para optimizar verificaciones frecuentes

## 📊 Matriz de Permisos por Rol

### Roles de Proyecto y sus Permisos

| Permiso | Admin | Manager | Editor | Member | Viewer |
|---------|-------|---------|--------|--------|--------|
| can_manage_project | ✅ | ❌ | ❌ | ❌ | ❌ |
| can_manage_members | ✅ | ✅ | ❌ | ❌ | ❌ |
| can_edit_content | ✅ | ✅ | ✅ | ✅ | ❌ |
| can_delete_content | ✅ | ✅ | ❌ | ❌ | ❌ |
| can_view_reports | ✅ | ✅ | ✅ | ❌ | ❌ |
| can_view_budget | ✅ | ✅ | ❌ | ❌ | ❌ |
| can_export_data | ✅ | ✅ | ❌ | ❌ | ❌ |
| can_track_time | ✅ | ✅ | ✅ | ✅ | ❌ |
| can_view_all_time_entries | ✅ | ✅ | ✅ | ❌ | ❌ |
| can_manage_integrations | ✅ | ❌ | ❌ | ❌ | ❌ |

## 🚀 Próximos Pasos Recomendados

1. **UI de Gestión Avanzada**: Interfaz para gestión visual de permisos
2. **Plantillas de Permisos**: Conjuntos predefinidos de permisos para casos comunes
3. **Delegación de Permisos**: Permitir que usuarios deleguen sus permisos temporalmente
4. **Logs de Auditoría**: Interfaz para revisar historial de cambios
5. **Notificaciones**: Alertar sobre cambios de permisos o expiración de accesos
6. **API REST**: Endpoints públicos para gestión de permisos via API

El sistema está completamente funcional y listo para usar en producción, con soporte completo para escenarios empresariales complejos.