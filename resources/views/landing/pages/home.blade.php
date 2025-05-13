@extends('landing.templates.landing-template')

@section('content')
    @include('landing.organisms.hero-section')
    @include('landing.organisms.features-section')
    @include('landing.organisms.interactive-demo')
    @include('landing.organisms.testimonials-section')
    @include('landing.organisms.pricing-section')
    @include('landing.organisms.cta-section')
@endsection