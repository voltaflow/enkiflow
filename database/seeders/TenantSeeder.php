<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si estamos en un contexto de tenant
        if (!tenant()) {
            $this->command->error('Este seeder debe ejecutarse en un contexto de tenant.');
            return;
        }

        // Obtener o crear usuario principal
        $user = User::firstOr(function () {
            return User::factory()->create([
                'name' => 'Usuario Principal',
                'email' => 'admin@' . tenant('id') . '.com',
            ]);
        });

        // Ejecutar seeders específicos
        $this->call([
            TaskStateSeeder::class,
        ]);

        $this->command->info('Datos iniciales creados para el tenant: ' . tenant('id'));
    }
}