<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class CustomDomainTenancyInitializer extends InitializeTenancyByDomain
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        // If this is a main domain, bypass tenancy initialization
        if ($request->attributes->get('bypass_tenancy', false)) {
            return $next($request);
        }

        try {
            // Standard tenancy initialization for non-main domains
            return parent::handle($request, $next);
        } catch (\Exception $e) {

            // For domains that should be handled as tenants but failed to initialize,
            // we could redirect to a central domain or show an error page
            if ($request->getHost() !== 'enkiflow.com' &&
                $request->getHost() !== 'www.enkiflow.com' &&
                $request->getHost() !== 'enkiflow.test') {
                return response()->view('errors.tenant-not-found', [
                    'domain' => $request->getHost(),
                ], 404);
            }

            return $next($request);
        }
    }
}
