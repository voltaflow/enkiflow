<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class CustomDomainTenancyInitializer extends InitializeTenancyByDomain
{
    /**
     * Find domain by hostname
     */
    protected function findDomain(string $hostname)
    {
        return \Stancl\Tenancy\Database\Models\Domain::where('domain', $hostname)->first();
    }
    
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
            // Get the domain
            $domain = $this->findDomain($request->getHost());
            
            if ($domain) {
                // Load the tenant model properly
                $tenant = \App\Models\Space::find($domain->tenant_id);
                
                if ($tenant) {
                    // Initialize tenancy with the loaded tenant
                    tenancy()->initialize($tenant);
                    
                    // Ensure the tenant is properly bound to the container
                    app()->instance(\Stancl\Tenancy\Contracts\Tenant::class, $tenant);
                    
                    // Almacenar el espacio actual en la sesión para el selector de espacios
                    if (auth()->check()) {
                        session(['current_space_id' => $tenant->id]);
                    }
                    
                } else {
                    throw new \Exception('Tenant not found for domain: ' . $domain->domain);
                }
            } else {
                throw new \Exception('Domain not found: ' . $request->getHost());
            }
            
            return $next($request);
        } catch (\Exception $e) {

            // For domains that should be handled as tenants but failed to initialize,
            // we could redirect to a central domain or show an error page
            if (!in_array($request->getHost(), config('tenancy.central_domains', []))) {
                return response()->view('errors.tenant-not-found', [
                    'domain' => $request->getHost(),
                ], 404);
            }

            return $next($request);
        }
    }
}
