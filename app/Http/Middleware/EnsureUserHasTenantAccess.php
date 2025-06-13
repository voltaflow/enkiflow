<?php

namespace App\Http\Middleware;

use App\Enums\SpacePermission;
use App\Models\Space;
use App\Traits\HasSpacePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTenantAccess
{
    use HasSpacePermissions;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lista de dominios principales donde este middleware NO debe ejecutarse
        $mainDomains = config('tenancy.central_domains', []);
        
        // Si estamos en un dominio principal, skip este middleware completamente
        if (in_array($request->getHost(), $mainDomains)) {
            return $next($request);
        }
        
        // Skip if we're not in a tenant context
        if (! tenant()) {
            return $next($request);
        }

        $user = $request->user();

        // Ensure authenticated user
        if (! $user) {
            return redirect()->route('login');
        }

        // Get the current space/tenant from domain
        $currentDomain = $request->getHost();
        $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $currentDomain)->first();
        
        if (!$domain) {
            abort(404);
        }
        
        // Load the space directly
        $space = \App\Models\Space::find($domain->tenant_id);
        
        if (!$space) {
            abort(404);
        }

        // Ensure user belongs to the space
        $spaceUser = $this->getSpaceUser($user, $space);

        if (! $spaceUser) {
            return response()->view('errors.unauthorized-tenant', [
                'space' => $space,
            ], 403);
        }

        // Check if the user has VIEW_SPACE permission (all roles should have this)
        if (! $spaceUser->hasPermission(SpacePermission::VIEW_SPACE)) {
            return response()->view('errors.unauthorized-tenant', [
                'space' => $space,
            ], 403);
        }

        return $next($request);
    }
}
