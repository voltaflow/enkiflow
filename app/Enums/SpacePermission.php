<?php

namespace App\Enums;

enum SpacePermission: string
{
    // Espacio
    case MANAGE_SPACE = 'manage_space';
    case VIEW_SPACE = 'view_space';
    case DELETE_SPACE = 'delete_space';

    // Usuarios
    case INVITE_USERS = 'invite_users';
    case REMOVE_USERS = 'remove_users';
    case MANAGE_USER_ROLES = 'manage_user_roles';

    // Facturación
    case MANAGE_BILLING = 'manage_billing';
    case VIEW_INVOICES = 'view_invoices';

    // Proyectos
    case CREATE_PROJECTS = 'create_projects';
    case EDIT_PROJECTS = 'edit_projects';
    case DELETE_PROJECTS = 'delete_projects';
    case VIEW_ALL_PROJECTS = 'view_all_projects';
    case MANAGE_ALL_PROJECTS = 'manage_all_projects';

    // Tareas
    case CREATE_TASKS = 'create_tasks';
    case EDIT_ANY_TASK = 'edit_any_task';
    case EDIT_OWN_TASKS = 'edit_own_tasks';
    case DELETE_ANY_TASK = 'delete_any_task';
    case DELETE_OWN_TASKS = 'delete_own_tasks';
    case VIEW_ALL_TASKS = 'view_all_tasks';

    // Comentarios
    case CREATE_COMMENTS = 'create_comments';
    case EDIT_ANY_COMMENT = 'edit_any_comment';
    case EDIT_OWN_COMMENTS = 'edit_own_comments';
    case DELETE_ANY_COMMENT = 'delete_any_comment';
    case DELETE_OWN_COMMENTS = 'delete_own_comments';

    // Etiquetas
    case MANAGE_TAGS = 'manage_tags';

    // Estadísticas
    case VIEW_STATISTICS = 'view_statistics';

    /**
     * Get all permissions.
     */
    public static function all(): array
    {
        return [
            self::MANAGE_SPACE,
            self::VIEW_SPACE,
            self::DELETE_SPACE,
            self::INVITE_USERS,
            self::REMOVE_USERS,
            self::MANAGE_USER_ROLES,
            self::MANAGE_BILLING,
            self::VIEW_INVOICES,
            self::CREATE_PROJECTS,
            self::EDIT_PROJECTS,
            self::DELETE_PROJECTS,
            self::VIEW_ALL_PROJECTS,
            self::MANAGE_ALL_PROJECTS,
            self::CREATE_TASKS,
            self::EDIT_ANY_TASK,
            self::EDIT_OWN_TASKS,
            self::DELETE_ANY_TASK,
            self::DELETE_OWN_TASKS,
            self::VIEW_ALL_TASKS,
            self::CREATE_COMMENTS,
            self::EDIT_ANY_COMMENT,
            self::EDIT_OWN_COMMENTS,
            self::DELETE_ANY_COMMENT,
            self::DELETE_OWN_COMMENTS,
            self::MANAGE_TAGS,
            self::VIEW_STATISTICS,
        ];
    }

