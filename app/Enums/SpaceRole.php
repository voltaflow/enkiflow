<?php

namespace App\Enums;

enum SpaceRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case MEMBER = 'member';
    case GUEST = 'guest';

    /**
     * Get human-readable name for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Propietario',
            self::ADMIN => 'Administrador',
            self::MANAGER => 'Gerente',
            self::MEMBER => 'Miembro',
            self::GUEST => 'Invitado',
        };
    }

    /**
     * Get the description of the role.
     */
    public function description(): string
    {
        return match ($this) {
            self::OWNER => 'Control total del espacio, incluida la facturación y eliminación.',
            self::ADMIN => 'Administración del espacio, incluidos usuarios, proyectos y configuraciones.',
            self::MANAGER => 'Gestión de proyectos y tareas, vista de estadísticas.',
            self::MEMBER => 'Trabajo en tareas asignadas y participación en proyectos.',
            self::GUEST => 'Acceso limitado solo para consulta.',
        };
    }

    /**
     * Get all roles except owner.
     */
    public static function assignableRoles(): array
    {
        return [
            self::ADMIN,
            self::MANAGER,
            self::MEMBER,
            self::GUEST,
        ];
    }

    /**
     * Get all roles.
     */
    public static function allRoles(): array
    {
        return [
            self::OWNER,
            self::ADMIN,
            self::MANAGER,
            self::MEMBER,
            self::GUEST,
        ];
    }

    /**
     * Check if this role has higher permission level than the given role.
     */
    public function higherThan(SpaceRole $role): bool
    {
        $hierarchy = [
            self::OWNER->value => 5,
            self::ADMIN->value => 4,
            self::MANAGER->value => 3,
            self::MEMBER->value => 2,
            self::GUEST->value => 1,
        ];

        return $hierarchy[$this->value] > $hierarchy[$role->value];
    }

    /**
     * Check if this role has equal or higher permission level than the given role.
     */
    public function equalOrHigherThan(SpaceRole $role): bool
    {
        $hierarchy = [
            self::OWNER->value => 5,
            self::ADMIN->value => 4,
            self::MANAGER->value => 3,
            self::MEMBER->value => 2,
            self::GUEST->value => 1,
        ];

        return $hierarchy[$this->value] >= $hierarchy[$role->value];
    }
}
