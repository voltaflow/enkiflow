# Sistema de Roles y Permisos - Guía de Uso

## Resumen de la Implementación

Se ha implementado un sistema completo de roles y permisos granular para EnkiFlow. El sistema permite:

1. **Asignar roles predefinidos** a usuarios por espacio de trabajo
2. **Personalizar permisos** de forma individual por usuario
3. **Proteger rutas** basándose en roles o permisos específicos
4. **Mantener aislamiento** completo entre tenants

## Roles Disponibles

### 1. **OWNER** (Propietario)
- Control total del espacio
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

## 🔐 Permisos Implementados (24 total)

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

## Permisos de cada Rol

### **OWNER** (24 permisos - todos)
Tiene TODOS los permisos del sistema

### **ADMIN** (20 permisos)
Todos los permisos EXCEPTO:
- `DELETE_SPACE` (no puede eliminar el espacio)
- `MANAGE_BILLING` (no puede gestionar facturación)
- `VIEW_INVOICES` (no puede ver facturas)
- `DELETE_OWN_COMMENTS` (no tiene este permiso)

### **MANAGER** (15 permisos)
- `MANAGE_SPACE`
- `VIEW_SPACE`
- `CREATE_PROJECTS`
- `EDIT_PROJECTS`
- `DELETE_PROJECTS`
- `VIEW_ALL_PROJECTS`
- `CREATE_TASKS`
- `EDIT_ANY_TASK`
- `DELETE_ANY_TASK`
- `VIEW_ALL_TASKS`
- `CREATE_COMMENTS`
- `EDIT_OWN_COMMENTS`
- `DELETE_OWN_COMMENTS`
- `MANAGE_TAGS`
- `VIEW_STATISTICS`

### **MEMBER** (9 permisos)
- `VIEW_SPACE`
- `VIEW_ALL_PROJECTS`
- `CREATE_TASKS`
- `EDIT_OWN_TASKS`
- `DELETE_OWN_TASKS`
- `VIEW_ALL_TASKS`
- `CREATE_COMMENTS`
- `EDIT_OWN_COMMENTS`
- `DELETE_OWN_COMMENTS`

### **GUEST** (6 permisos)
- `VIEW_SPACE`
- `VIEW_ALL_PROJECTS`
- `VIEW_ALL_TASKS`
- `CREATE_COMMENTS`
- `EDIT_OWN_COMMENTS`
- `DELETE_OWN_COMMENTS`

## Uso del Sistema

### 1. Proteger Rutas con Roles

```php
// Requiere rol de admin o superior
Route::middleware(['tenant.role:role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/invite', [UserController::class, 'invite']);
});

// Requiere rol de manager o superior
Route::middleware(['tenant.role:role:manager'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
});
```

### 2. Proteger Rutas con Permisos Específicos

```php
// Requiere permiso específico
Route::middleware(['tenant.role:create_projects'])->group(function () {
    Route::post('/projects', [ProjectController::class, 'store']);
});

// Requiere permiso para ver estadísticas
Route::middleware(['tenant.role:view_statistics'])->group(function () {
    Route::get('/analytics', [AnalyticsController::class, 'index']);
});
```

### 3. Verificar Permisos en Controladores

```php
use App\Traits\HasSpacePermissions;
use App\Enums\SpacePermission;

class ProjectController extends Controller
{
    use HasSpacePermissions;

    public function store(Request $request)
    {
        // Verificar permiso
        if (!$this->userHasPermission($request->user(), SpacePermission::CREATE_PROJECTS)) {
            abort(403, 'No tienes permiso para crear proyectos');
        }

        // Crear proyecto...
    }
}
```

### 4. Comando Artisan para Asignar Roles

```bash
# Asignar rol básico
php artisan space:assign-role usuario@ejemplo.com 1 admin

# Asignar rol con permisos adicionales
php artisan space:assign-role usuario@ejemplo.com 1 member \
  --additional=create_projects \
  --additional=view_statistics

# Asignar rol con permisos revocados
php artisan space:assign-role usuario@ejemplo.com 1 manager \
  --revoked=delete_projects
```

