<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenancyTestCase;

class ProjectTest extends TenancyTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize tenancy for testing
        $tenant = $this->createTenant();
        $this->initializeTenant($tenant);
    }

    /** @test */
    public function it_has_user_relationship()
    {
        $user = User::factory()->create();
        
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        
        $this->assertInstanceOf(User::class, $project->user);
        $this->assertEquals($user->id, $project->user->id);
    }

    /** @test */
    public function it_has_active_scope()
    {
        // Create an active project
        $activeProject = Project::create([
            'name' => 'Active Project',
            'description' => 'This is an active project',
            'user_id' => User::factory()->create()->id,
            'status' => 'active',
        ]);
        
        // Create a completed project
        $completedProject = Project::create([
            'name' => 'Completed Project',
            'description' => 'This is a completed project',
            'user_id' => User::factory()->create()->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        // Test the scope
        $activeProjects = Project::active()->get();
        
        $this->assertCount(1, $activeProjects);
        $this->assertEquals($activeProject->id, $activeProjects->first()->id);
    }

    /** @test */
    public function it_has_completed_scope()
    {
        // Create an active project
        $activeProject = Project::create([
            'name' => 'Active Project',
            'description' => 'This is an active project',
            'user_id' => User::factory()->create()->id,
            'status' => 'active',
        ]);
        
        // Create a completed project
        $completedProject = Project::create([
            'name' => 'Completed Project',
            'description' => 'This is a completed project',
            'user_id' => User::factory()->create()->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        // Test the scope
        $completedProjects = Project::completed()->get();
        
        $this->assertCount(1, $completedProjects);
        $this->assertEquals($completedProject->id, $completedProjects->first()->id);
    }

    /** @test */
    public function it_can_be_marked_as_completed()
    {
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => User::factory()->create()->id,
            'status' => 'active',
        ]);
        
        // Initially it should be active
        $this->assertEquals('active', $project->status);
        $this->assertNull($project->completed_at);
        
        // Mark as completed
        $project->markAsCompleted();
        
        // Check that it's now completed
        $this->assertEquals('completed', $project->status);
        $this->assertNotNull($project->completed_at);
    }

    /** @test */
    public function it_can_be_marked_as_active()
    {
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => User::factory()->create()->id,
            'status' => 'completed',
            'completed_at' => now()->subDay(),
        ]);
        
        // Initially it should be completed
        $this->assertEquals('completed', $project->status);
        $this->assertNotNull($project->completed_at);
        
        // Mark as active
        $project->markAsActive();
        
        // Check that it's now active
        $this->assertEquals('active', $project->status);
        $this->assertNull($project->completed_at);
    }

    /** @test */
    public function it_casts_settings_attribute_to_array()
    {
        $settingsArray = [
            'notification_enabled' => true,
            'color' => 'blue',
            'priority' => 1,
        ];
        
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => User::factory()->create()->id,
            'status' => 'active',
            'settings' => $settingsArray,
        ]);
        
        // Get fresh instance from database
        $project = Project::find($project->id);
        
        // Check that settings is cast to array
        $this->assertIsArray($project->settings);
        $this->assertEquals($settingsArray, $project->settings);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => User::factory()->create()->id,
            'status' => 'active',
        ]);
        
        // Delete the project
        $project->delete();
        
        // It should not be found in a normal query
        $this->assertNull(Project::find($project->id));
        
        // But it should be found when including trashed
        $this->assertNotNull(Project::withTrashed()->find($project->id));
    }
}
