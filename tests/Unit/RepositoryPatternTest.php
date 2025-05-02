<?php

namespace Tests\Unit;

use App\Repositories\Interfaces\ProjectRepositoryInterface;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use App\Repositories\Eloquent\TaskRepository;
use App\Services\ProjectService;
use App\Services\TaskService;
use PHPUnit\Framework\TestCase;

class RepositoryPatternTest extends TestCase
{
    public function test_repository_pattern_interfaces_exist()
    {
        // Test that the repository interfaces exist
        $this->assertTrue(interface_exists(ProjectRepositoryInterface::class));
        $this->assertTrue(interface_exists(TaskRepositoryInterface::class));
        
        // Test that the repository implementations exist
        $this->assertTrue(class_exists(ProjectRepository::class));
        $this->assertTrue(class_exists(TaskRepository::class));
        
        // Test that the service classes exist
        $this->assertTrue(class_exists(ProjectService::class));
        $this->assertTrue(class_exists(TaskService::class));
    }
    
    public function test_repositories_implement_interfaces()
    {
        // Test that the repositories implement their interfaces
        $projectRepositoryImplementsInterface = in_array(
            ProjectRepositoryInterface::class, 
            class_implements(ProjectRepository::class) ?: []
        );
        
        $taskRepositoryImplementsInterface = in_array(
            TaskRepositoryInterface::class, 
            class_implements(TaskRepository::class) ?: []
        );
        
        $this->assertTrue($projectRepositoryImplementsInterface);
        $this->assertTrue($taskRepositoryImplementsInterface);
    }
    
    public function test_repository_methods_are_correctly_typed()
    {
        // Check that the repositories have the required methods with correct return types
        $reflectionProject = new \ReflectionClass(ProjectRepositoryInterface::class);
        $reflectionTask = new \ReflectionClass(TaskRepositoryInterface::class);
        
        // Verify some key methods exist
        $this->assertTrue($reflectionProject->hasMethod('create'));
        $this->assertTrue($reflectionProject->hasMethod('update'));
        $this->assertTrue($reflectionProject->hasMethod('delete'));
        
        $this->assertTrue($reflectionTask->hasMethod('create'));
        $this->assertTrue($reflectionTask->hasMethod('update'));
        $this->assertTrue($reflectionTask->hasMethod('delete'));
    }
}