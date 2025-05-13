<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\LandingPageController;
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

Route::get('/', [LandingController::class, 'index'])->name('landing.home');
Route::get('/features', [LandingController::class, 'features'])->name('landing.features');
Route::get('/pricing', [LandingController::class, 'pricing'])->name('landing.pricing');
Route::get('/about', [LandingController::class, 'about'])->name('landing.about');
Route::get('/contact', [LandingController::class, 'contact'])->name('landing.contact');

// Landing pages with interactive demos
Route::get('/demos/time-tracking', [LandingController::class, 'timeTrackingDemo'])->name('landing.demos.time-tracking');