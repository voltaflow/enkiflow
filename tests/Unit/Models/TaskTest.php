<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\Task;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenancyTestCase;

class TaskTest extends TenancyTestCase
{
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a project for all tests
        $this->project = Project::factory()->create([
            'user_id' => $this->owner->id,
        ]);
    }

    #[Test]
    public function it_belongs_to_a_project()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
        ]);

        $this->assertInstanceOf(Project::class, $task->project);
        $this->assertEquals($this->project->id, $task->project->id);
    }

    #[Test]
    public function it_belongs_to_a_user()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
        ]);

        $this->assertEquals($this->owner->id, $task->user_id);
        $this->assertNotNull($task->user_id);
    }

    #[Test]
    public function it_can_filter_by_status()
    {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'status' => 'pending',
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'status' => 'in_progress',
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'status' => 'completed',
        ]);

        $this->assertCount(1, Task::pending()->get());
        $this->assertCount(1, Task::inProgress()->get());
        $this->assertCount(1, Task::completed()->get());
    }

    #[Test]
    public function it_can_be_marked_as_completed()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'status' => 'pending',
            'completed_at' => null,
        ]);

        $task->markAsCompleted();

        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    #[Test]
    public function it_can_be_marked_as_in_progress()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'status' => 'pending',
        ]);

        $task->markAsInProgress();

        $this->assertEquals('in_progress', $task->status);
    }

    #[Test]
    public function it_can_be_marked_as_pending()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $task->markAsPending();

        $this->assertEquals('pending', $task->status);
        $this->assertNull($task->completed_at);
    }
}