### 5. Gestión Programática de Permisos

```php
// Obtener o crear relación usuario-espacio
$spaceUser = SpaceUser::firstOrCreate(
    ['tenant_id' => $space->id, 'user_id' => $user->id],
    ['role' => SpaceRole::MEMBER]
);

// Asignar permisos personalizados (ignora el rol)
$spaceUser->custom_permissions = [
    'view_space',
    'create_tasks',
    'view_all_projects'
];

// O añadir permisos adicionales al rol
$spaceUser->additional_permissions = ['create_projects', 'view_statistics'];

// O revocar permisos específicos del rol
$spaceUser->revoked_permissions = ['delete_tasks'];

$spaceUser->save();
```

## Personalización de Permisos

El sistema ofrece tres formas de personalizar permisos:

### 1. **Permisos Personalizados** (`custom_permissions`)
- Reemplaza completamente los permisos del rol
- El usuario solo tendrá los permisos listados aquí

### 2. **Permisos Adicionales** (`additional_permissions`)
- Añade permisos extra al rol base
- Útil para dar capacidades específicas sin cambiar de rol

### 3. **Permisos Revocados** (`revoked_permissions`)
- Quita permisos específicos del rol
- Útil para limitar temporalmente capacidades

## Ejemplos de Implementación en Rutas

```php
// routes/tenant.php

// Gestión de usuarios - Solo admin y owner
Route::middleware(['tenant.role:role:admin'])->group(function () {
    Route::resource('users', UserController::class);
});

// Invitaciones - Requiere permiso específico
Route::middleware(['tenant.role:invite_users'])->group(function () {
    Route::resource('invitations', InvitationController::class);
});

// Analytics - Requiere permiso de ver estadísticas
Route::middleware(['tenant.role:view_statistics'])->group(function () {
    Route::get('/analytics', [AnalyticsController::class, 'index']);
    Route::get('/reports', [ReportController::class, 'index']);
});

// Proyectos - Diferentes permisos para diferentes acciones
Route::get('/projects', [ProjectController::class, 'index']); // Todos pueden ver
Route::middleware(['tenant.role:create_projects'])->group(function () {
    Route::post('/projects', [ProjectController::class, 'store']);
});
Route::middleware(['tenant.role:edit_projects'])->group(function () {
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
});
Route::middleware(['tenant.role:delete_projects'])->group(function () {
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
});
```

## Consideraciones de Seguridad

1. **Aislamiento de Tenant**: El middleware verifica automáticamente que el usuario pertenezca al tenant actual
2. **Caché de Permisos**: Los permisos se cachean por 30 minutos para mejorar el rendimiento
3. **Owner Virtual**: Si un usuario es el propietario del espacio, automáticamente obtiene rol OWNER
4. **Validación de Enums**: El sistema valida que los roles y permisos sean válidos antes de asignarlos

## Migración de Datos Existentes

Si tienes usuarios existentes, sus roles actuales se mantendrán. Las nuevas columnas JSON permiten personalización sin afectar los roles base:

```sql
-- Ver usuarios y sus roles actuales
SELECT u.email, s.name as space, su.role 
FROM space_users su
JOIN users u ON u.id = su.user_id
JOIN spaces s ON s.id = su.tenant_id;
```

## Próximos Pasos Recomendados

1. **Auditoría**: Implementar registro de cambios de roles/permisos
2. **UI de Gestión**: Crear interfaz para que admins gestionen permisos
3. **Políticas Laravel**: Implementar policies para lógica compleja
4. **Tests**: Añadir tests para verificar el sistema de permisos
5. **Documentación API**: Documentar endpoints protegidos

El sistema está completamente funcional y listo para usar. Todas las rutas pueden protegerse con el middleware `tenant.role` especificando roles o permisos específicos.