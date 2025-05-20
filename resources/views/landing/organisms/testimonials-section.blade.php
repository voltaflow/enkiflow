{{-- Testimonials Section for EnkiFlow Landing Page --}}
<section class="py-20 bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-blue-950 relative overflow-hidden">
    {{-- Decorative elements --}}
    <div class="absolute -bottom-24 -right-24 w-64 h-64 bg-blue-200 dark:bg-blue-800 rounded-full opacity-20 filter blur-3xl"></div>
    <div class="absolute top-12 left-12 w-48 h-48 bg-indigo-200 dark:bg-indigo-800 rounded-full opacity-20 filter blur-3xl"></div>
    
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white inline-block mb-4 bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-500 dark:from-blue-400 dark:to-indigo-300">{{ __('landing.testimonials_title', 'What Our Customers Are Saying') }}</h2>
            <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">{{ __('landing.testimonials_subtitle', 'Join thousands of teams that trust EnkiFlow to manage their projects and time effectively') }}</p>
        </div>
        
        {{-- Testimonial cards with hover effects --}}
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8 flex flex-col relative transform transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex items-center mb-4">
                    <img src="{{ asset('images/testimonials/user1.jpg') }}" alt="{{ __('landing.testimonial_1_alt') }}" class="w-16 h-16 rounded-full border-2 border-blue-100 dark:border-blue-900 mr-4">
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('landing.testimonial_1_name', 'Alex Roberts') }}</h4>
                        <p class="text-sm text-blue-600 dark:text-blue-400">{{ __('landing.testimonial_1_position', 'Product Manager at TechCorp') }}</p>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="flex text-yellow-400 mb-2">
                        @for ($i = 0; $i < 5; $i++)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        @endfor
                    </div>
                </div>
                <p class="text-gray-700 dark:text-gray-300 mb-6 flex-grow">{{ __('landing.testimonial_1_content', '"EnkiFlow has transformed the way our team manages projects. The AI-powered time tracking saves us hours each week, and the insights have helped us optimize our workflows significantly."') }}</p>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('landing.testimonial_1_date', '— Using EnkiFlow since 2023') }}</span>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8 flex flex-col relative transform transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl border border-gray-100 dark:border-gray-700 md:translate-y-6">
                <div class="flex items-center mb-4">
                    <img src="{{ asset('images/testimonials/user2.jpg') }}" alt="{{ __('landing.testimonial_2_alt') }}" class="w-16 h-16 rounded-full border-2 border-blue-100 dark:border-blue-900 mr-4">
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('landing.testimonial_2_name', 'Maria Stevens') }}</h4>
                        <p class="text-sm text-blue-600 dark:text-blue-400">{{ __('landing.testimonial_2_position', 'Team Lead at DesignHub') }}</p>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="flex text-yellow-400 mb-2">
                        @for ($i = 0; $i < 5; $i++)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        @endfor
                    </div>
                </div>
                <p class="text-gray-700 dark:text-gray-300 mb-6 flex-grow">{{ __('landing.testimonial_2_content', '"The intuitive interface and powerful features make EnkiFlow a must-have tool for any design team. We\'ve seen a 35% improvement in project delivery times since implementation. Customer support is also exceptional."') }}</p>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('landing.testimonial_2_date', '— Using EnkiFlow since 2022') }}</span>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8 flex flex-col relative transform transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex items-center mb-4">
                    <img src="{{ asset('images/testimonials/user3.jpg') }}" alt="{{ __('landing.testimonial_3_alt') }}" class="w-16 h-16 rounded-full border-2 border-blue-100 dark:border-blue-900 mr-4">
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('landing.testimonial_3_name', 'Daniel Kim') }}</h4>
                        <p class="text-sm text-blue-600 dark:text-blue-400">{{ __('landing.testimonial_3_position', 'CTO at StartupX') }}</p>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="flex text-yellow-400 mb-2">
                        @for ($i = 0; $i < 5; $i++)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        @endfor
                    </div>
                </div>
                <p class="text-gray-700 dark:text-gray-300 mb-6 flex-grow">{{ __('landing.testimonial_3_content', '"We\'ve tried several project management solutions, but EnkiFlow stands above the rest. Excellent support, regular updates, and features that actually help us work smarter. The ROI has been incredible."') }}</p>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('landing.testimonial_3_date', '— Using EnkiFlow since 2023') }}</span>
            </div>
        </div>
        
        {{-- Client logos --}}
        <div class="mt-20">
            <p class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-8">{{ __('landing.client_logos_title', 'Trusted by innovative companies') }}</p>
            <div class="flex flex-wrap justify-center items-center gap-x-12 gap-y-8">
                @foreach(['ACME Inc', 'TechCorp', 'StartupX', 'DesignHub', 'InnovateCo'] as $index => $company)
                    <img src="{{ asset('images/clients/logo' . ($index + 1) . '.png') }}" alt="{{ __('landing.client_logo_alt', ['company' => $company]) }}" class="h-7 opacity-60 grayscale hover:grayscale-0 hover:opacity-100 transition-all duration-300">
                @endforeach
            </div>
        </div>
    </div>
</section>
