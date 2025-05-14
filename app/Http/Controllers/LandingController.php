<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class LandingController extends Controller
{
    /**
     * Display the landing home page.
     */
    public function index(): View
    {
        // Log the request information
        \Log::info("LandingController@index called for domain: " . request()->getHost());
        \Log::info("URL: " . request()->fullUrl());
        \Log::info("Route name: " . request()->route()->getName());
        
        // Return the landing view - ensure this is being called
        return view('landing.pages.home', [
            'appearance' => session('appearance', 'system')
        ]);
    }

    /**
     * Display the features page.
     */
    public function features(): View
    {
        return view('landing.pages.features', [
            'appearance' => session('appearance', 'system')
        ]);
    }

    /**
     * Display the pricing page.
     */
    public function pricing(): View
    {
        return view('landing.pages.pricing', [
            'appearance' => session('appearance', 'system')
        ]);
    }

    /**
     * Display the about page.
     */
    public function about(): View
    {
        return view('landing.pages.about', [
            'appearance' => session('appearance', 'system')
        ]);
    }

    /**
     * Display the contact page.
     */
    public function contact(): View
    {
        return view('landing.pages.contact', [
            'appearance' => session('appearance', 'system')
        ]);
    }

    /**
     * Display the time tracking interactive demo.
     */
    public function timeTrackingDemo(): View
    {
        return view('landing.pages.demos.time-tracking', [
            'appearance' => session('appearance', 'system')
        ]);
    }
    
    /**
     * Set the application locale.
     */
    public function setLocale(Request $request, string $locale)
    {
        // Validate locale
        if (!in_array($locale, ['en', 'es'])) {
            $locale = config('app.locale');
        }
        
        // Set the app locale
        App::setLocale($locale);
        
        // Store locale in session
        Session::put('locale', $locale);
        
        // Redirect back or to home
        return redirect()->back()->withCookie(cookie()->forever('locale', $locale));
    }
}