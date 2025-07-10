<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\SpaceUserPolicy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
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
        // Register cache bindings before anything else
        $this->app->singleton('cache', function ($app) {
            return new \Illuminate\Cache\CacheManager($app);
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        // Ensure the contract is bound
        $this->app->singleton(\Illuminate\Contracts\Cache\Factory::class, function ($app) {
            return $app['cache'];
        });
        
        $this->app->singleton(\Illuminate\Contracts\Cache\Repository::class, function ($app) {
            return $app['cache.store'];
        });
        
        // Register ProjectPermissionResolver as singleton
        $this->app->singleton(\App\Services\ProjectPermissionResolver::class);
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

        // Registrar políticas
        Gate::policy(User::class, SpaceUserPolicy::class);

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
