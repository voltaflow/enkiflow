<?php

use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing Page Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the landing pages that are shown on the main
| domains. These routes will not be subject to tenancy initialization.
|
*/

// Base landing page routes (no locale prefix)
// Added our custom middlewares to ensure proper handling for main domains
// Important: Make this the highest priority - define named routes for easier debugging
Route::middleware(['web', 'ensure-landing', 'bypass-tenancy'])->group(function () {
    // CRITICAL: Home route
    Route::get('/', [LandingController::class, 'index'])->name('landing.home');

    // Other landing routes
    Route::get('/features', [LandingController::class, 'features'])->name('landing.features');
    Route::get('/pricing', [LandingController::class, 'pricing'])->name('landing.pricing');
    Route::get('/about', [LandingController::class, 'about'])->name('landing.about');
    Route::get('/contact', [LandingController::class, 'contact'])->name('landing.contact');
    Route::get('/demos/time-tracking', [LandingController::class, 'timeTrackingDemo'])->name('landing.demos.time-tracking');
});

// Locale switcher route - updated to use the dedicated LocaleController
Route::middleware(['web', 'bypass-tenancy'])->get('/set-locale/{locale}', [\App\Http\Controllers\LocaleController::class, 'setLocale'])->name('set-locale');

// Localized routes (with locale prefix)
// These routes mirror the main routes but with a locale prefix
$locales = ['en', 'es'];

Route::middleware(['web', 'ensure-landing', 'bypass-tenancy'])->group(function () use ($locales) {
    foreach ($locales as $locale) {
        Route::prefix($locale)->group(function () use ($locale) {
            Route::get('/', [LandingController::class, 'index'])->name('landing.home.'.$locale);
            Route::get('/features', [LandingController::class, 'features'])->name('landing.features.'.$locale);
            Route::get('/pricing', [LandingController::class, 'pricing'])->name('landing.pricing.'.$locale);
            Route::get('/about', [LandingController::class, 'about'])->name('landing.about.'.$locale);
            Route::get('/contact', [LandingController::class, 'contact'])->name('landing.contact.'.$locale);
            Route::get('/demos/time-tracking', [LandingController::class, 'timeTrackingDemo'])->name('landing.demos.time-tracking.'.$locale);
        });
    }
});
