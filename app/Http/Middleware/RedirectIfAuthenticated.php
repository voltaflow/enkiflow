<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Si hay un parámetro de invitación, redirigir a la página de invitación
                if ($request->has('invitation')) {
                    return redirect()->route('invitation.show', $request->invitation);
                }
                
                // Redirigir al dashboard o a la página de espacios
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}