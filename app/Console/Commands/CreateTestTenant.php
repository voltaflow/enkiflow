<?php

namespace App\Console\Commands;

use App\Models\Space;
use App\Models\User;
use App\Services\TenantCreator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create-test 
                            {--email=test@example.com : Email del usuario}
                            {--password=password : Contraseña del usuario}
                            {--name=Test User : Nombre del usuario}
                            {--company=Test Company : Nombre de la empresa}
                            {--subdomain= : Subdominio personalizado (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear un tenant de prueba con usuario';

    /**
     * Execute the console command.
     */
    public function handle(TenantCreator $tenantCreator)
    {
        $this->info('Creando tenant de prueba...');

        // Verificar si el usuario ya existe
        $email = $this->option('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Crear usuario
            $user = User::create([
                'name' => $this->option('name'),
                'email' => $email,
                'password' => Hash::make($this->option('password')),
                'email_verified_at' => now(),
            ]);
            $this->info("Usuario creado: {$user->email}");
        } else {
            $this->info("Usuario existente: {$user->email}");
        }

        // Verificar si el usuario ya tiene un tenant
        $existingSpace = Space::where('owner_id', $user->id)->first();
        if ($existingSpace) {
            $this->warn("El usuario ya tiene un space: {$existingSpace->name}");
            $this->info("Subdominio: {$existingSpace->slug}");
            $domain = config('app.domain', 'enkiflow.test');
            $this->info("URL: http://{$existingSpace->slug}.{$domain}");

            return Command::SUCCESS;
        }

        // Crear tenant
        try {
            $companyName = $this->option('company');
            $customSubdomain = $this->option('subdomain');

            $spaceData = [
                'name' => $companyName,
                'auto_tracking_enabled' => true,
                'seed_data' => true,
            ];

            // Si se proporciona un subdominio personalizado, usarlo
            if ($customSubdomain) {
                $spaceData['id'] = $customSubdomain;
                $spaceData['slug'] = $customSubdomain;
            }

            $space = $tenantCreator->create($user, $spaceData);

            $this->info('Tenant creado exitosamente!');
            $this->info("Nombre: {$space->name}");
            $this->info("Subdominio: {$space->slug}");

            $domain = config('app.domain', 'enkiflow.test');
            $tenantUrl = "http://{$space->slug}.{$domain}";

            $this->newLine();
            $this->info('=== Información de acceso ===');
            $this->info("URL: {$tenantUrl}");
            $this->info("Email: {$email}");
            $this->info('Password: '.$this->option('password'));
            $this->newLine();

            $this->info("✅ Puedes iniciar sesión en: {$tenantUrl}/login");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error al crear tenant: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
