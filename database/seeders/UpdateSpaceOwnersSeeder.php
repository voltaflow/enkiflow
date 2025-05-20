<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateSpaceOwnersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los espacios (tenants) que no tienen un propietario asignado
        $spaces = \App\Models\Space::whereNull('owner_id')->get();
        
        if ($spaces->isEmpty()) {
            $this->command->info('No hay espacios sin propietario.');
            return;
        }
        
        // Obtener al primer usuario para asignarlo como propietario por defecto
        $defaultOwner = \App\Models\User::first();
        
        if (!$defaultOwner) {
            $this->command->error('No hay usuarios en el sistema para asignar como propietarios.');
            return;
        }
        
        $updatedCount = 0;
        
        foreach ($spaces as $space) {
            // Buscar un usuario que sea administrador del espacio
            $admin = $space->users()
                ->wherePivot('role', 'admin')
                ->first();
            
            // Si hay un administrador, asignarlo como propietario, de lo contrario usar el propietario por defecto
            $ownerId = $admin ? $admin->id : $defaultOwner->id;
            
            $space->owner_id = $ownerId;
            $space->save();
            
            $updatedCount++;
        }
        
        $this->command->info("Se asignaron propietarios a {$updatedCount} espacios.");
    }
}
