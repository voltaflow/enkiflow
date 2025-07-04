<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionDomain
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener el dominio base dinámicamente
        $baseDomain = '.' . get_base_domain();
        $isSecure = $request->secure();
        
        // Asegurar que el dominio de la sesión esté configurado correctamente
        // antes de que Laravel inicie la sesión
        config([
            'session.domain' => $baseDomain,
            'session.cookie' => 'enkiflow_session',
            'session.secure' => $isSecure,
            'session.same_site' => 'lax',
        ]);
        
        return $next($request);
    }
}