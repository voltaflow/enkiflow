<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Space;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateTestTenant extends Command
{
    protected $signature = 'tenant:create-test';
    protected $description = 'Create a test tenant for multi-tenancy testing';

    public function handle()
    {
        $this->info('🚀 Creating test tenant for Enkiflow...');
        $this->newLine();

        // 1. Create or find test user
        $this->info('1. Checking test user...');
        $user = User::where('email', 'test@example.com')->first();

        if (!$user) {
            $this->info('   Creating test user...');
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $this->info("   ✅ User created: {$user->name} ({$user->email})");
        } else {
            $this->info("   ✅ Existing user: {$user->name} ({$user->email})");
        }

        // 2. Create Space (tenant)
        $this->newLine();
        $this->info('2. Creating Space (tenant)...');

        try {
            // Remove existing test tenant if exists  
            $existingSpace = Space::whereJsonContains('data->name', 'Espacio de Prueba')->first();
            if ($existingSpace) {
                $this->info('   Removing existing test space...');
                $existingSpace->delete();
            }

            // Create new space without triggering events automatically
            $space = new Space();
            $space->id = 'test-space-' . uniqid();
            $space->owner_id = $user->id;
            $space->data = [
                'name' => 'Espacio de Prueba',
                'plan' => 'free'
            ];
            
            // Save without triggering events
            $space->saveQuietly();
            
            $this->info("   ✅ Space created: {$space->data['name']} (ID: {$space->id})");
            
            // 3. Create domain
            $this->newLine();
            $this->info('3. Creating domain...');
            
            // Remove existing domain if exists
            $existingDomain = \Stancl\Tenancy\Database\Models\Domain::where('domain', 'prueba')->first();
            if ($existingDomain) {
                $this->info('   Removing existing test domain...');
                $existingDomain->delete();
            }
            
            $domain = $space->domains()->create([
                'domain' => 'prueba'
            ]);
            $this->info("   ✅ Domain created: {$domain->domain}");
            
            // 4. Create database manually
            $this->newLine();
            $this->info('4. Creating tenant database...');
            $databaseName = 'tenant' . str_replace('-', '', $space->id);
            
            try {
                // Drop database if exists
                DB::connection()->getPdo()->exec("DROP DATABASE IF EXISTS \"{$databaseName}\"");
                
                // Create new database
                DB::connection()->getPdo()->exec("CREATE DATABASE \"{$databaseName}\"");
                $this->info("   ✅ Database created: {$databaseName}");
            } catch (\Exception $e) {
                $this->error("   ❌ Error creating database: " . $e->getMessage());
                return;
            }
            
            // 5. Run tenant migrations
            $this->newLine();
            $this->info('5. Running tenant migrations...');
            
            // Configure tenant database connection
            config(['database.connections.tenant_temp' => [
                'driver' => 'pgsql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '5432'),
                'database' => $databaseName,
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ]]);
            
            // Run migrations
            $this->call('migrate', [
                '--database' => 'tenant_temp',
                '--path' => 'database/migrations/tenant',
                '--force' => true
            ]);
            
            $this->info('   ✅ Migrations executed');
            
            $this->newLine();
            $this->info('🎉 Tenant created successfully!');
            $this->table(['Property', 'Value'], [
                ['ID', $space->id],
                ['Name', $space->data['name']],
                ['Domain', $domain->domain],
                ['Database', $databaseName],
                ['Owner', $user->name],
                ['Access URL', 'http://prueba.enkiflow.test']
            ]);
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
