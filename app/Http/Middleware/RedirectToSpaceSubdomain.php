<?php

namespace App\Http\Middleware;

use App\Models\Space;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToSpaceSubdomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if we're on a route like /spaces/{id} that should redirect to subdomain
        if ($request->is('spaces/*') && !$request->is('spaces/create', 'spaces/*/edit')) {
            $spaceId = $request->route('space');
            
            // Skip if this is an admin/management route
            if ($request->is('spaces/*/show', 'spaces/*/users', 'spaces/*/settings')) {
                return $next($request);
            }
            
            // Try to find the space
            $space = Space::find($spaceId);
            
            if ($space && $space->domains->isNotEmpty()) {
                // Get the first domain
                $domain = $space->domains->first()->domain;
                
                // Build the redirect URL with the same protocol
                $protocol = $request->secure() ? 'https' : 'http';
                $redirectUrl = "{$protocol}://{$domain}";
                
                // Add any query parameters
                if ($request->getQueryString()) {
                    $redirectUrl .= '?' . $request->getQueryString();
                }
                
                return redirect($redirectUrl);
            }
        }
        
        return $next($request);
    }
}