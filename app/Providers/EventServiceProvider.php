<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Eventos de migración de tenant
        \Stancl\Tenancy\Events\MigratingDatabase::class => [
            \App\Listeners\RegisterMigrationStart::class,
        ],
        \Stancl\Tenancy\Events\DatabaseMigrated::class => [
            \App\Listeners\RegisterMigrationSuccess::class,
        ],
        \App\Events\MigrationFailed::class => [
            \App\Listeners\RegisterMigrationFail::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
