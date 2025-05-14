<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
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
        // Configuración de dominios principales (siempre los mismos)
        $bypassTenancy = $request->attributes->get('bypass_tenancy', false);
        $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];
        $isMainDomain = in_array($request->getHost(), $mainDomains);
        
        // Si estamos en un dominio principal, asegúrate de que bypass_tenancy está configurado
        if ($isMainDomain && !$bypassTenancy) {
            $request->attributes->set('bypass_tenancy', true);
        }
        
        // Orden de prioridad para determinar el idioma:
        // 1. Primer segmento de URL si es un código de idioma válido
        // 2. Cookie de idioma
        // 3. Idioma de sesión
        // 4. Idioma preferido del navegador si está soportado
        // 5. Idioma por defecto del sistema
        
        $supportedLocales = ['en', 'es'];
        $defaultLocale = config('app.fallback_locale', 'en');
        $locale = $defaultLocale;
        
        // 1. Verificar si el primer segmento de la URL es un idioma válido
        $urlLocale = $request->segment(1);
        
        if ($urlLocale && in_array($urlLocale, $supportedLocales)) {
            $locale = $urlLocale;
            Log::debug("SetLocale: Using URL locale: {$locale}");
        } 
        // 2. Verificar la cookie de idioma
        else if ($request->cookie('locale') && in_array($request->cookie('locale'), $supportedLocales)) {
            $locale = $request->cookie('locale');
            Log::debug("SetLocale: Using cookie locale: {$locale}");
        } 
        // 3. Verificar el idioma en la sesión
        else if (Session::has('locale') && in_array(Session::get('locale'), $supportedLocales)) {
            $locale = Session::get('locale');
            Log::debug("SetLocale: Using session locale: {$locale}");
        } 
        // 4. Verificar el idioma preferido del navegador
        else if ($request->getPreferredLanguage($supportedLocales)) {
            $locale = $request->getPreferredLanguage($supportedLocales);
            Log::debug("SetLocale: Using browser preferred locale: {$locale}");
        } 
        // 5. Usar el idioma por defecto
        else {
            Log::debug("SetLocale: Using default locale: {$locale}");
        }

        // Establecer el idioma en la aplicación y la sesión
        App::setLocale($locale);
        Session::put('locale', $locale);
        
        // Si no hay cookie de idioma, establecerla
        if (!$request->cookie('locale')) {
            Cookie::queue('locale', $locale, 525600); // 1 año en minutos
        }
        
        return $next($request);
    }
}