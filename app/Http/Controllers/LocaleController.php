<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Set the application locale.
     *
     * @param Request $request
     * @param string $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setLocale(Request $request, string $locale)
    {
        // Validate locale against supported languages
        $supportedLocales = ['en', 'es'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = config('app.fallback_locale', 'en');
        }
        
        // Set the application locale
        App::setLocale($locale);
        
        // Store in session
        Session::put('locale', $locale);
        
        // Log the locale change for debugging
        Log::info("Locale changed to {$locale} for user " . ($request->user() ? $request->user()->id : 'guest'));
        
        // Determine if we should redirect to a localized version of the current page
        $previousUrl = url()->previous();
        $redirectUrl = $this->getLocalizedRedirectUrl($previousUrl, $locale);
        
        // Redirect back with cookie
        return redirect($redirectUrl)
            ->withCookie(Cookie::forever('locale', $locale));
    }
    
    /**
     * Get a localized version of the URL based on the new locale
     *
     * @param string $url
     * @param string $newLocale
     * @return string
     */
    protected function getLocalizedRedirectUrl(string $url, string $newLocale): string
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '/';
        $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        
        // Remove current locale from path if present
        $pathSegments = explode('/', trim($path, '/'));
        $supportedLocales = ['en', 'es'];
        
        if (count($pathSegments) > 0 && in_array($pathSegments[0], $supportedLocales)) {
            // Remove the locale segment
            array_shift($pathSegments);
        }
        
        // Rebuild the path with the new locale
        $newPath = '/' . $newLocale;
        if (!empty($pathSegments)) {
            $newPath .= '/' . implode('/', $pathSegments);
        }
        
        // Rebuild the URL with the scheme and host from the original URL
        $scheme = $parsedUrl['scheme'] ?? 'http';
        $host = $parsedUrl['host'] ?? request()->getHost();
        
        // Check if there's a predefined route for this path
        $baseUrl = $scheme . '://' . $host;
        
        // Only include locale in path for non-home URLs
        if (count($pathSegments) === 0) {
            // For home page, we can use either /{locale} or just /
            // This depends on your routing strategy
            return $baseUrl . '/' . $newLocale;
        }
        
        return $baseUrl . $newPath . $query;
    }
}