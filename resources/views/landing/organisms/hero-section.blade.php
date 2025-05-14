{{-- Hero Section for EnkiFlow Landing Page --}}
<section class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-blue-950 py-24 md:py-32 relative overflow-hidden">
    {{-- Decorative elements/blobs for modern look --}}
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 opacity-20 dark:opacity-10">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-400 dark:bg-blue-600 rounded-full filter blur-3xl"></div>
        <div class="absolute top-1/3 right-0 w-80 h-80 bg-indigo-400 dark:bg-indigo-600 rounded-full filter blur-3xl"></div>
        <div class="absolute bottom-0 left-1/4 w-64 h-64 bg-purple-400 dark:bg-purple-600 rounded-full filter blur-3xl"></div>
    </div>
    
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 mb-10 md:mb-0 md:pr-10">
                <div class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 rounded-full text-sm font-medium mb-4 animate-pulse">
                    {{ __('landing.hero_badge', 'NEW! Enhanced Time Tracking 2025') }}
                </div>
                
                <h1 class="text-4xl md:text-6xl font-bold text-gray-900 dark:text-white leading-tight mb-6">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-500 dark:from-blue-400 dark:to-indigo-300">{{ __('landing.hero_title_highlight', 'Revolutionize') }}</span> {{ __('landing.hero_title_main', 'Your Workflow') }}
                    <span class="block mt-2 text-gray-900 dark:text-white">{{ __('landing.hero_title_sub', 'With EnkiFlow') }}</span>
                </h1>
                
                <p class="text-xl text-gray-700 dark:text-gray-300 mb-8 leading-relaxed">
                    {{ __('landing.hero_description', 'Save 30% of your time daily. Intuitive project management, AI-powered time tracking, and actionable insights in one powerful platform.') }}
                </p>
                
                {{-- Email capture form --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg mb-8 border border-gray-100 dark:border-gray-700">
                    <form class="flex flex-col sm:flex-row gap-3" action="{{ route('landing.contact') }}" method="POST">
                        @csrf
                        <input type="email" name="email" placeholder="{{ __('landing.email_placeholder', 'Enter your email') }}" class="flex-grow px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-300 shadow-md hover:shadow-lg">
                            {{ __('landing.cta_trial', 'Start Free Trial') }}
                        </button>
                    </form>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">✓ {{ __('landing.trial_terms', '14-day free trial · No credit card required · Cancel anytime') }}</p>
                </div>
                
                <div class="flex flex-wrap gap-6 items-center">
                    <a href="#demo" class="inline-flex items-center font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                        </svg>
                        {{ __('landing.watch_demo', 'Watch Demo') }}
                    </a>
                </div>
            </div>
            
            <div class="md:w-1/2">
                <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-2xl transform rotate-1 border border-gray-100 dark:border-gray-700">
                    <div class="h-6 bg-gray-100 dark:bg-gray-700 flex items-center px-4">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                        <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    </div>
                    <div class="p-4">
                        <div class="w-full h-64 bg-gray-50 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 text-gray-400 dark:text-gray-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
