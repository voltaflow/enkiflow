<?php

namespace Database\Factories;

use App\Helpers\RelativeDate;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * El modelo asociado a la factory.
     *
     * @var string
     */
    protected $model = Task::class;

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
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'task_state_id' => function () {
                return TaskState::inRandomOrder()->first()->id ?? TaskState::factory()->create()->id;
            },
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'position' => fake()->numberBetween(0, 100),
            'estimated_hours' => fake()->randomFloat(2, 1, 40),
            'start_date' => RelativeDate::get('-1 month', 'now'),
            'due_date' => RelativeDate::get('now', '+1 month'),
            'completed_at' => function (array $attributes) {
                $taskState = TaskState::find($attributes['task_state_id']);
                return $taskState && $taskState->is_completed ? RelativeDate::get('-1 month', 'now') : null;
            },
            'settings' => [
                'notifications' => fake()->boolean(80),
                'visibility' => fake()->randomElement(['public', 'private', 'team']),
            ],
            'is_demo' => false,
        ];
    }

    /**
     * Tarea de alta prioridad.
     */
    public function highPriority(): self
    {
        return $this->state(function () {
            return [
                'priority' => 'high',
            ];
        });
    }

    /**
     * Tarea urgente.
     */
    public function urgent(): self
    {
        return $this->state(function () {
            return [
                'priority' => 'urgent',
            ];
        });
    }

    /**
     * Tarea con subtareas.
     */
    public function withSubtasks(int $count = 2): self
    {
        return $this->afterCreating(function ($task) use ($count) {
            Task::factory()->count($count)->demo($task->is_demo)->create([
                'parent_id' => $task->id,
                'project_id' => $task->project_id,
                'tenant_id' => $task->tenant_id,
            ]);
        });
    }

    /**
     * Marcar como dato de demostración.
     */
    public function demo($isDemo = true): self
    {
        return $this->state(function () use ($isDemo) {
            return [
                'is_demo' => $isDemo,
                'title' => function (array $attributes) {
                    return $attributes['title'];
                },
            ];
        });
    }
}