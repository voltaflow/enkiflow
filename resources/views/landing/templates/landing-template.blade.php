<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ session('appearance', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EnkiFlow') }}</title>
    <meta name="description" content="EnkiFlow - Time tracking, project management, and productivity tools for teams of all sizes">
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Vite Assets (Styles and Scripts) -->
    @vite(['resources/css/app.css'])
    
    <!-- Landing Page Scripts -->
    <script src="{{ asset('js/landing/enhanced-timer.js') }}" defer></script>
    
    <style>
        /* Base dark mode styles */
        .dark {
            --bg-color: #121212;
            --text-color: #e2e2e2;
            color-scheme: dark;
        }
        .dark body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        /* Basic styles */
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="antialiased bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <header class="py-4 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center">
                <a href="/{{ App::getLocale() }}" class="flex items-center space-x-2">
                    <img src="{{ asset('logo.svg') }}" alt="EnkiFlow Logo" class="h-8 w-auto">
                    <span class="font-bold text-xl">EnkiFlow</span>
                </a>
                
                <nav class="hidden md:flex space-x-6">
                    <a href="/{{ App::getLocale() }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.home') }}</a>
                    <a href="/{{ App::getLocale() }}/features" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.features') }}</a>
                    <a href="/{{ App::getLocale() }}/pricing" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.pricing') }}</a>
                    <a href="/{{ App::getLocale() }}/about" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.about') }}</a>
                    <a href="/{{ App::getLocale() }}/contact" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.contact') }}</a>
                </nav>
                
                <div class="flex items-center space-x-3">
                    <!-- Language Selector -->
                    <div class="relative mr-2">
                        <select onchange="window.location.href=this.value" class="appearance-none bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 py-1 px-2 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="{{ route('set-locale', 'en') }}" {{ App::getLocale() == 'en' ? 'selected' : '' }}>EN</option>
                            <option value="{{ route('set-locale', 'es') }}" {{ App::getLocale() == 'es' ? 'selected' : '' }}>ES</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-200">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                            </svg>
                        </div>
                    </div>
                    
                    @auth
                        <a href="{{ route('dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">{{ __('landing.dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.login') }}</a>
                        <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">{{ __('landing.signup') }}</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>
    
    <main>
        @yield('content')
    </main>
    
    <footer class="bg-gray-100 dark:bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <img src="{{ asset('logo.svg') }}" alt="EnkiFlow Logo" class="h-8 w-auto mb-4">
                    <p class="text-gray-600 dark:text-gray-300">{{ __('landing.footer_description') }}</p>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">{{ __('landing.product') }}</h3>
                    <ul class="space-y-2">
                        <li><a href="/{{ App::getLocale() }}/features" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.features') }}</a></li>
                        <li><a href="/{{ App::getLocale() }}/pricing" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.pricing') }}</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.integrations') }}</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">{{ __('landing.resources') }}</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.documentation') }}</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.blog') }}</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.support') }}</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">{{ __('landing.company') }}</h3>
                    <ul class="space-y-2">
                        <li><a href="/{{ App::getLocale() }}/about" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.about') }}</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.careers') }}</a></li>
                        <li><a href="/{{ App::getLocale() }}/contact" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.contact') }}</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-600 dark:text-gray-300">&copy; {{ date('Y') }} EnkiFlow. {{ __('landing.all_rights_reserved') }}</p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.privacy_policy') }}</a>
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">{{ __('landing.terms_of_service') }}</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
