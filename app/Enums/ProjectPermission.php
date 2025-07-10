<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectPermission: string
{
    // Project Management
    case MANAGE_PROJECT = 'can_manage_project';
    case MANAGE_MEMBERS = 'can_manage_members';
    
    // Content Management
    case EDIT_CONTENT = 'can_edit_content';
    case DELETE_CONTENT = 'can_delete_content';
    
    // Reporting & Analytics
    case VIEW_REPORTS = 'can_view_reports';
    case VIEW_BUDGET = 'can_view_budget';
    case EXPORT_DATA = 'can_export_data';
    
    // Time Tracking
    case TRACK_TIME = 'can_track_time';
    case VIEW_ALL_TIME_ENTRIES = 'can_view_all_time_entries';
    
    // Integrations
    case MANAGE_INTEGRATIONS = 'can_manage_integrations';
    
    /**
     * Get display name for the permission.
     */
    public function label(): string
    {
        return match($this) {
            self::MANAGE_PROJECT => 'Gestionar Proyecto',
            self::MANAGE_MEMBERS => 'Gestionar Miembros',
            self::EDIT_CONTENT => 'Editar Contenido',
            self::DELETE_CONTENT => 'Eliminar Contenido',
            self::VIEW_REPORTS => 'Ver Reportes',
            self::VIEW_BUDGET => 'Ver Presupuesto',
            self::EXPORT_DATA => 'Exportar Datos',
            self::TRACK_TIME => 'Registrar Tiempo',
            self::VIEW_ALL_TIME_ENTRIES => 'Ver Todo el Tiempo Registrado',
            self::MANAGE_INTEGRATIONS => 'Gestionar Integraciones',
        };
    }
    
    /**
     * Get description for the permission.
     */
    public function description(): string
    {
        return match($this) {
            self::MANAGE_PROJECT => 'Configurar ajustes del proyecto, cambiar estado, eliminar proyecto',
            self::MANAGE_MEMBERS => 'Agregar, remover y cambiar roles de miembros',
            self::EDIT_CONTENT => 'Crear y editar tareas, documentos y otros contenidos',
            self::DELETE_CONTENT => 'Eliminar tareas, documentos y otros contenidos',
            self::VIEW_REPORTS => 'Acceder a reportes y estadísticas del proyecto',
            self::VIEW_BUDGET => 'Ver información de presupuesto y costos',
            self::EXPORT_DATA => 'Exportar datos del proyecto en diversos formatos',
            self::TRACK_TIME => 'Registrar tiempo trabajado en el proyecto',
            self::VIEW_ALL_TIME_ENTRIES => 'Ver registros de tiempo de todos los miembros',
            self::MANAGE_INTEGRATIONS => 'Configurar y gestionar integraciones externas',
        };
    }
    
    /**
     * Get the database column name.
     */
    public function column(): string
    {
        return $this->value;
    }
    
    /**
     * Get permissions grouped by category.
     */
    public static function grouped(): array
    {
        return [
            'Gestión del Proyecto' => [
                self::MANAGE_PROJECT,
                self::MANAGE_MEMBERS,
            ],
            'Gestión de Contenido' => [
                self::EDIT_CONTENT,
                self::DELETE_CONTENT,
            ],
            'Reportes y Análisis' => [
                self::VIEW_REPORTS,
                self::VIEW_BUDGET,
                self::EXPORT_DATA,
            ],
            'Seguimiento de Tiempo' => [
                self::TRACK_TIME,
                self::VIEW_ALL_TIME_ENTRIES,
            ],
            'Integraciones' => [
                self::MANAGE_INTEGRATIONS,
            ],
        ];
    }
    
    /**
     * Get default permissions for a role.
     */
    public static function defaultsForRole(ProjectRole $role): array
    {
        return match($role) {
            ProjectRole::ADMIN => [
                self::MANAGE_PROJECT,
                self::MANAGE_MEMBERS,
                self::EDIT_CONTENT,
                self::DELETE_CONTENT,
                self::VIEW_REPORTS,
                self::VIEW_BUDGET,
                self::EXPORT_DATA,
                self::TRACK_TIME,
                self::VIEW_ALL_TIME_ENTRIES,
                self::MANAGE_INTEGRATIONS,
            ],
            ProjectRole::MANAGER => [
                self::MANAGE_PROJECT,
                self::MANAGE_MEMBERS,
                self::EDIT_CONTENT,
                self::DELETE_CONTENT,
                self::VIEW_REPORTS,
                self::VIEW_BUDGET,
                self::EXPORT_DATA,
                self::TRACK_TIME,
                self::VIEW_ALL_TIME_ENTRIES,
            ],
            ProjectRole::EDITOR => [
                self::EDIT_CONTENT,
                self::VIEW_REPORTS,
                self::TRACK_TIME,
                self::VIEW_ALL_TIME_ENTRIES,
            ],
            ProjectRole::MEMBER => [
                self::EDIT_CONTENT,
                self::TRACK_TIME,
            ],
            ProjectRole::VIEWER => [
                // No default permissions beyond viewing
            ],
        };
    }
    
    /**
     * Check if permission should be granted by default for a role.
     */
    public function isDefaultForRole(ProjectRole $role): bool
    {
        return in_array($this, self::defaultsForRole($role), true);
    }
}