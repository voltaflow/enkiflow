<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->paragraph(),
            'task_id' => \App\Models\Task::factory(),
            'user_id' => \App\Models\User::factory(),
            'edited_at' => fake()->optional(0.2)->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
