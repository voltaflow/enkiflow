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
    public function store(Request $request, TenantCreator $tenantCreator): RedirectResponse
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

            // Generate the tenant URL
            $domain = config('app.domain', 'enkiflow.test');
            $tenantUrl = "http://{$space->slug}.{$domain}";

            Auth::login($user);

            // Redirect to the tenant subdomain
            return redirect($tenantUrl)->with('success', 'Your workspace has been created successfully!');
        }

        Auth::login($user);

        return to_route('spaces.create');
    }
}
