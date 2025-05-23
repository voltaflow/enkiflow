<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenancyTestCase;

class ProjectTest extends TenancyTestCase
{
    #[Test]
    public function it_has_user_relationship()
    {
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => $this->owner->id,
            'status' => 'active',
        ]);
        
        $this->assertEquals($this->owner->id, $project->user_id);
        $this->assertNotNull($project->user_id);
    }

    #[Test]
    public function it_has_active_scope()
    {
        // Create an active project
        $activeProject = Project::create([
            'name' => 'Active Project',
            'description' => 'This is an active project',
            'user_id' => $this->owner->id,
            'status' => 'active',
        ]);
        
        // Create a completed project
        $completedProject = Project::create([
            'name' => 'Completed Project',
            'description' => 'This is a completed project',
            'user_id' => $this->owner->id,
            'status' => 'completed',
        ]);
        
        $activeProjects = Project::active()->get();
        
        $this->assertCount(1, $activeProjects);
        $this->assertEquals($activeProject->id, $activeProjects->first()->id);
    }

    #[Test]
    public function it_has_completed_scope()
    {
        // Create an active project
        $activeProject = Project::create([
            'name' => 'Active Project',
            'description' => 'This is an active project',
            'user_id' => $this->owner->id,
            'status' => 'active',
        ]);
        
        // Create a completed project
        $completedProject = Project::create([
            'name' => 'Completed Project',
            'description' => 'This is a completed project',
            'user_id' => $this->owner->id,
            'status' => 'completed',
        ]);
        
        $completedProjects = Project::completed()->get();
        
        $this->assertCount(1, $completedProjects);
        $this->assertEquals($completedProject->id, $completedProjects->first()->id);
    }

    #[Test]
    public function it_can_be_marked_as_completed()
    {
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => $this->owner->id,
            'status' => 'active',
        ]);
        
        $project->markAsCompleted();
        
        $this->assertEquals('completed', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->completed_at);
    }

    #[Test]
    public function it_can_be_marked_as_active()
    {
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => $this->owner->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        $project->markAsActive();
        
        $this->assertEquals('active', $project->fresh()->status);
        $this->assertNull($project->fresh()->completed_at);
    }

    #[Test]
    public function it_casts_settings_attribute_to_array()
    {
        $settings = ['color' => 'blue', 'priority' => 'high'];
        
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => $this->owner->id,
            'status' => 'active',
            'settings' => $settings,
        ]);
        
        $this->assertIsArray($project->settings);
        $this->assertEquals($settings, $project->settings);
    }

    #[Test]
    public function it_uses_soft_deletes()
    {
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => $this->owner->id,
            'status' => 'active',
        ]);
        
        $project->delete();
        
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        $this->assertNotNull($project->deleted_at);
    }
}