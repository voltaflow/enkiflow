<?php

namespace Tests\Unit\Models;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenancyTestCase;

class CommentTest extends TenancyTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_task()
    {
        $task = Task::factory()->create();
        $comment = Comment::factory()->create(['task_id' => $task->id]);

        $this->assertInstanceOf(Task::class, $comment->task);
        $this->assertEquals($task->id, $comment->task->id);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }

    /** @test */
    public function it_can_update_its_content()
    {
        $comment = Comment::factory()->create(['content' => 'Original content']);
        $newContent = 'Updated content';

        $comment->updateContent($newContent);

        $this->assertEquals($newContent, $comment->content);
        $this->assertNotNull($comment->edited_at);
    }
}