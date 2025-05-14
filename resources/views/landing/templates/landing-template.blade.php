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
                    <a href="/{{ App::getLocale() }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Home</a>
                    <a href="/{{ App::getLocale() }}/features" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Features</a>
                    <a href="/{{ App::getLocale() }}/pricing" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Pricing</a>
                    <a href="/{{ App::getLocale() }}/about" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">About</a>
                    <a href="/{{ App::getLocale() }}/contact" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Contact</a>
                </nav>
                
                <div class="flex items-center space-x-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Log in</a>
                        <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Sign up</a>
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
                    <p class="text-gray-600 dark:text-gray-300">Time tracking and project management tools for teams of all sizes.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Product</h3>
                    <ul class="space-y-2">
                        <li><a href="/{{ App::getLocale() }}/features" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Features</a></li>
                        <li><a href="/{{ App::getLocale() }}/pricing" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Pricing</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Integrations</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Resources</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Documentation</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Blog</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Support</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Company</h3>
                    <ul class="space-y-2">
                        <li><a href="/{{ App::getLocale() }}/about" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">About</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Careers</a></li>
                        <li><a href="/{{ App::getLocale() }}/contact" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Contact</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-600 dark:text-gray-300">&copy; {{ date('Y') }} EnkiFlow. All rights reserved.</p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Privacy Policy</a>
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>