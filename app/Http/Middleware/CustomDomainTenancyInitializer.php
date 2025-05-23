<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class CustomDomainTenancyInitializer extends InitializeTenancyByDomain
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        // If this is a main domain, bypass tenancy initialization
        if ($request->attributes->get('bypass_tenancy', false)) {
            Log::info('Skipping tenancy initialization for main domain: ' . $request->getHost());
            return $next($request);
        }

        Log::info('CustomDomainTenancyInitializer: Attempting to initialize tenancy for: ' . $request->getHost());

        try {
            // Standard tenancy initialization for non-main domains
            $result = parent::handle($request, $next);
            Log::info('CustomDomainTenancyInitializer: Successfully initialized tenancy for: ' . $request->getHost());
            return $result;
        } catch (\Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedException $e) {
            Log::error('CustomDomainTenancyInitializer: Tenant could not be identified for domain: ' . $request->getHost());
            Log::error('Exception details: ' . $e->getMessage());
            
            return response()->view('errors.tenant-not-found', [
                'domain' => $request->getHost(),
                'error' => 'Tenant could not be identified'
            ], 404);
        } catch (\Exception $e) {
            // Log the error with more details
            Log::error('CustomDomainTenancyInitializer: Unexpected error for domain: ' . $request->getHost());
            Log::error('Exception: ' . get_class($e) . ' - ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // For critical errors, show error page
            return response()->view('errors.tenant-not-found', [
                'domain' => $request->getHost(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}