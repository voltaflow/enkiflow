<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Determine theme preference with fallback hierarchy:
        // 1. Session value
        // 2. Cookie value
        // 3. Default to 'system'
        $appearance = Session::get('appearance');
        
        if (!$appearance) {
            $appearance = $request->cookie('appearance');
        }
        
        if (!$appearance || !in_array($appearance, ['light', 'dark', 'system'])) {
            $appearance = 'system';
        }
        
        // Store in session if not already set
        if (Session::get('appearance') !== $appearance) {
            Session::put('appearance', $appearance);
        }
        
        // Share with all views
        View::share('appearance', $appearance);

        return $next($request);
    }
}
