<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class MigrationFailed
{
    use SerializesModels;

    /** @var TenantWithDatabase */
    public $tenant;

    /** @var string */
    public $migration;

    /** @var \Throwable */
    public $exception;

    /**
     * Crea una nueva instancia del evento.
     */
    public function __construct(TenantWithDatabase $tenant, string $migration, \Throwable $exception)
    {
        $this->tenant = $tenant;
        $this->migration = $migration;
        $this->exception = $exception;
    }
}
