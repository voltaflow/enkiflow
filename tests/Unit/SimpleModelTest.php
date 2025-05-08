<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\Comment;
use App\Models\Tag;
use PHPUnit\Framework\TestCase;

class SimpleModelTest extends TestCase
{
    public function test_models_have_proper_attributes()
    {
        // Test Task model
        $task = new Task();
        $this->assertIsArray($task->getFillable());
        $this->assertContains('title', $task->getFillable());
        
        // Test Comment model
        $comment = new Comment();
        $this->assertIsArray($comment->getFillable());
        $this->assertContains('content', $comment->getFillable());
        
        // Test Tag model
        $tag = new Tag();
        $this->assertIsArray($tag->getFillable());
        $this->assertContains('name', $tag->getFillable());
    }
}