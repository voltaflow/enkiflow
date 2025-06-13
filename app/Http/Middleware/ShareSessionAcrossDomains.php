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
        // Configurar el dominio de la sesión antes de procesarla
        config(['session.domain' => '.enkiflow.test']);
        
        $response = $next($request);
        
        // Log para depuración
        \Log::debug('ShareSessionAcrossDomains', [
            'host' => $request->getHost(),
            'session_domain' => config('session.domain'),
            'session_cookie' => config('session.cookie'),
            'auth_check' => auth()->check(),
            'user_id' => auth()->id(),
        ]);
        
        return $response;
    }
}