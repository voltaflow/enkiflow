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
        // Skip for central domains
        if (in_array($request->getHost(), config('tenancy.central_domains'))) {
            return $next($request);
        }

        try {
            // Will throw an exception if tenant not found
            $tenant = $this->resolver->resolve($request);
            
            // Check if the tenant has an active subscription or is within trial period
            $owner = $tenant->owner;
            
            if (!$owner || (!$owner->subscribed('default') && !$owner->onTrial('default'))) {
                return response()->view('errors.subscription-required', [
                    'tenant' => $tenant,
                    'owner' => $owner,
                ], 402);
            }
        } catch (\Exception $e) {
            return response()->view('errors.tenant-not-found', [
                'domain' => $request->getHost()
            ], 404);
        }

        return $next($request);
    }
}
