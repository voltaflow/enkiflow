<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomAuthenticate extends Middleware
{
    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate($request, array $guards)
    {
        // Check if this is a main domain
        $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];
        $isMainDomain = in_array($request->getHost(), $mainDomains);
        
        // For main domains, just log and allow access without authentication
        if ($isMainDomain) {
            Log::info("CustomAuthenticate: Bypassing authentication for main domain: " . $request->getHost());
            return;
        }
        
        // Otherwise proceed with standard authentication
        parent::authenticate($request, $guards);
    }
    
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Check if this is a main domain
        $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];
        $isMainDomain = in_array($request->getHost(), $mainDomains);
        
        // For main domains, don't redirect
        if ($isMainDomain) {
            Log::info("CustomAuthenticate: Not redirecting for main domain: " . $request->getHost());
            return null;
        }
        
        Log::info("CustomAuthenticate: Redirecting to login for subdomain: " . $request->getHost());
        return route('login');
    }
}
