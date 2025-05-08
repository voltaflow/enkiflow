<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Suscripción Requerida</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    <style>
        body {
            font-family: 'Figtree', sans-serif;
            color: #1a202c;
            background-color: #f7fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }

        .container {
            max-width: 36rem;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
            text-align: center;
        }

        .logo {
            height: 4rem;
            margin-bottom: 2rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        p {
            color: #4a5568;
            margin-bottom: 1.5rem;
        }

        .btn {
            background-color: #4f46e5;
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            font-weight: 500;
            display: inline-block;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #4338ca;
        }

        .btn-secondary {
            background-color: transparent;
            border: 1px solid #d1d5db;
            color: #4a5568;
            margin-left: 1rem;
        }

        .btn-secondary:hover {
            background-color: #f9fafb;
        }
        
        .space-info {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            text-align: left;
        }
        
        .space-name {
            font-weight: 600;
            color: #4f46e5;
        }
        
        .status-icon {
            display: inline-block;
            width: 5rem;
            height: 5rem;
            background-color: #fef2f2;
            border-radius: 9999px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .status-icon svg {
            color: #ef4444;
            width: 2.5rem;
            height: 2.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('logo.svg') }}" alt="Logo" class="logo">
        
        <div class="status-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        
        <h1>Suscripción Requerida</h1>
        <p>El espacio de trabajo que intentas acceder requiere una suscripción activa para continuar.</p>
        
        @if(isset($tenant))
            <div class="space-info">
                <p>El espacio <span class="space-name">{{ $tenant->name }}</span> no tiene una suscripción activa o su período de prueba ha finalizado.</p>
                
                @if(isset($owner) && auth()->check() && auth()->user()->id === $owner->id)
                    <p>Como propietario del espacio, puedes actualizar la suscripción desde la configuración del espacio.</p>
                    <div class="actions" style="margin-top: 1.5rem;">
                        <a href="{{ route('spaces.subscriptions.create', $tenant->id) }}" class="btn">Actualizar Suscripción</a>
                    </div>
                @else
                    <p>Por favor, contacta con el administrador del espacio para resolver este problema.</p>
                @endif
            </div>
        @endif
        
        <div class="actions" style="margin-top: 2rem;">
            <a href="{{ route('spaces.index') }}" class="btn">Mis Espacios</a>
            <a href="#" onclick="window.history.back()" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</body>
</html>