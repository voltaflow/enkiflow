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
            return $next($request);
        }

        try {
            // Try a direct lookup first
            $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $request->getHost())->first();
            
            if (!$domain) {
                \Log::error("Dominio {$request->getHost()} no encontrado en la base de datos. Dominios disponibles: " 
                    . implode(', ', \Stancl\Tenancy\Database\Models\Domain::all()->pluck('domain')->toArray()));
                
                // Try to create it on the fly for enkiflow.test
                if ($request->getHost() === 'enkiflow.test') {
                    $firstTenant = \App\Models\Space::first();
                    if ($firstTenant) {
                        $domain = \Stancl\Tenancy\Database\Models\Domain::create([
                            'domain' => 'enkiflow.test',
                            'tenant_id' => $firstTenant->id
                        ]);
                        \Log::info("Creado dominio enkiflow.test para tenant {$firstTenant->id}");
                    }
                }
            }
            
            // Will throw an exception if tenant not found
            $tenant = $this->resolver->resolve($request);
            
            if (!$tenant) {
                \Log::error("Tenant no encontrado para dominio: " . $request->getHost() . " incluso después de resolver");
                return response()->view('errors.tenant-not-found', [
                    'domain' => $request->getHost()
                ], 404);
            }
            
            \Log::info("Tenant encontrado: {$tenant->id} para dominio {$request->getHost()}");
            
            // Check if the tenant has an active subscription or is within trial period
            $owner = $tenant->owner;
            
            if (!$owner || (!$owner->subscribed('default') && !$owner->onTrial('default'))) {
                return response()->view('errors.subscription-required', [
                    'tenant' => $tenant,
                    'owner' => $owner,
                ], 402);
            }
        } catch (\Exception $e) {
            \Log::error("Error en EnsureValidTenant: " . $e->getMessage() . " - Dominio: " . $request->getHost() . " - Stack: " . $e->getTraceAsString());
            return response()->view('errors.tenant-not-found', [
                'domain' => $request->getHost(),
                'error' => $e->getMessage()
            ], 404);
        }

        return $next($request);
    }
}
