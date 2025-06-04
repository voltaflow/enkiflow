<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si estamos en un tenant, forzar la generación de URLs con el dominio del tenant
        if (tenancy()->initialized) {
            $tenant = tenant();
            
            // Obtener el dominio actual del request
            $currentDomain = $request->getHost();
            
            // Forzar la URL root para que use el dominio actual
            URL::forceRootUrl($request->getSchemeAndHttpHost());
            
            // Asegurar que las rutas generadas usen el dominio del tenant
            URL::defaults(['tenant_domain' => $currentDomain]);
        }

        return $next($request);
    }
}