<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Jobs\MigrateDatabase as BaseMigrateDatabase;

class ExtendedMigrateDatabase extends BaseMigrateDatabase
{
    /**
     * Ejecuta el job.
     */
    public function handle(): void
    {
        Artisan::call('tenants:migrate-extended', [
            '--tenants' => [$this->tenant->getTenantKey()],
        ]);
    }
}
