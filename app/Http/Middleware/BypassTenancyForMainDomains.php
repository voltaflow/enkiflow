<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BypassTenancyForMainDomains
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];

        if (in_array($request->getHost(), $mainDomains)) {
            // This is a main domain, bypass tenancy initialization
            $request->attributes->set('bypass_tenancy', true);
            $request->attributes->set('is_main_domain', true);
            \Log::info('Bypassing tenancy for main domain: '.$request->getHost());

            // Debug current path
            \Log::info('Current path: '.$request->getPathInfo());
        }

        return $next($request);
    }
}
