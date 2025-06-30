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
                    // Reemplazar el job original con nuestra versión extendida
                    \App\Jobs\ExtendedMigrateDatabase::class,
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
            Events\TenantUpdated::class => [
                JobPipeline::make([
                    // Job para sincronización de datos
                    \App\Jobs\SyncTenantData::class,
                ])->send(function (Events\TenantUpdated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // Cambiar a true en producción para procesar en background
                
                // Listeners adicionales
                \App\Listeners\LogTenantUpdated::class,
                \App\Listeners\UpdateTenantCache::class,
                \App\Listeners\NotifyTenantChanges::class,
            ],
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
        
        // Registrar el middleware de sesión compartida
        $this->app->bind('ShareSessionAcrossDomains', function ($app) {
            return new \App\Http\Middleware\ShareSessionAcrossDomains();
        });
    }

    public function boot()
    {
        $this->bootEvents();
        $this->mapRoutes();

        $this->makeTenancyMiddlewareHighestPriority();

        // Register our custom middlewares
        $this->app['router']->aliasMiddleware('ensure-landing', \App\Http\Middleware\EnsureLandingForMainDomains::class);
        $this->app['router']->aliasMiddleware('bypass-tenancy', \App\Http\Middleware\BypassTenancyForMainDomains::class);

        // Register custom middleware to check tenant validity (subscription status, etc.)
        $this->app['router']->aliasMiddleware('valid-tenant', EnsureValidTenant::class);
        $this->app['router']->aliasMiddleware('tenant.access', \App\Http\Middleware\EnsureUserHasTenantAccess::class);

        // Ensure our Space model works correctly with domains
        \App\Models\Space::$domainModel = \Stancl\Tenancy\Database\Models\Domain::class;

        // Customize the behavior of PreventAccessFromCentralDomains
        \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::$abortRequest = function ($request, $next) {
            $mainDomains = get_main_domains();

            // Always allow access from main domains, and set bypass flag for extra safety
            if (in_array($request->getHost(), $mainDomains)) {
                $request->attributes->set('bypass_tenancy', true);

                return $next($request);
            }

            // For other central domains, check if they're defined in config
            if (in_array($request->getHost(), config('tenancy.central_domains', []))) {
                return $next($request);
            }

            // Only block access if this is not a main domain trying to access tenant routes
            if (! $request->attributes->get('bypass_tenancy', false)) {
                return abort(404, 'This page is not accessible from this domain.');
            }

            return $next($request);
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
            // Solo cargar rutas de tenant si NO estamos en un dominio principal
            $host = request()->getHost();
            $mainDomains = get_main_domains();
            $isMainDomain = in_array($host, $mainDomains);
            
            if (!$isMainDomain && file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    // ->middleware([EnsureValidTenant::class]) // Removed - will be applied selectively in routes
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [
            // Primero: Inicializador de tenancy personalizado
            \App\Http\Middleware\CustomDomainTenancyInitializer::class,

            // Segundo: Prevenir acceso desde dominios centrales
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,

            // Tercero: Bypass tenancy para dominios principales
            \App\Http\Middleware\BypassTenancyForMainDomains::class,

            // Cuarto: Forzar página de inicio para dominios principales
            \App\Http\Middleware\EnsureLandingForMainDomains::class,

            // Otros middleware de inicialización
            \Stancl\Tenancy\Middleware\InitializeTenancyByPath::class,
            \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class,

            // Middleware personalizado (después de que se inicializa tenancy)
            \App\Http\Middleware\EnsureValidTenant::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            if (class_exists($middleware)) {
                $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($middleware);
            }
        }
    }
}
