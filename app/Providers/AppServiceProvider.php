<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forzar HTTPS en cualquier entorno
        URL::forceScheme('https');

        // Set locale from session if available
        if (Session::has('locale')) {
            $locale = Session::get('locale');
            App::setLocale($locale);
            \Log::info("AppServiceProvider: Setting application locale to {$locale}");
        }

        // Registrar comandos personalizados
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ExtendedTenantsMigrate::class,
                \App\Console\Commands\RetryFailedTenantMigrations::class,
                \App\Console\Commands\TenantMigrationStatus::class,
                \App\Console\Commands\TenantMigrateBack::class,
            ]);
        }
    }
}
