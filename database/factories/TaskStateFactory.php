<?php

namespace Database\Factories;

use App\Models\TaskState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskState>
 */
class TaskStateFactory extends Factory
{
    /**
     * El modelo asociado a la factory.
     *
     * @var string
     */
    protected $model = TaskState::class;

    /**
     * Define el estado predeterminado del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => function () {
                return tenant('id');
            },
            'name' => fake()->unique()->randomElement([
                'Pendiente', 'En progreso', 'En revisión', 'Bloqueado', 'Completado'
            ]),
            'color' => fake()->hexColor(),
            'position' => fake()->numberBetween(0, 10),
            'is_default' => false,
            'is_completed' => function (array $attributes) {
                return $attributes['name'] === 'Completado';
            },
            'is_demo' => false,
        ];
    }

    /**
     * Indica que este estado es el predeterminado.
     */
    public function default(): self
    {
        return $this->state(function () {
            return [
                'is_default' => true,
                'position' => 0,
            ];
        });
    }

    /**
     * Estado "Completado".
     */
    public function completed(): self
    {
        return $this->state(function () {
            return [
                'name' => 'Completado',
                'is_completed' => true,
                'color' => '#10B981', // Verde
            ];
        });
    }

    /**
     * Estado "En progreso".
     */
    public function inProgress(): self
    {
        return $this->state(function () {
            return [
                'name' => 'En progreso',
                'is_completed' => false,
                'color' => '#3B82F6', // Azul
            ];
        });
    }

    /**
     * Estado "Pendiente".
     */
    public function pending(): self
    {
        return $this->state(function () {
            return [
                'name' => 'Pendiente',
                'is_default' => true,
                'is_completed' => false,
                'color' => '#9CA3AF', // Gris
            ];
        });
    }

    /**
     * Estado "Bloqueado".
     */
    public function blocked(): self
    {
        return $this->state(function () {
            return [
                'name' => 'Bloqueado',
                'is_completed' => false,
                'color' => '#EF4444', // Rojo
            ];
        });
    }

    /**
     * Marcar como dato de demostración.
     */
    public function demo(): self
    {
        return $this->state(function () {
            return [
                'is_demo' => true,
                'name' => function (array $attributes) {
                    return $attributes['name'];
                },
            ];
        });
    }
}