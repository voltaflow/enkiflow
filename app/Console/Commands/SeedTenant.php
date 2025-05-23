<?php

namespace App\Console\Commands;

use App\Models\Space;
use Database\Seeders\TenantSeeder;
use Illuminate\Console\Command;

class SeedTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:tenant {tenant_id : The ID of the tenant to seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed a specific tenant with example data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');

        // Find the tenant
        $tenant = Space::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");

            return 1;
        }

        $this->info("Seeding tenant: {$tenant->name} ({$tenant->id})");

        // Execute within tenant context
        tenancy()->initialize($tenant);

        // Run the tenant seeder
        $this->call('db:seed', [
            '--class' => TenantSeeder::class,
        ]);

        // End tenancy
        tenancy()->end();

        $this->info("Tenant {$tenant->name} has been seeded successfully!");

        return 0;
    }
}
