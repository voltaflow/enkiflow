<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Logout from all spaces (global logout).
     */
    public function logoutAll(Request $request): RedirectResponse
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
            'auto_redirect' => [
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