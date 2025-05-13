<?php

use App\Http\Middleware\CheckMainDomains;
use App\Http\Middleware\EnsureUserHasTenantAccess;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
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
        // Registra nuestro middleware de verificación de dominios principales
        // como el primer middleware de la aplicación
        $middleware->prepend(CheckMainDomains::class);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            \App\Http\Middleware\TenantDiagnostics::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            HandleAppearance::class,
            SetLocale::class,
        ]);
        
        $middleware->alias([
            'tenant.access' => EnsureUserHasTenantAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
