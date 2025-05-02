<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use App\Services\ProjectService;
use Mockery;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{

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
        // Create mock data instead of using factories
        $projects = collect([
            (object)['id' => 1, 'name' => 'Project 1'],
            (object)['id' => 2, 'name' => 'Project 2'],
            (object)['id' => 3, 'name' => 'Project 3'],
        ]);
        
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
        $user = (object)['id' => 1, 'name' => 'Test User'];
        $projectData = [
            'name' => 'Test Project',
            'description' => 'This is a test project',
            'user_id' => $user->id,
            'status' => 'active',
        ];
        
        $project = (object)array_merge($projectData, ['id' => 1]);
        
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
        
        $project = (object)[
            'id' => $projectId,
            'name' => 'Updated Project',
            'description' => 'This is an updated project',
        ];
        
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
        $project = (object)[
            'id' => $projectId,
            'status' => 'active',
            'completed_at' => null,
        ];
        
        // Set up the mock to simulate the project being found
        $this->mockRepository->shouldReceive('find')
            ->once()
            ->with($projectId)
            ->andReturn($project);
        
        // Define the mock result
        $mockResult = (object)[
            'id' => $projectId,
            'status' => 'completed',
            'completed_at' => now(),
        ];
        
        // Set up mock method
        $project->shouldReceive('markAsCompleted')
            ->once()
            ->andReturn(true);
            
        // Call the service method
        $result = $this->service->markProjectAsCompleted($projectId);
        
        // Assert the result
        $this->assertInstanceOf(\stdClass::class, $result);
    }

    /** @test */
    public function it_can_mark_a_project_as_active()
    {
        // Create test data
        $projectId = 1;
        $project = (object)[
            'id' => $projectId,
            'status' => 'completed',
            'completed_at' => now(),
        ];
        
        // Set up the mock to simulate the project being found
        $this->mockRepository->shouldReceive('find')
            ->once()
            ->with($projectId)
            ->andReturn($project);
        
        // Define the mock result
        $mockResult = (object)[
            'id' => $projectId,
            'status' => 'active',
            'completed_at' => null,
        ];
        
        // Set up mock method
        $project->shouldReceive('markAsActive')
            ->once()
            ->andReturn(true);
            
        // Call the service method
        $result = $this->service->markProjectAsActive($projectId);
        
        // Assert the result
        $this->assertInstanceOf(\stdClass::class, $result);
    }
}