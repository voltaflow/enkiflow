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
        // Asegurar que el dominio de la sesión esté configurado correctamente
        // antes de que Laravel inicie la sesión
        config([
            'session.domain' => '.enkiflow.test',
            'session.cookie' => 'enkiflow_session',
            'session.secure' => false, // Para desarrollo local
            'session.same_site' => 'lax',
        ]);
        
        return $next($request);
    }
}