<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantCreator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, TenantCreator $tenantCreator): RedirectResponse|Response
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => 'required|string|max:255',
            'enable_tracking' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        // Create tenant for the user if company name is provided
        if ($request->company_name) {
            $space = $tenantCreator->create($user, [
                'name' => $request->company_name,
                'auto_tracking_enabled' => $request->enable_tracking ?? false,
                'seed_data' => true,
            ]);

            // Generate the tenant URL with proper protocol
            $domain = config('app.domain', 'enkiflow.test');
            $protocol = request()->secure() ? 'https' : 'http';
            $tenantUrl = "{$protocol}://{$space->slug}.{$domain}";

            Auth::login($user);

            // Return a page that will handle the redirect client-side
            // This avoids CORS issues with cross-subdomain AJAX requests
            return Inertia::render('auth/register-redirect', [
                'url' => $tenantUrl
            ]);
        }

        Auth::login($user);

        return to_route('spaces.create');
    }
}
