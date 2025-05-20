<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandingForMainDomains
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];
        $isMainDomain = in_array($request->getHost(), $mainDomains);

        // Mark main domains for bypass_tenancy and debugging
        if ($isMainDomain) {
            Log::info('EnsureLandingForMainDomains: Processing request for main domain: '.$request->getHost().' path: '.$request->getPathInfo());

            // Always ensure bypass_tenancy is set for main domains
            if (! $request->attributes->get('bypass_tenancy', false)) {
                $request->attributes->set('bypass_tenancy', true);
            }

            // For root path, use the landing controller directly
            // This provides a fallback in case the domain route in web.php isn't matched
            if ($request->getPathInfo() === '/') {
                Log::info('EnsureLandingForMainDomains: Intercepting root route for main domain: '.$request->getHost());
                $controller = app(\App\Http\Controllers\LandingController::class);
                // Convert View to Response
                $view = $controller->index();

                return response($view);
            }
        }

        return $next($request);
    }
}
