<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $sharedData = [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'csrf_token' => csrf_token(),
            'ziggy' => fn (): array => [
                ...(new Ziggy($request->route()?->getName()))->toArray(),
                'location' => $request->url(),
                'query' => $request->all(),
            ],
            'locale' => [
                'current' => app()->getLocale(),
                'available' => config('app.available_locales', ['en' => 'English', 'es' => 'Español']),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];

        // Añadir información del espacio actual si estamos en un tenant
        if (function_exists('tenant') && tenant()) {
            $currentSpace = tenant();
            $sharedData['currentSpace'] = [
                'id' => $currentSpace->id,
                'name' => $currentSpace->name,
                'domains' => $currentSpace->domains->map(function ($domain) {
                    return ['domain' => $domain->domain];
                }),
            ];
            
            // Mantener la información del tenant por compatibilidad
            $sharedData['tenant'] = [
                'id' => tenant('id'),
                'name' => tenant('name'),
                'domains' => tenant()->domains()->get(['domain'])->toArray(),
            ];
            
            // Si el usuario está autenticado, añadir todos sus espacios para el switcher
            if ($request->user()) {
                $sharedData['userSpaces'] = $request->user()
                    ->spaces()
                    ->with('domains')
                    ->get(['tenants.id', 'tenants.name'])
                    ->toArray();
            }
        }

        return $sharedData;
    }
}
