{{-- CTA Section for EnkiFlow Landing Page --}}
<section class="py-20 bg-gradient-to-r from-blue-600 to-indigo-700 dark:from-blue-800 dark:to-indigo-900 relative overflow-hidden">
    {{-- Decorative elements --}}
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white rounded-full opacity-10 filter blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-white rounded-full opacity-10 filter blur-3xl"></div>
    </div>
    
    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                {{ __('landing.cta_title', 'Ready to Transform Your Workflow?') }}
            </h2>
            <p class="text-xl text-blue-100 mb-10 leading-relaxed">
                {{ __('landing.cta_description', 'Join over 10,000 teams who have already revolutionized their productivity with EnkiFlow. Start your free 14-day trial today – no credit card required.') }}
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="px-8 py-4 bg-white hover:bg-gray-100 text-blue-600 font-medium rounded-lg transition duration-300 shadow-md hover:shadow-lg">
                    {{ __('landing.cta_button_primary', 'Start Free Trial') }}
                </a>
                <a href="{{ route('landing.demos.timetracking') }}" class="px-8 py-4 bg-transparent hover:bg-blue-700 text-white border border-white font-medium rounded-lg transition duration-300">
                    {{ __('landing.cta_button_secondary', 'Watch Demo') }}
                </a>
            </div>
            
            <p class="text-blue-100 mt-6 text-sm">
                {{ __('landing.cta_no_cc', 'No credit card required. Cancel anytime.') }}
            </p>
        </div>
    </div>
</section>
