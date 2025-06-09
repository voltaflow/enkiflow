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

        // Verificar si estamos en un tenant o en la aplicación central
        if (tenancy()->initialized) {
            // Estamos en un dominio de tenant
            // Simplemente redirigir al dashboard del tenant
            // La verificación de acceso se hace en el middleware EnsureUserHasTenantAccess
            return redirect()->intended('/dashboard');
        } else {
            // Estamos en el dominio principal
            // Si el usuario tiene espacios, redirigir a la lista de espacios
            if (auth()->user()->spaces()->count() > 0) {
                return redirect()->intended(route('spaces.index'));
            }

            // Si no tiene espacios, redirigir a la creación de espacio
            return redirect()->intended(route('spaces.create'));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
