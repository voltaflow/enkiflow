<?php

namespace Tests;

use App\Models\Space;
use App\Models\User;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Database\DatabaseManager;
use Stancl\Tenancy\Events\TenancyInitialized;
use Stancl\Tenancy\Tenancy;

abstract class TenancyTestCase extends TestCase
{
    /**
     * Current tenant for the test.
     *
     * @var \App\Models\Space
     */
    protected $currentTenant;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Disable automatic database creation
        $this->skipTenantDatabaseCreation();
        
        // Configure the application to use the test database for tenant connections
        config(['database.connections.tenant.database' => env('DB_DATABASE', 'testing')]);
    }

    /**
     * Configure tenancy to skip actual database creation.
     *
     * @return void
     */
    protected function skipTenantDatabaseCreation(): void
    {
        // Replace real DatabaseManager with a mock to skip actual tenant DB creation
        $this->mock(DatabaseManager::class, function ($mock) {
            $mock->shouldReceive('createDatabase')->andReturn(true);
            $mock->shouldReceive('deleteDatabase')->andReturn(true);
            $mock->shouldReceive('databaseExists')->andReturn(true);
        });
    }

    /**
     * Initialize a tenant for testing.
     *
     * @param \App\Models\Space|null $tenant
     * @return \App\Models\Space
     */
    protected function initializeTenant(?Space $tenant = null): Space
    {
        // Create a tenant if not provided
        $tenant = $tenant ?? Space::create([
            'id' => 'test-tenant-' . md5(uniqid()),
            'name' => 'Test Tenant',
            'owner_id' => User::factory()->create()->id,
            'data' => [],
        ]);

        // Initialize tenancy for this tenant
        // This will also fire TenancyInitialized event
        app(Tenancy::class)->initialize($tenant);
        $this->currentTenant = $tenant;

        // Mock the database bootstrapper to connect to the testing database instead of a tenant DB
        app()->bind(DatabaseTenancyBootstrapper::class, function () {
            $bootstrapper = $this->createMock(TenancyBootstrapper::class);
            $bootstrapper->method('bootstrap')->willReturn(true);
            $bootstrapper->method('revert')->willReturn(true);
            return $bootstrapper;
        });
        
        // Run the tenant migrations
        $this->artisan('migrate', [
            '--path' => 'database/migrations/tenant',
            '--database' => 'tenant',
        ]);

        return $tenant;
    }

    /**
     * End tenancy for the current test.
     *
     * @return void
     */
    protected function endTenancy(): void
    {
        if ($this->currentTenant) {
            app(Tenancy::class)->end();
            $this->currentTenant = null;
        }
    }

    /**
     * Clean up after the test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->endTenancy();
        parent::tearDown();
    }

    /**
     * Create a tenant owner with optional custom attributes.
     *
     * @param array $attributes
     * @return \App\Models\User
     */
    protected function createTenantOwner(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Create a tenant with domain and owner.
     *
     * @param array $attributes
     * @param \App\Models\User|null $owner
     * @return \App\Models\Space
     */
    protected function createTenant(array $attributes = [], ?User $owner = null): Space
    {
        $owner = $owner ?? $this->createTenantOwner();
        
        $tenant = Space::create(array_merge([
            'id' => 'test-tenant-' . md5(uniqid()),
            'name' => 'Test Tenant',
            'owner_id' => $owner->id,
            'data' => [],
        ], $attributes));

        // Create domain for the tenant
        $domain = strtolower(str_replace(' ', '-', $tenant->id)) . '.localhost';
        $tenant->domains()->create(['domain' => $domain]);

        // Add owner as a member with admin role
        $tenant->users()->attach($owner->id, ['role' => 'admin']);
        
        return $tenant;
    }
}
