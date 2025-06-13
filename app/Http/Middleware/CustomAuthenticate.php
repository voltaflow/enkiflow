<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class CustomAuthenticate extends Middleware
{
    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate($request, array $guards)
    {
        // Always proceed with standard authentication
        parent::authenticate($request, $guards);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson() || $request->header('X-Inertia')) {
            return null;
        }
        
        // Si estamos en un subdominio, redirigir al dominio principal para login
        $mainDomains = config('tenancy.central_domains', ['enkiflow.test']);
        if (!in_array($request->getHost(), $mainDomains)) {
            $mainDomain = $mainDomains[0] ?? 'enkiflow.test';
            return "https://{$mainDomain}/login";
        }
        
        return route('login');
    }
}
