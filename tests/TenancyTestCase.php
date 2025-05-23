<?php

namespace Tests;

use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

abstract class TenancyTestCase extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Current tenant for the test.
     */
    protected ?Space $tenant = null;
    
    /**
     * The user acting as the tenant owner.
     */
    protected ?User $owner = null;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run central migrations first
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        
        // Create owner and tenant
        $this->owner = User::factory()->create();
        $this->tenant = $this->createTestTenant();
        
        // Configure tenant database connection to use the same database
        config([
            'database.connections.tenant' => config('database.connections.sqlite'),
            'tenancy.tenant_database_prefix' => 'tenant_',
            'tenancy.tenant_database_suffix' => '',
        ]);
        
        // Create a domain for testing
        $domain = $this->tenant->id . '.localhost';
        $this->tenant->domains()->create(['domain' => $domain]);
        
        // Set the HTTP host for testing
        $this->app['request']->headers->set('HOST', $domain);
        config(['app.url' => 'http://' . $domain]);
        
        // Initialize tenancy
        tenancy()->initialize($this->tenant);
        
        // Run tenant migrations
        $this->runTenantMigrations();
    }
    
    /**
     * Create a test tenant.
     */
    protected function createTestTenant(array $attributes = []): Space
    {
        $tenant = Space::create(array_merge([
            'id' => 'test_' . str()->random(8),
            'name' => 'Test Tenant',
            'owner_id' => $this->owner->id,
            'data' => [],
        ], $attributes));
        
        // Add owner as admin
        DB::table('space_users')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $this->owner->id,
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return $tenant;
    }
    
    /**
     * Run tenant migrations.
     */
    protected function runTenantMigrations(): void
    {
        $this->artisan('migrate', [
            '--path' => 'database/migrations/tenant',
            '--database' => 'tenant',
            '--force' => true,
        ])->assertSuccessful();
    }
    
    /**
     * Act as the tenant owner.
     */
    protected function actingAsTenantUser(?User $user = null): self
    {
        return $this->actingAs($user ?? $this->owner);
    }
    
    /**
     * Make a request to a tenant route.
     */
    protected function tenantRequest($method, $uri, array $data = [])
    {
        $domain = $this->tenant->domains()->first()->domain;
        
        return $this->withHeaders([
            'Host' => $domain,
        ])->$method($uri, $data);
    }
    
    /**
     * Create a new user for the tenant.
     */
    protected function createTenantUser(array $attributes = [], string $role = 'member'): User
    {
        $user = User::factory()->create($attributes);
        
        DB::table('space_users')->insert([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return $user;
    }
    
    /**
     * Clean up after the test.
     */
    protected function tearDown(): void
    {
        tenancy()->end();
        
        parent::tearDown();
    }
}