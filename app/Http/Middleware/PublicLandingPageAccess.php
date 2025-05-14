<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicLandingPageAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];
        $isMainDomain = in_array($request->getHost(), $mainDomains);
        
        // Log the request information for debugging
        Log::info("PublicLandingPageAccess middleware: Processing " . $request->getHost() . $request->getPathInfo());
        Log::info("Is main domain: " . ($isMainDomain ? 'true' : 'false'));
        
        // Mark this as a main domain request - very important for other middleware
        if ($isMainDomain) {
            $request->attributes->set('is_main_domain', true);
            $request->attributes->set('bypass_tenancy', true);
            
            // For root path on main domains, force to landing controller
            if ($request->getPathInfo() === '/') {
                Log::info("PUBLIC ACCESS: Redirecting to landing page controller directly");
                return app(\App\Http\Controllers\LandingController::class)->index();
            }
        }
        
        return $next($request);
    }
}
