<?php

namespace App\Http\Middleware;

use App\Enums\SpacePermission;
use App\Enums\SpaceRole;
use App\Traits\HasSpacePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantRole
{
    use HasSpacePermissions;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roleOrPermission  Nombre del rol o permiso requerido
     */
    public function handle(Request $request, Closure $next, string $roleOrPermission): Response
    {
        // Skip en dominios principales
        $mainDomains = config('tenancy.central_domains', []);
        if (in_array($request->getHost(), $mainDomains)) {
            return $next($request);
        }
        
        // Skip si no estamos en un contexto de tenant
        if (! tenant()) {
            return $next($request);
        }

        $user = $request->user();

        // Asegurar que el usuario esté autenticado
        if (! $user) {
            return redirect()->route('login');
        }

        // Obtener el espacio actual
        $space = $this->getCurrentSpace();
        if (!$space) {
            abort(404, 'Espacio no encontrado');
        }

        // Obtener el SpaceUser
        $spaceUser = $this->getSpaceUser($user, $space);
        if (!$spaceUser) {
            return response()->view('errors.unauthorized-tenant', [
                'space' => $space,
                'message' => 'No tienes acceso a este espacio de trabajo',
            ], 403);
        }

        // Verificar si es un rol o un permiso
        if (str_starts_with($roleOrPermission, 'role:')) {
            // Es un rol
            $roleName = substr($roleOrPermission, 5);
            try {
                $role = SpaceRole::from($roleName);
                if (!$spaceUser->hasRoleEqualOrHigherThan($role)) {
                    return response()->view('errors.unauthorized-tenant', [
                        'space' => $space,
                        'message' => 'No tienes el rol necesario para acceder a esta sección',
                    ], 403);
                }
            } catch (\ValueError $e) {
                // Rol inválido
                abort(500, 'Configuración de rol inválida');
            }
        } else {
            // Es un permiso
            try {
                $permission = SpacePermission::from($roleOrPermission);
                if (!$spaceUser->hasPermission($permission)) {
                    return response()->view('errors.unauthorized-tenant', [
                        'space' => $space,
                        'message' => 'No tienes los permisos necesarios para acceder a esta sección',
                    ], 403);
                }
            } catch (\ValueError $e) {
                // Permiso inválido
                abort(500, 'Configuración de permiso inválida');
            }
        }

        return $next($request);
    }
}