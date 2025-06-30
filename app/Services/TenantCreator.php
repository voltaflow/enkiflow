<?php

namespace App\Services;

use App\Models\Space;
use App\Models\User;
use Stancl\Tenancy\Database\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class TenantCreator
{
    /**
     * Create a new tenant with subdomain.
     */
    public function create(User $owner, array $data): Space
    {
        // Generate subdomain
        $subdomain = Space::generateSubdomain($data['name']);

        // Create the tenant (without transaction for database creation)
        $space = Space::create([
            'id' => $data['id'] ?? null,
            'name' => $data['name'],
            'slug' => $subdomain,
            'owner_id' => $owner->id,
            'data' => [
                'plan' => $data['plan'] ?? 'free',
                'settings' => $data['settings'] ?? [],
            ],
            'auto_tracking_enabled' => $data['auto_tracking_enabled'] ?? false,
            'status' => 'active',
        ]);

        // Create the domain with full subdomain
        $baseDomain = get_base_domain();
        $fullDomain = $subdomain . '.' . $baseDomain;
        $space->domains()->create([
            'domain' => $fullDomain,
        ]);

        // Add the owner to the space with admin role
        $space->users()->attach($owner->id, [
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Wait for database creation with retry logic
        $maxRetries = 10;
        $retryDelay = 1; // seconds
        $dbCreated = false;
        
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                // Check if database exists
                $dbName = 'tenant' . $space->id;
                $result = DB::connection('pgsql')->select(
                    "SELECT 1 FROM pg_database WHERE datname = ?",
                    [$dbName]
                );
                
                if (count($result) > 0) {
                    $dbCreated = true;
                    Log::info('Database ready for tenant', ['database' => $dbName, 'attempts' => $i + 1]);
                    break;
                }
            } catch (\Exception $e) {
                Log::warning('Database not ready yet', ['attempt' => $i + 1, 'error' => $e->getMessage()]);
            }
            
            sleep($retryDelay);
        }
        
        if (!$dbCreated) {
            throw new \RuntimeException('Database creation timed out for tenant: ' . $space->id);
        }

        // Run tenant migrations
        $space->run(function () {
            \Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        });

        // Seed initial data if needed
        if ($data['seed_data'] ?? false) {
            $this->seedTenantData($space);
        }

        return $space;
    }

    /**
     * Add a full domain to an existing tenant.
     */
    public function addDomain(Space $space, string $domain): Domain
    {
        return $space->domains()->create([
            'domain' => $domain,
        ]);
    }

    /**
     * Update tenant settings.
     */
    public function updateSettings(Space $space, array $settings): Space
    {
        $data = $space->data ?? [];
        $data['settings'] = array_merge($data['settings'] ?? [], $settings);

        $space->update(['data' => $data]);

        return $space;
    }

    /**
     * Activate or deactivate a tenant.
     */
    public function setActive(Space $space, bool $active): Space
    {
        $space->update(['status' => $active ? 'active' : 'inactive']);

        return $space;
    }

    /**
     * Seed initial data for the tenant.
     */
    protected function seedTenantData(Space $space): void
    {
        $space->run(function () use ($space) {
            // Create default time categories
            \App\Models\TimeCategory::insert([
                ['name' => 'Development', 'color' => '#3B82F6', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Meeting', 'color' => '#10B981', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Research', 'color' => '#8B5CF6', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Support', 'color' => '#F59E0B', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Administration', 'color' => '#EF4444', 'created_at' => now(), 'updated_at' => now()],
            ]);

            // Create default app categories
            \App\Models\AppCategory::insert([
                ['app_name' => 'Visual Studio Code', 'category' => 'Development', 'productivity_level' => 'productive', 'created_at' => now(), 'updated_at' => now()],
                ['app_name' => 'PhpStorm', 'category' => 'Development', 'productivity_level' => 'productive', 'created_at' => now(), 'updated_at' => now()],
                ['app_name' => 'Slack', 'category' => 'Communication', 'productivity_level' => 'neutral', 'created_at' => now(), 'updated_at' => now()],
                ['app_name' => 'Microsoft Teams', 'category' => 'Communication', 'productivity_level' => 'neutral', 'created_at' => now(), 'updated_at' => now()],
                ['app_name' => 'Chrome', 'category' => 'Web Browsing', 'productivity_level' => 'neutral', 'created_at' => now(), 'updated_at' => now()],
                ['app_name' => 'YouTube', 'category' => 'Entertainment', 'productivity_level' => 'distracting', 'created_at' => now(), 'updated_at' => now()],
            ]);

            // Create a sample project
            $project = \App\Models\Project::create([
                'name' => 'Welcome to '.$space->name,
                'description' => 'This is your first project. Feel free to edit or delete it.',
                'status' => 'active',
                'user_id' => $space->owner_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create a sample task
            \App\Models\Task::create([
                'title' => 'Get started with time tracking',
                'description' => 'Try out the timer feature to track your work time.',
                'project_id' => $project->id,
                'user_id' => $space->owner_id,
                'status' => 'pending',
                'priority' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
