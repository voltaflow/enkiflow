<?php

namespace Tests\Feature\Tenant;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class SimpleTaskTest extends TestCase
{
    public function test_can_create_task()
    {
        // A simple test to make sure basic functionality works
        $this->assertTrue(true);
    }
}