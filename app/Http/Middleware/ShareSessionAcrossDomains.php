<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareSessionAcrossDomains
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener el dominio base dinámicamente
        $baseDomain = '.' . get_base_domain();
        
        // Configurar el dominio de la sesión antes de procesarla
        config(['session.domain' => $baseDomain]);
        
        return $next($request);
    }
}