    /**
     * Get the permissions for each role.
     */
    public static function permissionsForRole(SpaceRole $role): array
    {
        return match ($role) {
            SpaceRole::OWNER => self::all(),

            SpaceRole::ADMIN => [
                self::MANAGE_SPACE,
                self::VIEW_SPACE,
                self::INVITE_USERS,
                self::REMOVE_USERS,
                self::MANAGE_USER_ROLES,
                self::VIEW_INVOICES,
                self::CREATE_PROJECTS,
                self::EDIT_PROJECTS,
                self::DELETE_PROJECTS,
                self::VIEW_ALL_PROJECTS,
                self::MANAGE_ALL_PROJECTS,
                self::CREATE_TASKS,
                self::EDIT_ANY_TASK,
                self::EDIT_OWN_TASKS,
                self::DELETE_ANY_TASK,
                self::DELETE_OWN_TASKS,
                self::VIEW_ALL_TASKS,
                self::CREATE_COMMENTS,
                self::EDIT_ANY_COMMENT,
                self::EDIT_OWN_COMMENTS,
                self::DELETE_ANY_COMMENT,
                self::DELETE_OWN_COMMENTS,
                self::MANAGE_TAGS,
                self::VIEW_STATISTICS,
            ],

            SpaceRole::MANAGER => [
                self::VIEW_SPACE,
                self::CREATE_PROJECTS,
                self::EDIT_PROJECTS,
                self::VIEW_ALL_PROJECTS,
                self::CREATE_TASKS,
                self::EDIT_ANY_TASK,
                self::EDIT_OWN_TASKS,
                self::DELETE_ANY_TASK,
                self::DELETE_OWN_TASKS,
                self::VIEW_ALL_TASKS,
                self::CREATE_COMMENTS,
                self::EDIT_ANY_COMMENT,
                self::EDIT_OWN_COMMENTS,
                self::DELETE_ANY_COMMENT,
                self::DELETE_OWN_COMMENTS,
                self::MANAGE_TAGS,
                self::VIEW_STATISTICS,
            ],

            SpaceRole::MEMBER => [
                self::VIEW_SPACE,
                self::VIEW_ALL_PROJECTS,
                self::CREATE_TASKS,
                self::EDIT_OWN_TASKS,
                self::DELETE_OWN_TASKS,
                self::VIEW_ALL_TASKS,
                self::CREATE_COMMENTS,
                self::EDIT_OWN_COMMENTS,
                self::DELETE_OWN_COMMENTS,
            ],

            SpaceRole::GUEST => [
                self::VIEW_SPACE,
                self::VIEW_ALL_PROJECTS,
                self::VIEW_ALL_TASKS,
                self::CREATE_COMMENTS,
                self::EDIT_OWN_COMMENTS,
                self::DELETE_OWN_COMMENTS,
            ],
        };
    }

    /**
     * Check if a role has a specific permission.
     */
    public static function roleHasPermission(SpaceRole $role, self $permission): bool
    {
        $permissions = self::permissionsForRole($role);

        return in_array($permission, $permissions);
    }

    /**
     * Get the human-readable name for the permission.
     */
    public function label(): string
    {
        return match ($this) {
            self::MANAGE_SPACE => 'Administrar espacio',
            self::VIEW_SPACE => 'Ver espacio',
            self::DELETE_SPACE => 'Eliminar espacio',
            self::INVITE_USERS => 'Invitar usuarios',
            self::REMOVE_USERS => 'Eliminar usuarios',
            self::MANAGE_USER_ROLES => 'Gestionar roles de usuarios',
            self::MANAGE_BILLING => 'Gestionar facturación',
            self::VIEW_INVOICES => 'Ver facturas',
            self::CREATE_PROJECTS => 'Crear proyectos',
            self::EDIT_PROJECTS => 'Editar proyectos',
            self::DELETE_PROJECTS => 'Eliminar proyectos',
            self::VIEW_ALL_PROJECTS => 'Ver todos los proyectos',
            self::MANAGE_ALL_PROJECTS => 'Gestionar todos los proyectos',
            self::CREATE_TASKS => 'Crear tareas',
            self::EDIT_ANY_TASK => 'Editar cualquier tarea',
            self::EDIT_OWN_TASKS => 'Editar tareas propias',
            self::DELETE_ANY_TASK => 'Eliminar cualquier tarea',
            self::DELETE_OWN_TASKS => 'Eliminar tareas propias',
            self::VIEW_ALL_TASKS => 'Ver todas las tareas',
            self::CREATE_COMMENTS => 'Crear comentarios',
            self::EDIT_ANY_COMMENT => 'Editar cualquier comentario',
            self::EDIT_OWN_COMMENTS => 'Editar comentarios propios',
            self::DELETE_ANY_COMMENT => 'Eliminar cualquier comentario',
            self::DELETE_OWN_COMMENTS => 'Eliminar comentarios propios',
            self::MANAGE_TAGS => 'Gestionar etiquetas',
            self::VIEW_STATISTICS => 'Ver estadísticas',
        };
    }
}
