{{-- Footer Section for EnkiFlow Landing Page --}}
<footer class="bg-gray-50 dark:bg-gray-900 pt-16 pb-8">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-12">
            <div class="lg:col-span-2">
                <a href="{{ route('landing.home') }}" class="inline-block mb-6">
                    <img src="{{ asset('images/logo-full.png') }}" alt="{{ config('app.name') }}" class="h-10 dark:hidden">
                    <img src="{{ asset('images/logo-full-white.png') }}" alt="{{ config('app.name') }}" class="h-10 hidden dark:block">
                </a>
                
                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md">
                    {{ __('landing.footer_description', 'EnkiFlow helps teams boost productivity with intuitive project management and AI-powered time tracking. Save time, work smarter.') }}
                </p>
                
                <div class="flex space-x-4">
                    <a href="https://twitter.com/enkiflow" class="text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors duration-300">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723 10.054 10.054 0 01-3.127 1.195 4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                        </svg>
                    </a>
                    <a href="https://linkedin.com/company/enkiflow" class="text-gray-400 hover:text-blue-700 dark:hover:text-blue-500 transition-colors duration-300">
                        <span class="sr-only">LinkedIn</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                        </svg>
                    </a>
                    <a href="https://github.com/enkiflow" class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-300">
                        <span class="sr-only">GitHub</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
            
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                    {{ __('landing.footer_product', 'Product') }}
                </h3>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ route('landing.features') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_features', 'Features') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('landing.pricing') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_pricing', 'Pricing') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_login', 'Login') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('register') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_signup', 'Sign Up') }}
                        </a>
                    </li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                    {{ __('landing.footer_company', 'Company') }}
                </h3>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ route('landing.about') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_about', 'About Us') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('landing.contact') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_contact', 'Contact') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('landing.careers') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_careers', 'Careers') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('landing.blog') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_blog', 'Blog') }}
                        </a>
                    </li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                    {{ __('landing.footer_legal', 'Legal') }}
                </h3>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ route('landing.terms') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_terms', 'Terms of Service') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('landing.privacy') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_privacy', 'Privacy Policy') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('landing.cookies') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-300">
                            {{ __('landing.footer_cookies', 'Cookie Policy') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="pt-8 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-4 md:mb-0">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('landing.footer_copyright', 'All rights reserved') }}.
                </p>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        {{ __('landing.footer_help', 'Help Center') }}
                    </a>
                    <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        {{ __('landing.footer_support', 'Support') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>
