<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case EDITOR = 'editor';
    case MEMBER = 'member';
    case VIEWER = 'viewer';
    
    /**
     * Get display name for the role.
     */
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrador',
            self::MANAGER => 'Gerente',
            self::EDITOR => 'Editor',
            self::MEMBER => 'Miembro',
            self::VIEWER => 'Observador',
        };
    }
    
    /**
     * Get description for the role.
     */
    public function description(): string
    {
        return match($this) {
            self::ADMIN => 'Control total sobre el proyecto',
            self::MANAGER => 'Gestión del proyecto y miembros',
            self::EDITOR => 'Edición de contenido del proyecto',
            self::MEMBER => 'Participación activa en el proyecto',
            self::VIEWER => 'Solo visualización del proyecto',
        };
    }
    
    /**
     * Get hierarchy level (higher = more permissions).
     */
    public function level(): int
    {
        return match($this) {
            self::ADMIN => 100,
            self::MANAGER => 80,
            self::EDITOR => 60,
            self::MEMBER => 40,
            self::VIEWER => 20,
        };
    }
    
    /**
     * Check if this role is higher than another.
     */
    public function isHigherThan(self $other): bool
    {
        return $this->level() > $other->level();
    }
    
    /**
     * Check if this role is at least as high as another.
     */
    public function isAtLeast(self $other): bool
    {
        return $this->level() >= $other->level();
    }
    
    /**
     * Get all values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    /**
     * Get options for select.
     */
    public static function options(): array
    {
        return array_map(
            fn(self $role) => [
                'value' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
            ],
            self::cases()
        );
    }
}