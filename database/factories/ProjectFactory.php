<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'user_id' => \App\Models\User::factory(),
            'status' => fake()->randomElement(['active', 'completed', 'paused']),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'completed_at' => function (array $attributes) {
                return $attributes['status'] === 'completed' ? fake()->dateTimeBetween('-1 month', 'now') : null;
            },
            'settings' => [],
        ];
    }
}
