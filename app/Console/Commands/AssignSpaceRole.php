<?php

namespace App\Console\Commands;

use App\Enums\SpaceRole;
use App\Models\Space;
use App\Models\SpaceUser;
use App\Models\User;
use Illuminate\Console\Command;

class AssignSpaceRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'space:assign-role {email} {space} {role} {--additional=*} {--revoked=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna un rol a un usuario en un espacio de trabajo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $spaceId = $this->argument('space');
        $roleName = $this->argument('role');
        $additionalPermissions = $this->option('additional');
        $revokedPermissions = $this->option('revoked');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("Usuario no encontrado: {$email}");
            return 1;
        }

        $space = Space::find($spaceId);
        if (!$space) {
            $this->error("Espacio no encontrado: {$spaceId}");
            return 1;
        }

        try {
            $role = SpaceRole::from($roleName);
        } catch (\ValueError $e) {
            $this->error("Rol inválido: {$roleName}");
            $this->info("Roles disponibles: " . implode(', ', array_column(SpaceRole::cases(), 'value')));
            return 1;
        }

        $spaceUser = SpaceUser::updateOrCreate(
            ['tenant_id' => $space->id, 'user_id' => $user->id],
            [
                'role' => $role,
                'additional_permissions' => !empty($additionalPermissions) ? $additionalPermissions : null,
                'revoked_permissions' => !empty($revokedPermissions) ? $revokedPermissions : null,
            ]
        );

        $this->info("Rol {$role->value} asignado a {$user->email} en el espacio {$space->id}");
        
        if (!empty($additionalPermissions)) {
            $this->info("Permisos adicionales: " . implode(', ', $additionalPermissions));
        }
        
        if (!empty($revokedPermissions)) {
            $this->info("Permisos revocados: " . implode(', ', $revokedPermissions));
        }

        return 0;
    }
}