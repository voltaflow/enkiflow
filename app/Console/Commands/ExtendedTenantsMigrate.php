<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\MigrationFailed;
use App\Jobs\TenantMigrationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Events\DatabaseMigrated;
use Stancl\Tenancy\Events\MigratingDatabase;

class ExtendedTenantsMigrate extends Command
{
    protected $signature = 'tenants:migrate-extended {--tenants=* : Run migrations for specific tenants} {--async : Run migrations asynchronously}';

    protected $description = 'Ejecuta migraciones para tenant(s) con seguimiento de estado extendido';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $tenantIds = $this->option('tenants');

        // Filtrar valores null y convertir a array si es necesario
        /** @var array<string>|null $filteredTenantIds */
        $filteredTenantIds = null;

        if (is_array($tenantIds)) {
            $filtered = array_filter($tenantIds, fn ($id) => $id !== null && $id !== '');
            if (! empty($filtered)) {
                /** @var array<string> $filteredTenantIds */
                $filteredTenantIds = array_values($filtered);
            }
        }

        tenancy()->runForMultiple($filteredTenantIds, function ($tenant) {
            $this->line("Tenant: {$tenant->getTenantKey()}");
            $tenantId = $tenant->getTenantKey();

            if ($this->option('async')) {
                // Registrar migraciones pendientes también en modo async
                $this->registerPendingMigrations($tenant);

                // Despachar el job
                TenantMigrationJob::dispatch($tenant)->onQueue('tenant-migrations');

                $this->info("Job despachado para {$tenantId}");

                return;
            }

            try {
                event(new MigratingDatabase($tenant));

                // Registrar migraciones pendientes antes de ejecutar
                $this->registerPendingMigrations($tenant);

                // Ejecutar migraciones usando el comando base
                $exitCode = Artisan::call('migrate', [
                    '--force' => true,
                    '--path' => [database_path('migrations/tenant')],
                    '--realpath' => true,
                ], $this->output);

                if ($exitCode === 0) {
                    // Disparar evento que actualizará los estados
                    event(new DatabaseMigrated($tenant));
                } else {
                    throw new \Exception("Error ejecutando migraciones para tenant {$tenantId}");
                }

            } catch (\Throwable $e) {
                // Disparar evento de fallo
                event(new MigrationFailed($tenant, 'unknown', $e));

                $this->error("Error migrando tenant {$tenantId}: {$e->getMessage()}");

                return 1;
            }
        });

        return 0;
    }

    /**
     * Registra las migraciones pendientes para un tenant.
     *
     * @param  mixed  $tenant
     */
    protected function registerPendingMigrations($tenant): void
    {
        // Obtener archivos de migración
        $migrationPath = database_path('migrations/tenant');
        $files = glob($migrationPath.'/*.php');

        // Obtener migraciones ya ejecutadas en el contexto del tenant
        $ran = [];
        try {
            $ran = DB::table('migrations')->pluck('migration')->toArray();
        } catch (\Exception $e) {
            // Si la tabla no existe, asumimos que no hay migraciones ejecutadas
            $ran = [];
        }

        $tenantId = $tenant->getTenantKey();

        // Obtener conexión landlord antes de cualquier operación
        $landlordDb = DB::connection(config('tenancy.database.central_connection', 'pgsql'));

        foreach ($files as $file) {
            $migration = pathinfo($file, PATHINFO_FILENAME);

            if (! in_array($migration, $ran)) {
                $landlordDb->table('tenant_migration_states')
                    ->updateOrInsert(
                        ['tenant_id' => $tenantId, 'migration' => $migration],
                        [
                            'status' => 'pending',
                            'started_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
            }
        }
    }
}
