<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProjectPermission;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\Space;
use App\Models\User;
use App\Services\ProjectPermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectPermissionResolver $resolver;
    protected Space $tenant;
    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable feature flag
        Config::set('features.project_permissions', true);

        // Create resolver
        $this->resolver = app(ProjectPermissionResolver::class);

        // Create tenant and initialize
        $this->tenant = Space::factory()->create();
        tenancy()->initialize($this->tenant);

        // Create test data
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();

        // Add user to space
        $this->tenant->users()->attach($this->user, ['role' => 'member']);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }

    public function test_user_with_no_permissions_cannot_access_project(): void
    {
        $hasPermission = $this->resolver->userHasPermission(
            $this->user,
            $this->project,
            ProjectPermission::VIEW_REPORTS
        );

        $this->assertFalse($hasPermission);
    }

    public function test_user_with_role_gets_default_permissions(): void
    {
        // Assign user as member
        $this->resolver->updateUserRole(
            $this->user,
            $this->project,
            ProjectRole::MEMBER
        );

        // Member should be able to edit content
        $canEdit = $this->resolver->userHasPermission(
            $this->user,
            $this->project,
            ProjectPermission::EDIT_CONTENT
        );
        $this->assertTrue($canEdit);

        // Member should NOT be able to manage project
        $canManage = $this->resolver->userHasPermission(
            $this->user,
            $this->project,
            ProjectPermission::MANAGE_PROJECT
        );
        $this->assertFalse($canManage);
    }

    public function test_explicit_permission_overrides_role_default(): void
    {
        // Assign user as viewer (no default permissions)
        $this->resolver->updateUserRole(
            $this->user,
            $this->project,
            ProjectRole::VIEWER
        );

        // Viewer should NOT have edit permission by default
        $canEdit = $this->resolver->userHasPermission(
            $this->user,
            $this->project,
            ProjectPermission::EDIT_CONTENT
        );
        $this->assertFalse($canEdit);

        // Grant explicit permission
        $this->resolver->grantPermission(
            $this->user,
            $this->project,
            ProjectPermission::EDIT_CONTENT
        );

        // Now viewer should have edit permission
        $canEdit = $this->resolver->userHasPermission(
            $this->user,
            $this->project,
            ProjectPermission::EDIT_CONTENT
        );
        $this->assertTrue($canEdit);
    }

    public function test_revoke_permission_overrides_role_default(): void
    {
        // Assign user as editor (has EDIT_CONTENT by default)
        $this->resolver->updateUserRole(
            $this->user,
            $this->project,
            ProjectRole::EDITOR
        );

        // Editor should have edit permission by default
        $canEdit = $this->resolver->userHasPermission(
            $this->user,
            $this->project,
            ProjectPermission::EDIT_CONTENT
        );
        $this->assertTrue($canEdit);

        // Revoke the permission
        $this->resolver->revokePermission(
            $this->user,
            $this->project,
            ProjectPermission::EDIT_CONTENT
        );

        // Now editor should NOT have edit permission
        $canEdit = $this->resolver->userHasPermission(
            $this->user,
            $this->project,
            ProjectPermission::EDIT_CONTENT
        );
        $this->assertFalse($canEdit);
    }

    public function test_space_owner_has_all_permissions(): void
    {
        // Create a user who owns the space
        $owner = User::factory()->create();
        $this->tenant->owner_id = $owner->id;
        $this->tenant->save();

        // Owner should have all permissions without explicit assignment
        foreach (ProjectPermission::cases() as $permission) {
            $hasPermission = $this->resolver->userHasPermission(
                $owner,
                $this->project,
                $permission
            );
            $this->assertTrue($hasPermission, "Owner should have {$permission->value}");
        }
    }

    public function test_role_hierarchy(): void
    {
        $admin = ProjectRole::ADMIN;
        $manager = ProjectRole::MANAGER;
        $member = ProjectRole::MEMBER;
        $viewer = ProjectRole::VIEWER;

        $this->assertTrue($admin->isHigherThan($manager));
        $this->assertTrue($admin->isHigherThan($member));
        $this->assertTrue($admin->isHigherThan($viewer));
        $this->assertTrue($manager->isHigherThan($member));
        $this->assertTrue($manager->isHigherThan($viewer));
        $this->assertTrue($member->isHigherThan($viewer));

        $this->assertFalse($viewer->isHigherThan($member));
        $this->assertFalse($member->isHigherThan($manager));
    }

    public function test_get_user_permissions_returns_effective_permissions(): void
    {
        // Assign user as manager
        $this->resolver->updateUserRole(
            $this->user,
            $this->project,
            ProjectRole::MANAGER
        );

        // Revoke one default permission
        $this->resolver->revokePermission(
            $this->user,
            $this->project,
            ProjectPermission::DELETE_CONTENT
        );

        // Grant one non-default permission
        $this->resolver->grantPermission(
            $this->user,
            $this->project,
            ProjectPermission::MANAGE_INTEGRATIONS
        );

        $permissions = $this->resolver->getUserPermissions($this->user, $this->project);
        $permissionValues = $permissions->map(fn($p) => $p->value)->toArray();

        // Should have manager defaults minus DELETE_CONTENT
        $this->assertContains(ProjectPermission::MANAGE_MEMBERS->value, $permissionValues);
        $this->assertContains(ProjectPermission::EDIT_CONTENT->value, $permissionValues);
        $this->assertNotContains(ProjectPermission::DELETE_CONTENT->value, $permissionValues);

        // Should have explicitly granted permission
        $this->assertContains(ProjectPermission::MANAGE_INTEGRATIONS->value, $permissionValues);

        // Should NOT have admin-only permission
        $this->assertNotContains(ProjectPermission::MANAGE_PROJECT->value, $permissionValues);
    }

    public function test_expired_permissions_are_not_active(): void
    {
        // Add permission that expires in the past
        DB::connection('tenant')
            ->table('project_permissions')
            ->insert([
                'project_id' => $this->project->id,
                'user_id' => $this->user->id,
                'role' => ProjectRole::ADMIN->value,
                'is_active' => true,
                'expires_at' => now()->subDay(), // Expired yesterday
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // Clear cache to force fresh lookup
        $this->resolver->clearCache($this->user, $this->project);

        // User should not have any permissions
        $hasPermission = $this->resolver->userHasPermission(
            $this->user,
            $this->project,
            ProjectPermission::MANAGE_PROJECT
        );

        $this->assertFalse($hasPermission);
    }

    public function test_inactive_permissions_are_not_granted(): void
    {
        // Add inactive admin permission
        DB::connection('tenant')
            ->table('project_permissions')
            ->insert([
                'project_id' => $this->project->id,
                'user_id' => $this->user->id,
                'role' => ProjectRole::ADMIN->value,
                'is_active' => false, // Inactive
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // Clear cache to force fresh lookup
        $this->resolver->clearCache($this->user, $this->project);

        // User should not have any permissions
        $hasPermission = $this->resolver->userHasPermission(
            $this->user,
            $this->project,
            ProjectPermission::MANAGE_PROJECT
        );

        $this->assertFalse($hasPermission);
    }
}