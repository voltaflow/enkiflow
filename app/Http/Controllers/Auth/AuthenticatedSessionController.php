<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        
        // Establecer una cookie de sesión de larga duración si se seleccionó "recordarme"
        if ($request->boolean('remember')) {
            config(['session.lifetime' => 60 * 24 * 30]); // 30 días
        }

        // Verificar si estamos en un tenant o en la aplicación central
        if (tenancy()->initialized) {
            // Estamos en un dominio de tenant
            // Simplemente redirigir al dashboard del tenant
            // La verificación de acceso se hace en el middleware EnsureUserHasTenantAccess
            return redirect()->intended('/dashboard');
        }

        $user = auth()->user();
        
        // Si es una petición Inertia (AJAX), siempre redirigir a la página de espacios
        // para evitar problemas de CORS con subdominios
        if ($request->header('X-Inertia')) {
            // Si el usuario tiene espacios, ir a la página de selección
            if ($user->spaces()->count() > 0) {
                return redirect()->route('spaces.index');
            }
            // Si no tiene espacios, ir a la creación
            return redirect()->route('spaces.create');
        }
        
        // Para peticiones normales (no-AJAX), mantener la lógica original
        // CASO 1: Si el usuario solo tiene un espacio, ir directamente a él
        if ($user->spaces()->count() === 1) {
            $space = $user->spaces()->first();
            
            // Guardar la fecha de último acceso
            $user->spaces()->updateExistingPivot($space->id, [
                'last_accessed_at' => now(),
            ]);
                
            // Usar la ruta teleport
            return redirect()->route('teleport', ['space' => $space->id]);
        }
        
        // CASO 2: Si el usuario tiene un espacio usado recientemente
        $lastSpace = $user->spaces()
            ->wherePivot('last_accessed_at', '!=', null)
            ->orderByPivot('last_accessed_at', 'desc')
            ->first();
            
        if ($lastSpace && $request->cookie('auto_redirect') === 'true') {
            // Actualizar fecha de último acceso
            $user->spaces()->updateExistingPivot($lastSpace->id, [
                'last_accessed_at' => now(),
            ]);
            
            // Usar la ruta teleport
            return redirect()->route('teleport', ['space' => $lastSpace->id]);
        }
        
        // CASO 3: Si el usuario tiene espacios, mostrar la página de selección
        if ($user->spaces()->count() > 0) {
            return redirect()->intended(route('spaces.index', absolute: false));
        }

        // Si no tiene espacios, redirigir a la creación de espacio
        return redirect()->intended(route('spaces.create', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Eliminar todas las cookies de sesión
        $domain = config('session.domain');
        $cookies = [
            'enkiflow_session' => [
                'domain' => $domain,
                'path' => '/',
            ],
            'XSRF-TOKEN' => [
                'domain' => $domain,
                'path' => '/',
            ],
        ];
        
        $response = redirect('/');
        
        foreach ($cookies as $name => $options) {
            $response->cookie($name, '', -1, $options['path'], $options['domain']);
        }
        
        return $response;
    }
}
