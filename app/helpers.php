<?php

/**
 * Archivo de helpers personalizados para la aplicación EnkiFlow
 * 
 * Aquí se pueden agregar funciones helper globales que serán
 * accesibles en toda la aplicación.
 */

if (!function_exists('get_base_domain')) {
    /**
     * Get the base domain for the application
     * 
     * @return string
     */
    function get_base_domain(): string
    {
        // Extract from APP_URL
        $appUrl = config('app.url');
        $baseDomain = parse_url($appUrl, PHP_URL_HOST);
        
        // Remove 'www.' if present to get the base domain
        $baseDomain = preg_replace('/^www\./', '', $baseDomain);
        
        return $baseDomain ?: 'enkiflow.test';
    }
}

if (!function_exists('get_main_domains')) {
    /**
     * Get all main domains for the application
     * 
     * @return array
     */
    function get_main_domains(): array
    {
        $baseDomain = get_base_domain();
        
        // For local development
        if ($baseDomain === 'enkiflow.test') {
            return ['enkiflow.test'];
        }
        
        // For production
        return [
            $baseDomain,
            'www.' . $baseDomain
        ];
    }
}