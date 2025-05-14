@extends('landing.templates.landing-template')

@section('content')
    <div class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center mb-16">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-6">
                    {{ __('landing.features_page_title') }}
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300">
                    {{ __('landing.features_page_description') }}
                </p>
            </div>
            
            <!-- Feature List -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-16">
                <!-- Feature 1 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-8 rounded-xl">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">{{ __('landing.time_tracking_title') }}</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ __('landing.time_tracking_description') }}
                    </p>
                </div>
                
                <!-- Feature 2 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-8 rounded-xl">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">{{ __('landing.project_management_title') }}</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ __('landing.project_management_description') }}
                    </p>
                </div>
                
                <!-- Feature 3 -->
                <div class="bg-gray-50 dark:bg-gray-800 p-8 rounded-xl">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">{{ __('landing.detailed_reports_title') }}</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ __('landing.detailed_reports_description') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
