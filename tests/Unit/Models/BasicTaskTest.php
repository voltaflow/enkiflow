<?php

namespace Tests\Unit\Models;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_has_valid_fillable_attributes()
    {
        $task = new Task;
        $fillable = $task->getFillable();

        $this->assertIsArray($fillable);
        $this->assertContains('title', $fillable);
        $this->assertContains('description', $fillable);
    }
}
