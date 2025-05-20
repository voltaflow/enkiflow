<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Symfony\Component\HttpFoundation\Response;

class CustomInitializeTenancyByDomainOrSubdomain extends InitializeTenancyByDomainOrSubdomain
{
    /**
     * Handle an incoming request.
     *
     * @param  $request  The request object
     * @param  \Closure  $next  The next middleware
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Skip tenancy initialization for main domains
        if ($request->attributes->get('skip_tenancy', false)) {
            Log::info("Skipping tenancy initialization for main domain: " . $request->getHost());
            return $next($request);
        }

        // For all other domains, proceed with normal tenancy initialization
        try {
            return parent::handle($request, $next);
        } catch (\Stancl\Tenancy\Exceptions\NotASubdomainException $e) {
            // Handle the case where it's not a subdomain but also not a main domain
            Log::warning("Not a subdomain, but not a main domain either: " . $request->getHost());
            
            // Fall back to domain-based initialization
            return (new \Stancl\Tenancy\Middleware\InitializeTenancyByDomain())->handle($request, $next);
        } catch (\Exception $e) {
            // Log the exception but don't interrupt the request flow
            Log::error("Error in tenancy initialization: " . $e->getMessage());
            return $next($request);
        }
    }
}