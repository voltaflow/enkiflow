{{-- FAQ Section for EnkiFlow Landing Page --}}
<section class="py-20 bg-white dark:bg-gray-800">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <span class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 rounded-full text-sm font-medium mb-4">
                {{ __('landing.faq_badge', 'Common Questions') }}
            </span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                {{ __('landing.faq_title', 'Frequently Asked Questions') }}
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                {{ __('landing.faq_subtitle', 'Everything you need to know about EnkiFlow') }}
            </p>
        </div>

        <div class="max-w-3xl mx-auto">
            <div class="space-y-6" x-data="{active: 0}">
                @php
                    $faqs = [
                        [
                            'question' => __('landing.faq_q1', 'How does the free trial work?'),
                            'answer' => __('landing.faq_a1', 'Our free trial gives you full access to EnkiFlow for 14 days. No credit card required, and you can cancel anytime. After your trial ends, you can choose to upgrade to one of our paid plans.')
                        ],
                        [
                            'question' => __('landing.faq_q2', 'Can I change my plan later?'),
                            'answer' => __('landing.faq_a2', 'Yes! You can upgrade, downgrade, or change your plan at any time. When you upgrade, the new features will be available immediately. When you downgrade, the changes will take effect at the end of your current billing cycle.')
                        ],
                        [
                            'question' => __('landing.faq_q3', 'Is there a limit to the number of projects I can create?'),
                            'answer' => __('landing.faq_a3', 'The free plan allows you to create up to 2 projects. Our Professional and Enterprise plans offer unlimited projects, so you can organize your work however you prefer.')
                        ],
                        [
                            'question' => __('landing.faq_q4', 'Can I import data from other tools?'),
                            'answer' => __('landing.faq_a4', 'Yes, EnkiFlow supports importing data from popular project management and time tracking tools. Our import wizard makes it easy to transition your existing data into EnkiFlow with just a few clicks.')
                        ],
                        [
                            'question' => __('landing.faq_q5', 'How secure is my data?'),
                            'answer' => __('landing.faq_a5', 'We take security seriously. All data is encrypted in transit and at rest. We implement industry-standard security practices, regular backups, and strict access controls. Our Enterprise plan offers additional security features for organizations with advanced requirements.')
                        ],
                        [
                            'question' => __('landing.faq_q6', 'What kind of support do you offer?'),
                            'answer' => __('landing.faq_a6', 'All plans include access to our help center and email support. Professional plan users receive priority email support, while Enterprise customers get dedicated support and can access phone support during business hours.')
                        ],
                    ];
                @endphp

                @foreach ($faqs as $index => $faq)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <button 
                        class="flex justify-between items-center w-full px-6 py-4 text-left font-medium text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 focus:outline-none transition-colors duration-200"
                        @click="active = active === {{ $index }} ? null : {{ $index }}"
                    >
                        <span>{{ $faq['question'] }}</span>
                        <svg 
                            class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200" 
                            :class="{'rotate-180': active === {{ $index }}}"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div 
                        class="overflow-hidden transition-all max-h-0 duration-300 ease-in-out bg-gray-50 dark:bg-gray-700/30" 
                        :class="{'max-h-96': active === {{ $index }}}"
                        x-ref="answer{{ $index }}"
                        x-bind:style="active === {{ $index }} ? 'max-height: ' + $refs.answer{{ $index }}.scrollHeight + 'px' : ''"
                    >
                        <div class="p-6 text-gray-600 dark:text-gray-300">
                            {{ $faq['answer'] }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="text-center mt-12">
                <p class="text-gray-600 dark:text-gray-300 mb-4">{{ __('landing.faq_more_questions', 'Still have questions?') }}</p>
                <a href="{{ route('landing.contact') }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 font-medium hover:text-blue-800 dark:hover:text-blue-300">
                    {{ __('landing.faq_contact_us', 'Contact our support team') }}
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>
