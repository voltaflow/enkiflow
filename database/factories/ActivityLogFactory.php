<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activityType = $this->faker->randomElement(['keyboard', 'mouse', 'application_focus', 'idle']);
        
        $metadata = match($activityType) {
            'keyboard' => [
                'keystrokes' => $this->faker->numberBetween(10, 200),
                'speed_wpm' => $this->faker->numberBetween(20, 80),
            ],
            'mouse' => [
                'clicks' => $this->faker->numberBetween(5, 50),
                'movements' => $this->faker->numberBetween(100, 1000),
                'scrolls' => $this->faker->numberBetween(0, 20),
            ],
            'application_focus' => [
                'app_name' => $this->faker->randomElement(['Chrome', 'VS Code', 'Slack', 'Terminal', 'Figma']),
                'window_title' => $this->faker->sentence(3),
                'url' => $activityType === 'Chrome' ? $this->faker->url() : null,
            ],
            'idle' => [
                'duration_seconds' => $this->faker->numberBetween(60, 600),
                'reason' => $this->faker->randomElement(['no_activity', 'locked_screen', 'away']),
            ],
        };
        
        return [
            'user_id' => User::factory(),
            'time_entry_id' => TimeEntry::factory(),
            'activity_type' => $activityType,
            'metadata' => $metadata,
            'timestamp' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
    
    /**
     * Indicate that the activity is keyboard activity.
     */
    public function keyboard(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'keyboard',
            'metadata' => [
                'keystrokes' => $this->faker->numberBetween(10, 200),
                'speed_wpm' => $this->faker->numberBetween(20, 80),
            ],
        ]);
    }
    
    /**
     * Indicate that the activity is mouse activity.
     */
    public function mouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'mouse',
            'metadata' => [
                'clicks' => $this->faker->numberBetween(5, 50),
                'movements' => $this->faker->numberBetween(100, 1000),
                'scrolls' => $this->faker->numberBetween(0, 20),
            ],
        ]);
    }
    
    /**
     * Indicate that the activity is application focus.
     */
    public function applicationFocus(string $appName = null): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'application_focus',
            'metadata' => [
                'app_name' => $appName ?? $this->faker->randomElement(['Chrome', 'VS Code', 'Slack', 'Terminal', 'Figma']),
                'window_title' => $this->faker->sentence(3),
                'url' => in_array($appName, ['Chrome', 'Firefox']) ? $this->faker->url() : null,
            ],
        ]);
    }
    
    /**
     * Indicate that the activity is idle time.
     */
    public function idle(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'idle',
            'metadata' => [
                'duration_seconds' => $this->faker->numberBetween(60, 600),
                'reason' => $this->faker->randomElement(['no_activity', 'locked_screen', 'away']),
            ],
        ]);
    }
}