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
        // Skip if we're not in a tenant context
        if (!tenant()) {
            return $next($request);
        }
        
        $user = $request->user();
        
        // Ensure authenticated user
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Get the current space/tenant
        $space = tenant();
        
        // Ensure user belongs to the space
        $spaceUser = $this->getSpaceUser($user, $space);
        
        if (!$spaceUser) {
            return response()->view('errors.unauthorized-tenant', [
                'space' => $space,
            ], 403);
        }
        
        // Check if the user has VIEW_SPACE permission (all roles should have this)
        if (!$spaceUser->hasPermission(SpacePermission::VIEW_SPACE)) {
            return response()->view('errors.unauthorized-tenant', [
                'space' => $space,
            ], 403);
        }
        
        return $next($request);
    }
}