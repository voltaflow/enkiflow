@extends('landing.templates.landing-template')

@section('content')
    <div class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-16">
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        Sobre EnkiFlow
                    </h1>
                    <p class="text-xl text-gray-600 dark:text-gray-300">
                        Nuestra misión es ayudar a equipos a gestionar su tiempo de manera más eficiente.
                    </p>
                </div>
                
                <div class="prose prose-lg dark:prose-invert max-w-none">
                    <p>
                        EnkiFlow nació de la necesidad de tener una herramienta integral que permitiera a equipos de cualquier tamaño gestionar sus proyectos y su tiempo de manera efectiva. Después de años trabajando en empresas tecnológicas, nos dimos cuenta de que las soluciones existentes eran complejas, difíciles de utilizar o carecían de características clave.
                    </p>
                    
                    <p>
                        Nuestra plataforma combina la gestión de proyectos, el seguimiento del tiempo y la generación de informes en una única solución intuitiva. Nos esforzamos por crear una experiencia de usuario excepcional que permita a los equipos concentrarse en lo que realmente importa: hacer su trabajo.
                    </p>
                    
                    <h2 class="text-3xl font-bold mt-12 mb-6">Nuestros valores</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-8">
                        <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl">
                            <h3 class="text-xl font-semibold mb-3">Simplicidad</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Creemos que las mejores herramientas son aquellas que son fáciles de usar pero potentes en sus capacidades.
                            </p>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl">
                            <h3 class="text-xl font-semibold mb-3">Transparencia</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Valoramos la honestidad y la claridad en todo lo que hacemos, desde nuestra estructura de precios hasta nuestras comunicaciones.
                            </p>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl">
                            <h3 class="text-xl font-semibold mb-3">Innovación</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Estamos constantemente buscando nuevas formas de mejorar nuestra plataforma y ofrecer más valor a nuestros usuarios.
                            </p>
                        </div>
                    </div>
                    
                    <h2 class="text-3xl font-bold mt-12 mb-6">Nuestro equipo</h2>
                    
                    <p>
                        Somos un equipo diverso de profesionales apasionados por crear productos que mejoren la vida laboral de las personas. Nuestro equipo combina experiencia en desarrollo de software, diseño de productos y gestión de proyectos para ofrecer una solución que realmente funciona para nuestros usuarios.
                    </p>
                    
                    <div class="mt-8">
                        <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6 mb-8">
                            <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold">Ana Martínez</h3>
                                <p class="text-blue-600 dark:text-blue-400">Fundadora y CEO</p>
                                <p class="text-gray-600 dark:text-gray-300 mt-2">
                                    Ana tiene más de 10 años de experiencia en gestión de proyectos y desarrollo de productos.
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6">
                            <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold">Carlos Rodríguez</h3>
                                <p class="text-blue-600 dark:text-blue-400">CTO</p>
                                <p class="text-gray-600 dark:text-gray-300 mt-2">
                                    Carlos es un desarrollador experimentado con un historial probado en la creación de plataformas escalables.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection