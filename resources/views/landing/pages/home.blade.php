@extends('landing.templates.landing-template')

@section('content')
    <div class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-6">
                    Simplifica tu gestión de tiempo y proyectos
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-10">
                    EnkiFlow te ayuda a gestionar proyectos, tareas y tiempo en un solo lugar, mejorando la productividad de tu equipo.
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="/register" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-md">
                        Comenzar gratis
                    </a>
                    <a href="#features" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium rounded-lg transition-colors">
                        Ver características
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="features" class="py-20 bg-gray-50 dark:bg-gray-800">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Características principales
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                    Todo lo que necesitas para gestionar tus proyectos y tu tiempo eficientemente.
                </p>
            </div>
        </div>
    </div>

    @include('landing.organisms.interactive-demo')

    <div class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Planes y precios
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                    Soluciones para equipos de todos los tamaños.
                </p>
            </div>
        </div>
    </div>

    <div class="py-20 bg-blue-600">
        <div class="container mx-auto px-4">
            <div class="text-center text-white">
                <h2 class="text-3xl md:text-4xl font-bold mb-6">¿Listo para mejorar tu productividad?</h2>
                <p class="text-xl opacity-90 mb-10 max-w-3xl mx-auto">
                    Comienza a usar EnkiFlow hoy y optimiza la gestión de tu tiempo y proyectos.
                </p>
                <a href="/register" class="inline-block px-8 py-4 bg-white text-blue-600 font-bold rounded-lg shadow-lg hover:bg-gray-100 transition-colors">
                    Comenzar ahora
                </a>
            </div>
        </div>
    </div>
@endsection