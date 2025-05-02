<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectRepositoryInterface $mockRepository;
    protected ProjectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock repository
        $this->mockRepository = Mockery::mock(ProjectRepositoryInterface::class);
        
        // Create service with mock repository
        $this->service = new ProjectService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_all_projects()
    {
        // Create some test projects
        $projects = Project::factory()->count(3)->make();
        
        // Set up the mock repository
        $this->mockRepository->shouldReceive('all')
            ->once()
            ->andReturn($projects);
        
        // Call the service method
        $result = $this->service->getAllProjects();
        
        // Assert the result
        $this->assertCount(3, $result);
        $this->assertSame($projects, $result);
    }

    /** @test */
    public function it_can_create_a_project()
    {
        // Create test data
        $user = User::factory()->make(['id' => 1]);
        $projectData = [
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => $user->id,
            'status' => 'active',
        ];
        
        $project = Project::factory()->make($projectData);
        
        // Set up the mock repository
        $this->mockRepository->shouldReceive('create')
            ->once()
            ->with($projectData)
            ->andReturn($project);
        
        // Call the service method
        $result = $this->service->createProject($projectData);
        
        // Assert the result
        $this->assertSame($project, $result);
        $this->assertEquals('Test Project', $result->name);
    }

    /** @test */
    public function it_can_update_a_project()
    {
        // Create test data
        $projectId = 1;
        $projectData = [
            'name' => 'Updated Project',
            'description' => 'This is an updated project',
        ];
        
        $project = Project::factory()->make([
            'id' => $projectId,
            'name' => 'Updated Project',
        ]);
        
        // Set up the mock repository
        $this->mockRepository->shouldReceive('update')
            ->once()
            ->with($projectId, $projectData)
            ->andReturn($project);
        
        // Call the service method
        $result = $this->service->updateProject($projectId, $projectData);
        
        // Assert the result
        $this->assertSame($project, $result);
        $this->assertEquals('Updated Project', $result->name);
    }

    /** @test */
    public function it_can_delete_a_project()
    {
        // Create test data
        $projectId = 1;
        
        // Set up the mock repository
        $this->mockRepository->shouldReceive('delete')
            ->once()
            ->with($projectId)
            ->andReturn(true);
        
        // Call the service method
        $result = $this->service->deleteProject($projectId);
        
        // Assert the result
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_mark_a_project_as_completed()
    {
        // Create test data
        $projectId = 1;
        $project = Project::factory()->make([
            'id' => $projectId,
            'status' => 'active',
            'completed_at' => null,
        ]);
        
        // Set up the mock to simulate the project being found
        $this->mockRepository->shouldReceive('find')
            ->once()
            ->with($projectId)
            ->andReturn($project);
        
        // Call the service method
        $result = $this->service->markProjectAsCompleted($projectId);
        
        // Assert the result
        $this->assertEquals('completed', $result->status);
        $this->assertNotNull($result->completed_at);
    }

    /** @test */
    public function it_can_mark_a_project_as_active()
    {
        // Create test data
        $projectId = 1;
        $project = Project::factory()->make([
            'id' => $projectId,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        // Set up the mock to simulate the project being found
        $this->mockRepository->shouldReceive('find')
            ->once()
            ->with($projectId)
            ->andReturn($project);
        
        // Call the service method
        $result = $this->service->markProjectAsActive($projectId);
        
        // Assert the result
        $this->assertEquals('active', $result->status);
        $this->assertNull($result->completed_at);
    }
}