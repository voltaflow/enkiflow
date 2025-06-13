<?php

namespace App\Http\Middleware;

use App\Models\Space;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TeleportToSpace
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        // Verificar que estamos en la ruta de teleport
        if ($request->route()->getName() === 'teleport') {
            $spaceId = $request->route('space');
            $space = Space::find($spaceId);
            
            if (!$space) {
                return redirect()->route('spaces.index')
                    ->with('error', 'El espacio solicitado no existe.');
            }
            
            // Verificar que el usuario tiene acceso al espacio
            $hasAccess = Auth::user()->spaces()
                ->where('tenant_id', $spaceId)
                ->exists();
                
            if (!$hasAccess) {
                return redirect()->route('spaces.index')
                    ->with('error', 'No tienes acceso a este espacio.');
            }
            
            // Actualizar la fecha de último acceso
            Auth::user()->spaces()->updateExistingPivot($spaceId, [
                'last_accessed_at' => now(),
            ]);
            
            // Generar un token temporal seguro
            $token = Str::random(64);
            
            // Guardar el token en Redis sin prefix para que sea accesible desde cualquier dominio
            $tokenData = [
                'user_id' => Auth::id(),
                'space_id' => $spaceId,
                'expires_at' => now()->addMinutes(1)->timestamp,
            ];
            
            // Usar conexión Redis compartida sin prefix
            $redis = Redis::connection('shared');
            $redisKey = 'autologin:' . $token;
            $redis->setex($redisKey, 60, json_encode($tokenData));
            
            // Verificar que se guardó correctamente
            $verify = $redis->get($redisKey);
            
            \Log::info('TeleportToSpace: Token created in Redis', [
                'user_id' => Auth::id(),
                'space_id' => $spaceId,
                'token' => substr($token, 0, 8) . '...',
                'redis_key' => $redisKey,
                'saved_successfully' => $verify ? 'yes' : 'no',
                'redis_db' => config('database.redis.shared.database')
            ]);
            
            // Construir la URL del subdominio
            $domain = $space->domains->first() ? 
                $space->domains->first()->domain : 
                $space->id . '.' . config('app.domain_base', 'enkiflow.test');
            
            // Construir URL completa con protocolo y token
            $protocol = request()->secure() ? 'https' : 'http';
            $url = $protocol . '://' . $domain . '/autologin/' . $token;
            
            // Redirigir al subdominio con el token
            return redirect()->away($url);
        }
        
        return $next($request);
    }
}