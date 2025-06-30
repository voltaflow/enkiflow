<?php

use App\Http\Middleware\CheckMainDomains;
use App\Http\Middleware\CustomAuthenticate;
use App\Http\Middleware\EnsureUserHasTenantAccess;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PublicLandingPageAccess;
use App\Http\Middleware\RedirectToSpaceSubdomain;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Asegurar que el dominio de sesión esté configurado antes que todo
        $middleware->prepend(\App\Http\Middleware\EnsureSessionDomain::class);
        
        // Prioritize our public landing page access middleware above everything else
        // This ensures main domains always show the landing page without auth redirects
        $middleware->prepend(PublicLandingPageAccess::class);

        // Registra nuestro middleware de verificación de dominios principales
        // como el segundo middleware de la aplicación
        $middleware->prepend(CheckMainDomains::class);

        // Disable automatic guest redirection to /login
        // to allow public routes to be accessible
        $middleware->redirectGuestsTo(function ($request) {
            // Explicitly check if this is a main domain - using multiple checks for reliability
            $mainDomains = get_main_domains();
            $isMainDomain = in_array($request->getHost(), $mainDomains) ||
                          $request->attributes->get('is_main_domain', false) ||
                          $request->attributes->get('bypass_tenancy', false) ||
                          $request->attributes->get('skip_tenancy', false);

            \Log::info("Guest redirection check - Host: {$request->getHost()}, Is main: ".($isMainDomain ? 'yes' : 'no').", Path: {$request->getPathInfo()}");

            // For main domains, NEVER redirect to login
            if ($isMainDomain) {
                // Do not redirect, allow access to the landing page
                return null;
            }

            // For all other domains, maintain default behavior
            return route('login');
        });

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            \App\Http\Middleware\TenantDiagnostics::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            HandleAppearance::class,
            SetLocale::class,
            RedirectToSpaceSubdomain::class,
            \App\Http\Middleware\LogRequests::class,
        ]);

        $middleware->alias([
            'tenant.access' => EnsureUserHasTenantAccess::class,
            'auth' => CustomAuthenticate::class, // Reemplazar el middleware de autenticación por defecto
            'teleport' => \App\Http\Middleware\TeleportToSpace::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
