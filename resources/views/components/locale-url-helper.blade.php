@php
/**
 * Helper component to get localized URLs
 * Usage: @include('components.locale-url-helper', ['route' => 'landing.features', 'locale' => 'es'])
 * 
 * Params:
 * - route: the route name
 * - locale: the locale to use (default: current locale)
 * - params: array of route parameters (default: [])
 */

$locale = $locale ?? app()->getLocale();
$params = $params ?? [];

// If the route name includes a locale suffix like '.en', remove it
$baseRoute = $route;
foreach (['en', 'es'] as $localeCode) {
    $suffix = '.' . $localeCode;
    if (substr($route, -strlen($suffix)) === $suffix) {
        $baseRoute = substr($route, 0, -strlen($suffix));
        break;
    }
}

// Check if there's a localized version of the route
$localizedRoute = $baseRoute . '.' . $locale;
$routeExists = Illuminate\Support\Facades\Route::has($localizedRoute);

// Generate the URL using the appropriate route
if ($routeExists) {
    $url = route($localizedRoute, $params);
} else {
    $url = route($baseRoute, $params);
}

echo $url;
@endphp