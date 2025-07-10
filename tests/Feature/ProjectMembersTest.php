<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Space;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectMembersTest extends TestCase
{
    use RefreshDatabase;

    protected $space;
    protected $owner;
    protected $member1;
    protected $member2;
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a space owner
        $this->owner = User::factory()->create();

        // Create a space
        $this->space = Space::create([
            'name' => 'Test Space',
            'slug' => 'test-space',
            'owner_id' => $this->owner->id,
        ]);

        // Add domain
        $this->space->domains()->create(['domain' => 'test-space']);

        // Initialize tenant
        tenancy()->initialize($this->space);

        // Create members
        $this->member1 = User::factory()->create();
        $this->member2 = User::factory()->create();

        // Add users to space
        $this->space->users()->attach([
            $this->owner->id => ['role' => 'owner'],
            $this->member1->id => ['role' => 'member'],
            $this->member2->id => ['role' => 'member'],
        ]);

        // Create a project
        $this->project = Project::create([
            'name' => 'Test Project',
            'description' => 'Test project description',
            'user_id' => $this->owner->id,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }

    public function test_can_get_project_members()
    {
        // Assign member1 to project
        $this->project->assignedUsers()->attach($this->member1->id, [
            'role' => 'member',
            'custom_rate' => 50.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/projects/{$this->project->id}/members");

        $response->assertOk()
            ->assertJsonCount(1, 'data.members')
            ->assertJsonFragment([
                'id' => $this->member1->id,
                'name' => $this->member1->name,
                'email' => $this->member1->email,
                'role' => 'member',
                'custom_rate' => '50.00',
            ]);
    }

    public function test_can_assign_users_to_project()
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/projects/{$this->project->id}/members", [
                'user_ids' => [$this->member1->id, $this->member2->id],
                'role' => 'manager',
                'custom_rate' => 75.00,
            ]);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Users assigned successfully',
                'data' => ['assigned_count' => 2],
            ]);

        // Verify in database
        $this->assertDatabaseHas('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->member1->id,
            'role' => 'manager',
            'custom_rate' => 75.00,
        ]);
    }

    public function test_can_update_user_role_in_project()
    {
        // First assign user
        $this->project->assignedUsers()->attach($this->member1->id, [
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->owner)
            ->putJson("/api/projects/{$this->project->id}/members/{$this->member1->id}", [
                'role' => 'manager',
                'custom_rate' => 100.00,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'User assignment updated successfully',
            ]);

        // Verify in database
        $this->assertDatabaseHas('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->member1->id,
            'role' => 'manager',
            'custom_rate' => 100.00,
        ]);
    }

    public function test_can_remove_user_from_project()
    {
        // First assign user
        $this->project->assignedUsers()->attach($this->member1->id, [
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/projects/{$this->project->id}/members/{$this->member1->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'User removed from project successfully',
            ]);

        // Verify removal
        $this->assertDatabaseMissing('project_user', [
            'project_id' => $this->project->id,
            'user_id' => $this->member1->id,
        ]);
    }

    public function test_get_available_users_excludes_assigned_users()
    {
        // Assign member1 to project
        $this->project->assignedUsers()->attach($this->member1->id, [
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/projects/{$this->project->id}/members/available");

        $response->assertOk()
            ->assertJsonCount(2, 'data') // owner and member2
            ->assertJsonFragment(['id' => $this->owner->id])
            ->assertJsonFragment(['id' => $this->member2->id])
            ->assertJsonMissing(['id' => $this->member1->id]);
    }
}