<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\EnsureValidTenant;
use App\Models\Space;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    // By default, no namespace is used to support the callable array syntax.
    public static string $controllerNamespace = '';

    public function events()
    {
        return [
            // Tenant events
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    Jobs\MigrateDatabase::class,
                    // Jobs\SeedDatabase::class,

                    // Your own jobs to prepare the tenant.
                    // Provision API keys, create S3 buckets, anything you want!

                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],

            // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

            // Tenancy events
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

            // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],

            // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register()
    {
        // Register the Space model as the tenant model
        $this->app->bind(\Stancl\Tenancy\Contracts\Tenant::class, Space::class);
        
        // Configure the package tenant model to use our custom Space model
        // This is important for working with domains() and other Stancl Tenancy features
        config(['tenancy.tenant_model' => Space::class]);
    }

    public function boot()
    {
        $this->bootEvents();
        $this->mapRoutes();

        $this->makeTenancyMiddlewareHighestPriority();

        // Register custom middleware to check tenant validity (subscription status, etc.)
        $this->app['router']->aliasMiddleware('valid-tenant', EnsureValidTenant::class);
        $this->app['router']->aliasMiddleware('tenant.access', \App\Http\Middleware\EnsureUserHasTenantAccess::class);

        // Ensure our Space model works correctly with domains
        \App\Models\Space::$domainModel = \Stancl\Tenancy\Database\Models\Domain::class;

        // Personalizamos el comportamiento de PreventAccessFromCentralDomains
        \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::$abortRequest = function ($request, $next) {
            $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];
            if (in_array($request->getHost(), $mainDomains) && $request->getPathInfo() === '/') {
                return $next($request); // Permitir acceso a la ruta raíz en dominios principales
            }

            return abort(404); // Comportamiento predeterminado para otras rutas
        };

        // El DomainTenantResolver requiere una instancia de Illuminate\Contracts\Cache\Factory como primer argumento,
        // no un modelo de Space como intentamos hacer antes.
        // Este resolver está correctamente configurado por el paquete Stancl Tenancy.
    }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }

    protected function mapRoutes()
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->middleware([EnsureValidTenant::class]) // Add our custom middleware to tenant routes
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [
            // Even higher priority than the initialization middleware
            Middleware\PreventAccessFromCentralDomains::class,

            // Tenancy initialization middleware - modificado para solo usar el middleware de dominio
            // evitando los middleware de subdominio que pueden causar problemas
            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,

            // Our custom middleware (after tenancy is initialized)
            EnsureValidTenant::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            // Ensure the middleware exists in Laravel's container before trying to prepend it
            if (class_exists($middleware)) {
                $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($middleware);
            }
        }
    }
}
