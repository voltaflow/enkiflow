<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\TenantMigrationJob;
use App\Models\Space;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestQueueCommand extends Command
{
    protected $signature = 'test:queue {tenant?}';

    protected $description = 'Prueba el sistema de colas con un job de migración';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant') ?? 'demo-space';

        $this->info("Buscando tenant: {$tenantId}");

        $tenant = Space::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant '{$tenantId}' no encontrado");

            return 1;
        }

        $this->info("Tenant encontrado: {$tenant->name}");

        // Verificar tabla de jobs antes
        $beforeCount = DB::table('jobs')->count();
        $this->info("Jobs en cola antes: {$beforeCount}");

        try {
            // Despachar el job
            TenantMigrationJob::dispatch($tenant)->onQueue('tenant-migrations');
            $this->info('Job despachado exitosamente');

            // Verificar tabla de jobs después
            $afterCount = DB::table('jobs')->count();
            $this->info("Jobs en cola después: {$afterCount}");

            // Mostrar detalles del último job
            $lastJob = DB::table('jobs')
                ->orderBy('id', 'desc')
                ->first();

            if ($lastJob) {
                $this->info("Último job - ID: {$lastJob->id}, Cola: {$lastJob->queue}");
                $this->info('Payload: '.substr($lastJob->payload, 0, 200).'...');
            }

        } catch (\Exception $e) {
            $this->error('Error al despachar job: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());

            return 1;
        }

        return 0;
    }
}
