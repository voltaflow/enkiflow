@extends('landing.templates.landing-template')

@section('content')
    <div class="py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl md:text-4xl font-bold text-center mb-8">Time Tracking Demo</h1>
            <p class="text-lg text-center max-w-3xl mx-auto mb-12">
                Experience how our time tracking features work in this interactive demo.
            </p>
            
            @include('landing.organisms.interactive-demo')
        </div>
    </div>
@endsection