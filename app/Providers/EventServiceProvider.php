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

        // Eventos de invitaciones
        \App\Events\InvitationSent::class => [
            \App\Listeners\SendInvitationEmail::class,
            \App\Listeners\LogInvitationActivity::class,
        ],
        \App\Events\InvitationViewed::class => [
            \App\Listeners\LogInvitationActivity::class,
        ],
        \App\Events\InvitationAccepted::class => [
            \App\Listeners\LogInvitationActivity::class,
            \App\Listeners\SendInvitationAcceptedNotification::class,
            \App\Listeners\BroadcastInvitationAccepted::class,
        ],
        \App\Events\InvitationRevoked::class => [
            \App\Listeners\LogInvitationActivity::class,
        ],
        \App\Events\InvitationExpired::class => [
            \App\Listeners\LogInvitationActivity::class,
            \App\Listeners\SendReminderNotification::class,
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
