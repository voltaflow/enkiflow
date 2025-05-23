<?php

namespace Tests\Feature\Tenant;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Services\TaskService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenancyTestCase;

class TaskServiceTest extends TenancyTestCase
{
    protected TaskService $taskService;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->taskService = app(TaskService::class);
        
        // Create a project within the tenant
        $this->project = Project::factory()->create([
            'user_id' => $this->owner->id,
        ]);
    }

    #[Test]
    public function it_can_get_all_tasks()
    {
        // Create some tasks
        $tasks = Task::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
        ]);

        $result = $this->taskService->getAllTasks();

        $this->assertCount(3, $result);
    }

    #[Test]
    public function it_can_create_a_task()
    {
        $taskData = [
            'title' => 'New Test Task',
            'description' => 'This is a test task',
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'status' => 'pending',
            'priority' => 3,
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ];

        $task = $this->taskService->createTask($taskData);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('New Test Task', $task->title);
        $this->assertDatabaseHas('tasks', [
            'title' => 'New Test Task',
            'user_id' => $this->owner->id,
        ]);
    }

    #[Test]
    public function it_can_update_a_task()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'title' => 'Original Task Title',
        ]);

        $updateData = [
            'title' => 'Updated Task Title',
            'description' => 'Updated description',
        ];

        $updatedTask = $this->taskService->updateTask($task->id, $updateData);

        $this->assertEquals('Updated Task Title', $updatedTask->title);
        $this->assertEquals('Updated description', $updatedTask->description);
    }

    #[Test]
    public function it_can_delete_a_task()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
        ]);

        $result = $this->taskService->deleteTask($task->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    #[Test]
    public function it_can_mark_task_as_completed()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'status' => 'pending',
        ]);

        $updatedTask = $this->taskService->markTaskAsCompleted($task->id);

        $this->assertEquals('completed', $updatedTask->status);
        $this->assertNotNull($updatedTask->completed_at);
    }

    #[Test]
    public function it_can_mark_task_as_in_progress()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
            'status' => 'pending',
        ]);

        $updatedTask = $this->taskService->markTaskAsInProgress($task->id);

        $this->assertEquals('in_progress', $updatedTask->status);
    }

    #[Test]
    public function it_can_sync_tags()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->owner->id,
        ]);

        // Create tags first
        $tag1 = Tag::create(['name' => 'urgent']);
        $tag2 = Tag::create(['name' => 'important']);
        $tag3 = Tag::create(['name' => 'backend']);
        
        $tagIds = [$tag1->id, $tag2->id, $tag3->id];
        
        $this->taskService->syncTags($task->id, $tagIds);

        $task->refresh();
        $this->assertCount(3, $task->tags);
        $this->assertTrue($task->tags->pluck('name')->contains('urgent'));
    }
}