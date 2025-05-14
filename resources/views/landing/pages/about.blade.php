@extends('landing.templates.landing-template')

@section('content')
    <div class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-16">
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        {{ __('landing.about_page_title') }}
                    </h1>
                    <p class="text-xl text-gray-600 dark:text-gray-300">
                        {{ __('landing.about_page_description') }}
                    </p>
                </div>
                
                <div class="prose prose-lg dark:prose-invert max-w-none">
                    <p>
                        {{ __('landing.about_intro_p1') }}
                    </p>
                    
                    <p>
                        {{ __('landing.about_intro_p2') }}
                    </p>
                    
                    <h2 class="text-3xl font-bold mt-12 mb-6">{{ __('landing.our_values') }}</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-8">
                        <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl">
                            <h3 class="text-xl font-semibold mb-3">{{ __('landing.simplicity') }}</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                {{ __('landing.simplicity_description') }}
                            </p>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl">
                            <h3 class="text-xl font-semibold mb-3">{{ __('landing.transparency') }}</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                {{ __('landing.transparency_description') }}
                            </p>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl">
                            <h3 class="text-xl font-semibold mb-3">{{ __('landing.innovation') }}</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                {{ __('landing.innovation_description') }}
                            </p>
                        </div>
                    </div>
                    
                    <h2 class="text-3xl font-bold mt-12 mb-6">{{ __('landing.our_team') }}</h2>
                    
                    <p>
                        {{ __('landing.team_description') }}
                    </p>
                    
                    <div class="mt-8">
                        <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6 mb-8">
                            <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold">{{ __('landing.founder_name') }}</h3>
                                <p class="text-blue-600 dark:text-blue-400">{{ __('landing.founder_title') }}</p>
                                <p class="text-gray-600 dark:text-gray-300 mt-2">
                                    {{ __('landing.founder_description') }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6">
                            <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold">{{ __('landing.cto_name') }}</h3>
                                <p class="text-blue-600 dark:text-blue-400">{{ __('landing.cto_title') }}</p>
                                <p class="text-gray-600 dark:text-gray-300 mt-2">
                                    {{ __('landing.cto_description') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
