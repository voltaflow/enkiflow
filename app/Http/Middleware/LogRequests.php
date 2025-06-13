<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only log problematic responses
        if ($response->getStatusCode() >= 400) {
            Log::warning('Request resulted in error', [
                'status' => $response->getStatusCode(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'host' => $request->getHost(),
                'path' => $request->path(),
                'is_ajax' => $request->ajax(),
                'is_inertia' => $request->header('X-Inertia') ? 'yes' : 'no',
                'user_id' => $request->user() ? $request->user()->id : null,
                'auth_check' => auth()->check(),
                'session_id' => $request->session()->getId(),
                'route_name' => $request->route() ? $request->route()->getName() : null,
            ]);
        }
        
        return $response;
    }
}