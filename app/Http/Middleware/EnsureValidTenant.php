<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTenant
{
    protected DomainTenantResolver $resolver;

    public function __construct(DomainTenantResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for central domains and main domains
        $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];
        if (in_array($request->getHost(), config('tenancy.central_domains')) ||
            in_array($request->getHost(), $mainDomains)) {
            \Log::info("EnsureValidTenant: Skipping tenant validation for main domain: " . $request->getHost());
            return $next($request);
        }
        
        // Check if tenancy is initialized (should be done by CustomDomainTenancyInitializer)
        if (!function_exists('tenant') || !tenant()) {
            \Log::error("EnsureValidTenant: Tenant not initialized for: " . $request->getHost());
            return response()->view('errors.tenant-not-found', [
                'domain' => $request->getHost(),
                'error' => 'Tenant not initialized'
            ], 404);
        }

        $tenant = tenant();
        \Log::info("EnsureValidTenant: Tenant active: {$tenant->id} for domain {$request->getHost()}");
        
        // For development/testing, skip subscription checks
        if (app()->environment('local')) {
            \Log::info("EnsureValidTenant: Skipping subscription checks in local environment");
            return $next($request);
        }
        
        // Check if the tenant has an active subscription or is within trial period
        try {
            $owner = $tenant->owner;
            
            if (!$owner) {
                \Log::warning("EnsureValidTenant: No owner found for tenant {$tenant->id}");
                // In local environment, allow access without owner
                if (app()->environment('local')) {
                    return $next($request);
                }
                
                return response()->view('errors.subscription-required', [
                    'tenant' => $tenant,
                    'owner' => null,
                ], 402);
            }
            
            // Note: Subscription check is commented out for development
            // if (!$owner->subscribed('default') && !$owner->onTrial('default')) {
            //     return response()->view('errors.subscription-required', [
            //         'tenant' => $tenant,
            //         'owner' => $owner,
            //     ], 402);
            // }
            
        } catch (\Exception $e) {
            \Log::error("EnsureValidTenant: Error checking subscription: " . $e->getMessage());
            // In local environment, continue anyway
            if (app()->environment('local')) {
                return $next($request);
            }
            throw $e;
        }

        return $next($request);
    }
}
