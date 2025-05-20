@extends('landing.templates.landing-template')

@section('content')
    <div class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-6">
                    {{ __('landing.hero_title') }}
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-10">
                    {{ __('landing.hero_description') }}
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="/register" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-md">
                        {{ __('landing.get_started') }}
                    </a>
                    <a href="#features" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium rounded-lg transition-colors">
                        {{ __('landing.see_features') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="features" class="py-20 bg-gray-50 dark:bg-gray-800">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ __('landing.features_title') }}
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                    {{ __('landing.features_description') }}
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-10">
                <!-- Feature 1: Plan & Organize -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 dark:border-gray-700">
                    <div class="bg-blue-600 h-1.5 w-full"></div>
                    <div class="p-6">
                        <div class="flex items-start mb-4">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('landing.feature1_title') }}</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('landing.feature1_description') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 2: Time Tracking -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 dark:border-gray-700">
                    <div class="bg-green-600 h-1.5 w-full"></div>
                    <div class="p-6">
                        <div class="flex items-start mb-4">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('landing.feature2_title') }}</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('landing.feature2_description') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 3: Reports -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 dark:border-gray-700">
                    <div class="bg-purple-600 h-1.5 w-full"></div>
                    <div class="p-6">
                        <div class="flex items-start mb-4">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('landing.feature3_title') }}</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('landing.feature3_description') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 4: Invoicing -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 dark:border-gray-700">
                    <div class="bg-yellow-600 h-1.5 w-full"></div>
                    <div class="p-6">
                        <div class="flex items-start mb-4">
                            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('landing.feature4_title') }}</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('landing.feature4_description') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 5: Integrations -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 dark:border-gray-700">
                    <div class="bg-indigo-600 h-1.5 w-full"></div>
                    <div class="p-6">
                        <div class="flex items-start mb-4">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('landing.feature5_title') }}</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('landing.feature5_description') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 6: Security -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 dark:border-gray-700">
                    <div class="bg-red-600 h-1.5 w-full"></div>
                    <div class="p-6">
                        <div class="flex items-start mb-4">
                            <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('landing.feature6_title') }}</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('landing.feature6_description') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View More Features Button -->
            <div class="mt-12 text-center">
                <a href="{{ app()->getLocale() == 'es' ? '/es/features' : '/features' }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    {{ __('landing.see_features') }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 -mr-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    @include('landing.organisms.interactive-demo')

    <div class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ __('landing.pricing_title') }}
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                    {{ __('landing.pricing_description') }}
                </p>
            </div>
        </div>
    </div>

    <div class="py-20 bg-blue-600">
        <div class="container mx-auto px-4">
            <div class="text-center text-white">
                <h2 class="text-3xl md:text-4xl font-bold mb-6">{{ __('landing.cta_title') }}</h2>
                <p class="text-xl opacity-90 mb-10 max-w-3xl mx-auto">
                    {{ __('landing.cta_description') }}
                </p>
                <a href="/register" class="inline-block px-8 py-4 bg-white text-blue-600 font-bold rounded-lg shadow-lg hover:bg-gray-100 transition-colors">
                    {{ __('landing.start_now') }}
                </a>
            </div>
        </div>
    </div>
@endsection
