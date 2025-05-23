<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMainDomains
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];

        // Set attribute to identify main domains
        $isMainDomain = in_array($host, $mainDomains);
        $request->attributes->set('is_main_domain', $isMainDomain);

        // For main domains, set a flag to skip tenancy initialization in middleware
        if ($isMainDomain) {
            \Log::info("Main domain detected: {$host} - Skipping tenancy initialization");
            // Use both attribute names for compatibility
            $request->attributes->set('bypass_tenancy', true);
            $request->attributes->set('skip_tenancy', true);
        }

        return $next($request);
    }
}
