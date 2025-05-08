<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Espacio no encontrado</title>

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
            max-width: 32rem;
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
        }
        
        .domain-name {
            font-weight: 600;
            color: #4f46e5;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('logo.svg') }}" alt="Logo" class="logo">
        <h1>Espacio no encontrado</h1>
        <p>El espacio de trabajo que intentas acceder no existe o ha sido eliminado.</p>
        
        @if(isset($domain))
            <div class="space-info">
                <p>No se encontró ningún espacio asociado con el dominio <span class="domain-name">{{ $domain }}</span>.</p>
                <p>Por favor, verifica que la URL sea correcta o contacta al administrador del sistema.</p>
            </div>
        @endif
        
        <div class="actions">
            <a href="{{ route('spaces.index') }}" class="btn">Mis Espacios</a>
            <a href="#" onclick="window.history.back()" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</body>
</html>