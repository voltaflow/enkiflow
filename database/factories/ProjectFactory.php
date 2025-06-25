<?php

namespace Database\Factories;

use App\Helpers\RelativeDate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * El modelo asociado a la factory.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define el estado predeterminado del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = RelativeDate::get('-3 months', '+1 week');
        
        return [
            'tenant_id' => function () {
                return tenant('id');
            },
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['active', 'on_hold', 'completed']),
            'start_date' => $startDate,
            'due_date' => RelativeDate::get('+1 month', '+3 months', $startDate),
            'completed_at' => function (array $attributes) {
                return $attributes['status'] === 'completed' ? RelativeDate::get('-1 month', 'now') : null;
            },
            'settings' => [
                'show_completed_tasks' => fake()->boolean(70),
                'default_view' => fake()->randomElement(['list', 'board', 'calendar', 'gantt']),
            ],
            'is_demo' => false,
        ];
    }

    /**
     * Proyecto completado.
     */
    public function completed(): self
    {
        return $this->state(function () {
            return [
                'status' => 'completed',
                'completed_at' => RelativeDate::get('-1 month', 'now'),
            ];
        });
    }

    /**
     * Proyecto activo.
     */
    public function active(): self
    {
        return $this->state(function () {
            return [
                'status' => 'active',
                'completed_at' => null,
            ];
        });
    }

    /**
     * Proyecto en pausa.
     */
    public function onHold(): self
    {
        return $this->state(function () {
            return [
                'status' => 'on_hold',
                'completed_at' => null,
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