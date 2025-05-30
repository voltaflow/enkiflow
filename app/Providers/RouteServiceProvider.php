<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // Primero verificamos si estamos en un subdominio de tenant
            $host = request()->getHost();
            $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];
            $isMainDomain = in_array($host, $mainDomains);

            // Si NO estamos en un dominio principal, cargamos primero las rutas de tenant
            if (! $isMainDomain) {
                // Cargamos las rutas de tenant primero para subdominios
                if (file_exists(base_path('routes/tenant.php'))) {
                    Route::middleware('web')
                        ->group(base_path('routes/tenant.php'));
                }
            }

            // Luego cargamos las rutas web normales
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // API routes are loaded separately
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
