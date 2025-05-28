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

    /** @var \Exception */
    public $exception;

    /**
     * Crea una nueva instancia del evento.
     *
     * @param TenantWithDatabase $tenant
     * @param string $migration
     * @param \Exception $exception
     */
    public function __construct(TenantWithDatabase $tenant, string $migration, \Exception $exception)
    {
        $this->tenant = $tenant;
        $this->migration = $migration;
        $this->exception = $exception;
    }
}