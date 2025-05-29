<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\MigrationFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Events\DatabaseMigrated;
use Stancl\Tenancy\Events\MigratingDatabase;

class TenantMigrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;     // reintentos

    public int $backoff = 60;  // segundos entre intentos

    public function __construct(
        protected TenantWithDatabase $tenant,
        protected ?string $migration = null
    ) {
        // No establecer la cola aquí, lo haremos en el dispatch
    }

    public function handle(): void
    {
        tenancy()->initialize($this->tenant);
        $id = $this->tenant->getTenantKey();

        try {
            event(new MigratingDatabase($this->tenant));

            $this->migration
                ? $this->runSingle($this->migration)
                : $this->runAll();

            event(new DatabaseMigrated($this->tenant));
            Log::channel('tenant_migrations')->info("✅ Migración OK para {$id}");
        } catch (\Throwable $e) {
            event(new MigrationFailed($this->tenant, $this->migration ?? 'all', $e));
            Log::channel('tenant_migrations')->error("❌ Error migrando {$id}: {$e->getMessage()}");
            throw $e;
        } finally {
            tenancy()->end();
        }
    }

    private function runSingle(string $file): void
    {
        Artisan::call('migrate', [
            '--force' => true,
            '--path' => [database_path("migrations/tenant/{$file}.php")],
            '--realpath' => true,
        ]);
    }

    private function runAll(): void
    {
        Artisan::call('migrate', [
            '--force' => true,
            '--path' => [database_path('migrations/tenant')],
            '--realpath' => true,
        ]);
    }
}
