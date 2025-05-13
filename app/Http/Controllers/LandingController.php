<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    /**
     * Display the landing home page.
     */
    public function index(): View
    {
        return view('landing.pages.home');
    }

    /**
     * Display the features page.
     */
    public function features(): View
    {
        return view('landing.pages.features');
    }

    /**
     * Display the pricing page.
     */
    public function pricing(): View
    {
        return view('landing.pages.pricing');
    }

    /**
     * Display the about page.
     */
    public function about(): View
    {
        return view('landing.pages.about');
    }

    /**
     * Display the contact page.
     */
    public function contact(): View
    {
        return view('landing.pages.contact');
    }

    /**
     * Display the time tracking interactive demo.
     */
    public function timeTrackingDemo(): View
    {
        return view('landing.pages.demos.time-tracking');
    }
}