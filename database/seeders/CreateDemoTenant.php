<?php

namespace Database\Seeders;

use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class CreateDemoTenant extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user if not exists
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Usuario de Prueba',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Usuario: {$user->email}");

        // Check if tenant already exists
        $existingSpace = Space::find('demo');
        if ($existingSpace) {
            $this->command->warn("El tenant 'demo' ya existe");

            return;
        }

        // Create tenant using raw SQL to bypass model issues
        DB::table('tenants')->insert([
            'id' => 'demo',
            'name' => 'Demo Company',
            'slug' => 'demo',
            'owner_id' => $user->id,
            'data' => json_encode([
                'plan' => 'free',
                'settings' => [],
            ]),
            'auto_tracking_enabled' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create domain
        DB::table('domains')->insert([
            'domain' => 'demo',
            'tenant_id' => 'demo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attach user to space
        DB::table('space_users')->insert([
            'tenant_id' => 'demo',
            'user_id' => $user->id,
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Tenant "demo" creado exitosamente');

        // Run tenant migrations
        $space = Space::find('demo');
        if ($space) {
            $space->run(function () {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
            });

            // Seed tenant data
            $space->run(function () use ($user) {
                // Create default time categories
                DB::table('time_categories')->insert([
                    ['name' => 'Development', 'color' => '#3B82F6', 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'Meeting', 'color' => '#10B981', 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'Research', 'color' => '#8B5CF6', 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'Support', 'color' => '#F59E0B', 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'Administration', 'color' => '#EF4444', 'created_at' => now(), 'updated_at' => now()],
                ]);

                // Create a sample project
                $projectId = DB::table('projects')->insertGetId([
                    'name' => 'Welcome to Demo Company',
                    'description' => 'This is your first project. Feel free to edit or delete it.',
                    'status' => 'active',
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create a sample task
                DB::table('tasks')->insert([
                    'title' => 'Get started with time tracking',
                    'description' => 'Try out the timer feature to track your work time.',
                    'project_id' => $projectId,
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'priority' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            $this->command->info('Datos de tenant sembrados exitosamente');
        }

        $domain = config('app.domain', 'enkiflow.test');
        $this->command->info('=== Información de acceso ===');
        $this->command->info("URL: http://demo.{$domain}");
        $this->command->info('Email: test@example.com');
        $this->command->info('Password: password');
    }
}
