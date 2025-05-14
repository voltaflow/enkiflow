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
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    
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
        
        /* Theme transition */
        html.transition,
        html.transition *,
        html.transition *:before,
        html.transition *:after {
            transition: all 200ms ease-in-out !important;
            transition-delay: 0 !important;
        }
    </style>
    
    <!-- Initial theme detection script (preload) -->
    <script>
        // On page load, check for saved theme preference
        function setupInitialTheme() {
            // Get stored theme from localStorage or cookie or default to system
            function getStoredTheme() {
                const localTheme = localStorage.getItem('theme');
                if (localTheme && ['light', 'dark', 'system'].includes(localTheme)) {
                    return localTheme;
                }
                
                // Check for cookie
                const cookieValue = document.cookie.split('; ')
                    .find(row => row.startsWith('appearance='))
                    ?.split('=')[1];
                    
                if (cookieValue && ['light', 'dark', 'system'].includes(cookieValue)) {
                    return cookieValue;
                }
                
                return 'system';
            }
            
            const theme = getStoredTheme();
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            // Apply theme without flash
            if (theme === 'dark' || (theme === 'system' && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        
        // Run immediately
        setupInitialTheme();
    </script>
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
                    <!-- Language Selector Component -->
                    <div class="mr-2">
                        @include('components.language-switcher')
                    </div>
                    
                    <!-- Theme Switcher Component -->
                    <div class="mr-2">
                        @include('components.theme-switcher')
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
    
    @if(config('app.debug'))
        @include('components.debug-language')
        @include('components.appearance-debug')
    @endif
</body>
</html>
