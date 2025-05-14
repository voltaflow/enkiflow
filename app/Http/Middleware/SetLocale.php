<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Safety check to ensure the middleware runs regardless of tenancy context
        $bypassTenancy = $request->attributes->get('bypass_tenancy', false);
        $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];
        $isMainDomain = in_array($request->getHost(), $mainDomains);
        
        // If we're on a main domain, make sure bypass_tenancy is set
        if ($isMainDomain && !$bypassTenancy) {
            $request->attributes->set('bypass_tenancy', true);
            \Log::info("SetLocale: Enforcing bypass_tenancy for main domain: " . $request->getHost());
        }
        
        // Check URL first segment for locale
        $urlLocale = $request->segment(1);
        
        // Check if the URL has a valid locale code
        if ($urlLocale && in_array($urlLocale, ['en', 'es'])) {
            // Set app locale from URL segment
            App::setLocale($urlLocale);
            Session::put('locale', $urlLocale);
            \Log::info("SetLocale: Using URL locale: " . $urlLocale);
        } else {
            // Use session or default locale
            $locale = Session::get('locale', config('app.locale'));
            App::setLocale($locale);
            \Log::info("SetLocale: Using session/default locale: " . $locale);
        }
        
        return $next($request);
    }
}