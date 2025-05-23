<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user in the space
        $user = User::firstOr(function () {
            return User::factory()->create([
                'name' => 'Usuario de Prueba',
                'email' => 'test@example.com',
            ]);
        });

        // Create project statuses
        $projectStatuses = ['active', 'completed'];

        // Create task statuses
        $taskStatuses = ['pending', 'in_progress', 'completed'];

        // Create tags
        $tags = [
            'bug', 'feature', 'enhancement', 'documentation', 'design',
            'frontend', 'backend', 'database', 'ui', 'ux', 'testing',
            'security', 'performance', 'urgent', 'low-priority',
        ];

        foreach ($tags as $tagName) {
            Tag::firstOrCreate(['name' => $tagName]);
        }

        $allTags = Tag::all();

        // Create 5 projects
        Project::factory(5)
            ->create([
                'user_id' => $user->id,
                'status' => $projectStatuses[array_rand($projectStatuses)],
            ])
            ->each(function ($project) use ($user, $taskStatuses, $allTags) {
                // Create 3-7 tasks per project
                $tasksCount = rand(3, 7);

                Task::factory($tasksCount)
                    ->create([
                        'project_id' => $project->id,
                        'user_id' => $user->id,
                        'status' => $taskStatuses[array_rand($taskStatuses)],
                        'priority' => rand(0, 4),
                    ])
                    ->each(function ($task) use ($user, $allTags) {
                        // Attach 0-3 tags to each task
                        $tagCount = rand(0, 3);
                        if ($tagCount > 0) {
                            $task->tags()->attach(
                                $allTags->random($tagCount)->pluck('id')->toArray()
                            );
                        }

                        // Create 0-5 comments per task
                        $commentCount = rand(0, 5);
                        if ($commentCount > 0) {
                            Comment::factory($commentCount)->create([
                                'task_id' => $task->id,
                                'user_id' => $user->id,
                            ]);
                        }
                    });

                // Attach 0-3 tags to each project
                $tagCount = rand(0, 3);
                if ($tagCount > 0) {
                    $project->tags()->attach(
                        $allTags->random($tagCount)->pluck('id')->toArray()
                    );
                }
            });
    }
}
