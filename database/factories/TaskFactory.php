<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'project_id' => \App\Models\Project::factory(),
            'user_id' => \App\Models\User::factory(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed']),
            'priority' => fake()->numberBetween(0, 5),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'completed_at' => function (array $attributes) {
                return $attributes['status'] === 'completed' ? fake()->dateTimeBetween('-1 month', 'now') : null;
            },
            'settings' => [],
        ];
    }
}
