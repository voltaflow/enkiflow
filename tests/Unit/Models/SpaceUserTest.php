<?php

namespace Tests\Unit\Models;

use App\Models\Space;
use App\Models\SpaceUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaceUserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_user_relationship()
    {
        $user = User::factory()->create();
        $space = Space::create([
            'id' => 'test-space',
            'name' => 'Test Space',
            'owner_id' => $user->id,
            'data' => [],
        ]);
        
        $spaceUser = SpaceUser::create([
            'tenant_id' => $space->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
        
        $this->assertInstanceOf(User::class, $spaceUser->user);
        $this->assertEquals($user->id, $spaceUser->user->id);
    }

    /** @test */
    public function it_has_space_relationship()
    {
        $user = User::factory()->create();
        $space = Space::create([
            'id' => 'test-space',
            'name' => 'Test Space',
            'owner_id' => $user->id,
            'data' => [],
        ]);
        
        $spaceUser = SpaceUser::create([
            'tenant_id' => $space->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
        
        $this->assertInstanceOf(Space::class, $spaceUser->space);
        $this->assertEquals($space->id, $spaceUser->space->id);
    }

    /** @test */
    public function it_can_check_for_admin_role()
    {
        $user = User::factory()->create();
        $space = Space::create([
            'id' => 'test-space',
            'name' => 'Test Space',
            'owner_id' => $user->id,
            'data' => [],
        ]);
        
        // Admin user
        $adminUser = SpaceUser::create([
            'tenant_id' => $space->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
        
        // Member user
        $memberUser = SpaceUser::create([
            'tenant_id' => $space->id,
            'user_id' => User::factory()->create()->id,
            'role' => 'member',
        ]);
        
        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($memberUser->isAdmin());
    }

    /** @test */
    public function it_can_check_for_member_role()
    {
        $user = User::factory()->create();
        $space = Space::create([
            'id' => 'test-space',
            'name' => 'Test Space',
            'owner_id' => $user->id,
            'data' => [],
        ]);
        
        // Admin user
        $adminUser = SpaceUser::create([
            'tenant_id' => $space->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
        
        // Member user
        $memberUser = SpaceUser::create([
            'tenant_id' => $space->id,
            'user_id' => User::factory()->create()->id,
            'role' => 'member',
        ]);
        
        $this->assertTrue($memberUser->isMember());
        $this->assertFalse($adminUser->isMember());
    }

    /** @test */
    public function it_can_check_for_any_role()
    {
        $user = User::factory()->create();
        $space = Space::create([
            'id' => 'test-space',
            'name' => 'Test Space',
            'owner_id' => $user->id,
            'data' => [],
        ]);
        
        // Admin user
        $adminUser = SpaceUser::create([
            'tenant_id' => $space->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
        
        // Member user
        $memberUser = SpaceUser::create([
            'tenant_id' => $space->id,
            'user_id' => User::factory()->create()->id,
            'role' => 'member',
        ]);
        
        $this->assertTrue($adminUser->hasRole('admin'));
        $this->assertTrue($memberUser->hasRole('member'));
        $this->assertFalse($adminUser->hasRole('member'));
        $this->assertFalse($memberUser->hasRole('admin'));
        $this->assertFalse($memberUser->hasRole('owner'));
    }
}
