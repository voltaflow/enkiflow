<?php

namespace Database\Seeders;

use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Seeder;

class SpaceSeeder extends Seeder
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

        // Create a demo space (tenant)
        $space = Space::firstOrCreate(
            ['id' => 'demo-space'],
            [
                'name' => 'Espacio de Demostración',
                'owner_id' => $user->id,
                'data' => [
                    'plan' => 'free',
                ],
            ]
        );

        // Create a domain for the space
        $domain = $space->domains()->firstOrCreate([
            'domain' => 'demo.localhost',
        ]);

        // Attach the user to the space as admin
        if (!$space->users()->where('user_id', $user->id)->exists()) {
            $space->users()->attach($user->id, ['role' => 'admin']);
        }

        $this->command->info('Space "Espacio de Demostración" created with ID: demo-space');
        $this->command->info('Domain: demo.localhost');
        $this->command->info('Owner: test@example.com / password');
    }
}