@component('landing.templates.landing-template', ['title' => config('app.name', 'EnkiFlow')])
    @component('landing.organisms.hero-section')
    @endcomponent

    @component('landing.organisms.features-section')
    @endcomponent

    @component('landing.organisms.interactive-demo', [
        'title' => __('landing.interactive_demo_title'),
        'description' => __('landing.interactive_demo_description')
    ])
    @endcomponent

    @component('landing.organisms.testimonials-section')
    @endcomponent

    @component('landing.organisms.pricing-section')
    @endcomponent

    @component('landing.organisms.cta-section')
    @endcomponent

    @component('landing.organisms.footer')
    @endcomponent

    @push('scripts')
        <script src="{{ asset('js/landing/solid-counter.js') }}"></script>
    @endpush
@endcomponent
