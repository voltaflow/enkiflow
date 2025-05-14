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
