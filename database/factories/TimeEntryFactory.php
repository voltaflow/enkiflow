<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeCategory;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TimeEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $endedAt = clone $startedAt;
        $endedAt->modify('+' . $this->faker->numberBetween(15, 180) . ' minutes');
        $duration = Carbon::parse($endedAt)->diffInSeconds(Carbon::parse($startedAt));
        
        $tags = $this->faker->randomElements(['research', 'development', 'design', 'meeting', 'bug fix', 'refactor', 'documentation', 'testing'], $this->faker->numberBetween(0, 3));
        
        return [
            'user_id' => User::factory(),
            'task_id' => $this->faker->boolean(70) ? Task::factory() : null,
            'project_id' => $this->faker->boolean(80) ? Project::factory() : null,
            'category_id' => $this->faker->boolean(60) ? TimeCategory::factory() : null,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration' => $duration,
            'description' => $this->faker->sentence(),
            'is_billable' => $this->faker->boolean(80),
            'is_manual' => $this->faker->boolean(20),
            'tags' => $tags,
            'metadata' => [
                'activity_level' => $this->faker->numberBetween(50, 100),
                'idle_detected' => false,
                'app_name' => $this->faker->randomElement(['Browser', 'IDE', 'Terminal', 'Office']),
            ],
        ];
    }
    
    /**
     * Indicate that the time entry is billable.
     */
    public function billable(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_billable' => true,
            ];
        });
    }
    
    /**
     * Indicate that the time entry is not billable.
     */
    public function nonBillable(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_billable' => false,
            ];
        });
    }
    
    /**
     * Indicate that the time entry is running (not ended).
     */
    public function running(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'ended_at' => null,
                'duration' => 0,
            ];
        });
    }
    
    /**
     * Indicate that the time entry was manually entered.
     */
    public function manual(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_manual' => true,
            ];
        });
    }
    
    /**
     * Create a time entry for a specific user.
     */
    public function forUser(User $user): Factory
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }
    
    /**
     * Create a time entry for a specific task.
     */
    public function forTask(Task $task): Factory
    {
        return $this->state(function (array $attributes) use ($task) {
            return [
                'task_id' => $task->id,
                'project_id' => $task->project_id,
            ];
        });
    }
    
    /**
     * Create a time entry for a specific project.
     */
    public function forProject(Project $project): Factory
    {
        return $this->state(function (array $attributes) use ($project) {
            return [
                'project_id' => $project->id,
            ];
        });
    }
}
