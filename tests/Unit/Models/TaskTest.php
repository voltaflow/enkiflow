<?php

namespace Tests\Unit\Models;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TenancyTestCase;

class TaskTest extends TenancyTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_project()
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $task->project);
        $this->assertEquals($project->id, $task->project->id);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $task->user);
        $this->assertEquals($user->id, $task->user->id);
    }

    /** @test */
    public function it_has_many_comments()
    {
        $task = Task::factory()->create();
        $comment1 = Comment::factory()->create(['task_id' => $task->id]);
        $comment2 = Comment::factory()->create(['task_id' => $task->id]);

        $this->assertCount(2, $task->comments);
        $this->assertInstanceOf(Comment::class, $task->comments->first());
    }

    /** @test */
    public function it_can_filter_by_status()
    {
        Task::factory()->create(['status' => 'pending']);
        Task::factory()->create(['status' => 'in_progress']);
        Task::factory()->create(['status' => 'completed']);

        $this->assertCount(1, Task::pending()->get());
        $this->assertCount(1, Task::inProgress()->get());
        $this->assertCount(1, Task::completed()->get());
    }

    /** @test */
    public function it_can_be_marked_as_completed()
    {
        $task = Task::factory()->create(['status' => 'pending', 'completed_at' => null]);
        $task->markAsCompleted();

        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    /** @test */
    public function it_can_be_marked_as_in_progress()
    {
        $task = Task::factory()->create(['status' => 'pending']);
        $task->markAsInProgress();

        $this->assertEquals('in_progress', $task->status);
    }

    /** @test */
    public function it_can_be_marked_as_pending()
    {
        $task = Task::factory()->create(['status' => 'completed', 'completed_at' => now()]);
        $task->markAsPending();

        $this->assertEquals('pending', $task->status);
        $this->assertNull($task->completed_at);
    }
}