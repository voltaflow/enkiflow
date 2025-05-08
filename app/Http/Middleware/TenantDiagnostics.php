<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpFoundation\Response;

class TenantDiagnostics
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        
        // Registrar información de diagnóstico
        Log::info("[TenantDiagnostics] Procesando solicitud para dominio: {$host}");
        
        // Buscar el dominio en la base de datos
        $domain = Domain::where('domain', $host)->first();
        
        if ($domain) {
            Log::info("[TenantDiagnostics] Dominio encontrado: " . json_encode($domain->toArray()));
            
            // Intentar obtener el tenant asociado
            try {
                $tenant = \App\Models\Space::find($domain->tenant_id);
                
                if ($tenant) {
                    Log::info("[TenantDiagnostics] Tenant encontrado: {$tenant->name} (ID: {$tenant->id})");
                } else {
                    Log::warning("[TenantDiagnostics] Dominio apunta a un tenant que no existe (ID: {$domain->tenant_id})");
                }
            } catch (\Exception $e) {
                Log::error("[TenantDiagnostics] Error al obtener tenant: " . $e->getMessage());
            }
        } else {
            Log::warning("[TenantDiagnostics] No se encontró un registro de dominio para: {$host}");
            
            // Verificar si hay algún dominio similar
            $similarDomains = Domain::all()->pluck('domain')->toArray();
            Log::info("[TenantDiagnostics] Dominios disponibles: " . implode(', ', $similarDomains));
        }
        
        return $next($request);
    }
}