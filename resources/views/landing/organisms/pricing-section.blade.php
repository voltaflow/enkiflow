{{-- Pricing Section for EnkiFlow Landing Page --}}
<section class="py-20 bg-white dark:bg-gray-800">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16 max-w-3xl mx-auto">
            <span class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 rounded-full text-sm font-medium mb-4">
                {{ __('landing.pricing_badge', 'Transparent Pricing') }}
            </span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                {{ __('landing.pricing_title', 'Choose the Perfect Plan for Your Team') }}
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-300">
                {{ __('landing.pricing_subtitle', 'All plans include a 14-day free trial with no credit card required') }}
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            {{-- Free Plan --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-xl overflow-hidden">
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('landing.plan_free_name', 'Starter') }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('landing.plan_free_description', 'For individuals getting started') }}</p>
                    
                    <div class="flex items-end mb-6">
                        <span class="text-4xl font-bold text-gray-900 dark:text-white">{{ __('landing.plan_free_price', 'Free') }}</span>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_free_feature_1', 'Up to 2 projects') }}
                        </li>
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_free_feature_2', 'Basic time tracking') }}
                        </li>
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_free_feature_3', '1 GB storage') }}
                        </li>
                        <li class="flex items-center text-gray-400">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            {{ __('landing.plan_free_feature_4_missing', 'No advanced reports') }}
                        </li>
                    </ul>
                    
                    <a href="{{ route('register') }}" class="block w-full text-center px-6 py-3 border border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-medium rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition duration-300">
                        {{ __('landing.plan_free_cta', 'Get Started Free') }}
                    </a>
                </div>
            </div>
            
            {{-- Pro Plan --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border-2 border-blue-500 dark:border-blue-400 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl overflow-hidden relative">
                <div class="absolute top-0 inset-x-0 bg-blue-500 dark:bg-blue-400 text-white text-xs font-bold uppercase tracking-wider py-1 text-center">
                    {{ __('landing.plan_popular_badge', 'Most Popular') }}
                </div>
                <div class="p-8 pt-10">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('landing.plan_pro_name', 'Professional') }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('landing.plan_pro_description', 'For small teams and professionals') }}</p>
                    
                    <div class="flex items-end mb-6">
                        <span class="text-4xl font-bold text-gray-900 dark:text-white">${{ __('landing.plan_pro_price', '12') }}</span>
                        <span class="text-gray-500 dark:text-gray-400 ml-2">/ {{ __('landing.monthly', 'month') }}</span>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_pro_feature_1', 'Unlimited projects') }}
                        </li>
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_pro_feature_2', 'Advanced time tracking') }}
                        </li>
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_pro_feature_3', '10 GB storage') }}
                        </li>
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_pro_feature_4', 'Team collaboration') }}
                        </li>
                    </ul>
                    
                    <a href="{{ route('register') . '?plan=pro' }}" class="block w-full text-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-300 shadow-md hover:shadow-lg">
                        {{ __('landing.plan_pro_cta', 'Start 14-Day Trial') }}
                    </a>
                </div>
            </div>
            
            {{-- Enterprise Plan --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-xl overflow-hidden">
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('landing.plan_enterprise_name', 'Enterprise') }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('landing.plan_enterprise_description', 'For large organizations') }}</p>
                    
                    <div class="flex items-end mb-6">
                        <span class="text-4xl font-bold text-gray-900 dark:text-white">${{ __('landing.plan_enterprise_price', '29') }}</span>
                        <span class="text-gray-500 dark:text-gray-400 ml-2">/ {{ __('landing.monthly', 'month') }}</span>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_enterprise_feature_1', 'Everything in Professional') }}
                        </li>
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_enterprise_feature_2', '100 GB storage') }}
                        </li>
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_enterprise_feature_3', 'Priority support') }}
                        </li>
                        <li class="flex items-center text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('landing.plan_enterprise_feature_4', 'Advanced security') }}
                        </li>
                    </ul>
                    
                    <a href="{{ route('register') . '?plan=enterprise' }}" class="block w-full text-center px-6 py-3 border border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-medium rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition duration-300">
                        {{ __('landing.plan_enterprise_cta', 'Contact Sales') }}
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-12">
            <p class="text-gray-500 dark:text-gray-400">
                {{ __('landing.pricing_question', 'Need something else?') }} 
                <a href="{{ route('landing.contact') }}" class="text-blue-600 dark:text-blue-400 font-medium hover:underline">
                    {{ __('landing.pricing_contact', 'Contact us') }}
                </a> 
                {{ __('landing.pricing_custom_solution', 'for a custom solution') }}
            </p>
        </div>
    </div>
</section>
