<?php

namespace Tests\Feature\Tenant;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenancyTestCase;

class TaskControllerTest extends TenancyTestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize a tenant for testing
        $tenant = $this->initializeTenant();
        
        // Create a user
        $this->user = User::factory()->create();
        
        // Create a project within the tenant
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function authenticated_users_can_view_task_listing()
    {
        // Create some tasks
        Task::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/Index')
            ->has('tasks.data', 3)
        );
    }

    /** @test */
    public function authenticated_users_can_create_tasks()
    {
        $taskData = [
            'title' => 'New Test Task',
            'description' => 'This is a test task',
            'project_id' => $this->project->id,
            'status' => 'pending',
            'priority' => 3,
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertStatus(302);
        $response->assertRedirect(route('tasks.show', Task::latest()->first()));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Test Task',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function authenticated_users_can_view_a_task()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.show', $task));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/Show')
            ->has('task')
            ->where('task.id', $task->id)
        );
    }

    /** @test */
    public function authenticated_users_can_update_tasks()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Original Task Title',
        ]);

        $updateData = [
            'title' => 'Updated Task Title',
            'description' => 'Updated description',
            'project_id' => $this->project->id,
            'status' => 'in_progress',
            'priority' => 4,
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'user_id' => $this->user->id,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('tasks.update', $task), $updateData);

        $response->assertStatus(302);
        $response->assertRedirect(route('tasks.show', $task));
        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertEquals('Updated Task Title', $task->title);
        $this->assertEquals('in_progress', $task->status);
    }

    /** @test */
    public function authenticated_users_can_delete_tasks()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('tasks.destroy', $task));

        $response->assertStatus(302);
        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    /** @test */
    public function authenticated_users_can_mark_tasks_as_completed()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.complete', $task));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    /** @test */
    public function authenticated_users_can_mark_tasks_as_in_progress()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.in-progress', $task));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertEquals('in_progress', $task->status);
    }

    /** @test */
    public function authenticated_users_can_add_comments_to_tasks()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $commentData = [
            'content' => 'This is a test comment',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), $commentData);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test comment',
        ]);
    }
}