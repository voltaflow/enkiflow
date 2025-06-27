<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SpaceSetupController extends Controller
{
    /**
     * Show the first step of the space setup wizard.
     */
    public function index()
    {
        return Inertia::render('Spaces/Setup/Index', [
            'plans' => [
                [
                    'id' => 'free',
                    'name' => 'Gratuito',
                    'description' => 'Para pequeños equipos o uso personal',
                    'price' => '0',
                    'features' => [
                        'Hasta 3 usuarios',
                        'Proyectos ilimitados',
                        'Tareas básicas',
                        'Sin soporte prioritario',
                    ],
                    'most_popular' => false,
                ],
                [
                    'id' => 'pro',
                    'name' => 'Profesional',
                    'description' => 'Para equipos medianos que necesitan más funcionalidades',
                    'price' => '9.99',
                    'features' => [
                        'Hasta 10 usuarios',
                        'Proyectos ilimitados',
                        'Tareas avanzadas con etiquetas',
                        'Estadísticas básicas',
                        'Soporte por email',
                    ],
                    'most_popular' => true,
                ],
                [
                    'id' => 'business',
                    'name' => 'Empresarial',
                    'description' => 'Para grandes equipos con necesidades avanzadas',
                    'price' => '19.99',
                    'features' => [
                        'Usuarios ilimitados',
                        'Todas las funcionalidades',
                        'Estadísticas avanzadas',
                        'Prioridad en desarrollo de características',
                        'Soporte prioritario 24/7',
                    ],
                    'most_popular' => false,
                ],
            ],
        ]);
    }

    /**
     * Show the second step of the space setup wizard (space details).
     */
    public function details(Request $request)
    {
        $plan = $request->input('plan', 'free');

        // Validate plan
        if (! in_array($plan, ['free', 'pro', 'business'])) {
            $plan = 'free';
        }

        return Inertia::render('Spaces/Setup/Details', [
            'plan' => $plan,
        ]);
    }

    /**
     * Show the third step of the space setup wizard (invite members).
     */
    public function inviteMembers(Request $request)
    {
        // Validate the space details
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?$/'],
            'plan' => ['required', 'string', 'in:free,pro,business'],
        ]);

        // Store in session for use in store method
        session([
            'space_setup.name' => $request->input('name'),
            'space_setup.subdomain' => $request->input('subdomain'),
            'space_setup.plan' => $request->input('plan'),
        ]);

        return Inertia::render('Spaces/Setup/InviteMembers', [
            'name' => $request->input('name'),
            'subdomain' => $request->input('subdomain'),
            'plan' => $request->input('plan'),
        ]);
    }

    /**
     * Show the fourth step of the space setup wizard (confirmation).
     */
    public function confirm(Request $request)
    {
        // Validate the invites
        $request->validate([
            'invites' => ['nullable', 'array'],
            'invites.*.email' => ['required', 'email'],
            'invites.*.role' => ['required', 'string', 'in:admin,manager,member,guest'],
        ]);

        // Store in session for use in store method
        session(['space_setup.invites' => $request->input('invites', [])]);

        // Get setup data from session
        $setupData = [
            'name' => session('space_setup.name'),
            'subdomain' => session('space_setup.subdomain'),
            'plan' => session('space_setup.plan'),
            'invites' => session('space_setup.invites', []),
        ];

        return Inertia::render('Spaces/Setup/Confirm', $setupData);
    }

    /**
     * Create the space and finish the setup.
     */
    public function store(Request $request)
    {
        // Get all data from session
        $name = session('space_setup.name');
        $subdomain = session('space_setup.subdomain');
        $plan = session('space_setup.plan');
        $invites = session('space_setup.invites', []);

        // Final validation
        if (! $name || ! $subdomain || ! $plan) {
            return redirect()->route('spaces.setup.index')
                ->with('error', 'Falta información necesaria para crear el espacio.');
        }

        // Check if subdomain is unique
        if (DB::table('domains')->where('domain', $subdomain.'.'.config('app.url'))->exists()) {
            return redirect()->route('spaces.setup.details')
                ->with('error', 'El subdominio ya está en uso. Por favor, elige otro.');
        }

        // Create the space inside a transaction
        DB::beginTransaction();

        try {
            // Create the space
            $space = Space::create([
                'id' => Str::uuid()->toString(),
                'name' => $name,
                'slug' => $subdomain, // Add slug field
                'owner_id' => Auth::id(),
                'data' => [
                    'plan' => $plan,
                ],
            ]);

            // Create the domain
            $domainName = $subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST);
            $space->domains()->create([
                'domain' => $domainName,
            ]);

            // Add owner with admin role
            $space->users()->attach(Auth::id(), [
                'role' => 'admin',
            ]);

            // Process invites (in a real app, you would send emails here)
            foreach ($invites as $invite) {
                // Check if user exists
                $user = User::where('email', $invite['email'])->first();

                // Skip invite if user doesn't exist
                if (! $user) {
                    continue;
                }

                // Add user to space
                if (! $space->users()->where('user_id', $user->id)->exists()) {
                    $space->users()->attach($user->id, [
                        'role' => $invite['role'],
                    ]);
                }
            }

            // Database creation is handled automatically by the TenantCreated event
            // which is fired when the Space model is created

            // Update subscription quantity
            $space->syncMemberCount();

            // Clear session data
            $request->session()->forget([
                'space_setup.name',
                'space_setup.subdomain',
                'space_setup.plan',
                'space_setup.invites',
            ]);

            DB::commit();

            // Redirect to the new space (in a real app, you would show a success page first)
            return redirect()->route('spaces.show', $space->id)
                ->with('success', 'Espacio creado correctamente. ¡Bienvenido a '.$space->name.'!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('spaces.setup.index')
                ->with('error', 'Error al crear el espacio: '.$e->getMessage());
        }
    }
}
