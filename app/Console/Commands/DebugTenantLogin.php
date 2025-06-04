<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Database\Models\Domain;

class DebugTenantLogin extends Command
{
    protected $signature = 'tenant:debug-login {email} {password} {domain}';
    protected $description = 'Debug tenant login process';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $domainName = $this->argument('domain');

        $this->info("=== DEBUGGING TENANT LOGIN ===");
        $this->info("Email: $email");
        $this->info("Domain: $domainName");

        // 1. Verificar usuario
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("✗ User not found");
            return 1;
        }
        $this->info("✓ User found: {$user->name} (ID: {$user->id})");

        // 2. Verificar contraseña
        if (!Hash::check($password, $user->password)) {
            $this->error("✗ Invalid password");
            return 1;
        }
        $this->info("✓ Password is correct");

        // 3. Verificar dominio
        $domain = Domain::where('domain', $domainName)->first();
        if (!$domain) {
            $this->error("✗ Domain not found");
            return 1;
        }
        $this->info("✓ Domain found (Tenant: {$domain->tenant_id})");

        // 4. Inicializar tenant
        tenancy()->initialize($domain->tenant);
        $this->info("✓ Tenant initialized");

        // 5. Verificar acceso del usuario al tenant
        $tenant = tenant();
        $hasAccess = $user->spaces()->where('tenants.id', $tenant->id)->exists();
        
        if (!$hasAccess) {
            $this->error("✗ User does not have access to this tenant");
            tenancy()->end();
            return 1;
        }
        $this->info("✓ User has access to tenant");

        // 6. Verificar rutas
        $this->info("\n=== ROUTES ===");
        $dashboardRoute = route('tenant.dashboard');
        $this->info("Dashboard route: $dashboardRoute");
        
        // 7. Verificar URL generation
        $this->info("\n=== URL GENERATION ===");
        $this->info("Current host: " . request()->getHost());
        $this->info("Scheme and host: " . request()->getSchemeAndHttpHost());
        
        tenancy()->end();
        
        $this->info("\n✓ All checks passed. Login should work!");
        return 0;
    }
}