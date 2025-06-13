<?php

namespace App\Policies\Traits;

use App\Models\Space;

trait ResolvesCurrentSpace
{
    /**
     * Get the current space from tenant() helper or domain.
     */
    protected function getCurrentSpace(): ?Space
    {
        // Try to get space from tenant() helper first
        $space = tenant();
        
        \Log::debug('ResolvesCurrentSpace::getCurrentSpace - tenant() result', [
            'tenant_found' => $space ? 'yes' : 'no',
            'tenant_id' => $space ? $space->id : null,
            'tenant_key' => $space && method_exists($space, 'getTenantKey') ? $space->getTenantKey() : null,
            'host' => request()->getHost(),
        ]);
        
        // If not available or has no ID, get from domain
        if (!$space || !$space->id) {
            $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', request()->getHost())->first();
            if ($domain) {
                $space = Space::find($domain->tenant_id);
                \Log::debug('ResolvesCurrentSpace::getCurrentSpace - loaded from domain', [
                    'domain' => $domain->domain,
                    'tenant_id' => $domain->tenant_id,
                    'space_found' => $space ? 'yes' : 'no',
                    'space_id' => $space ? $space->id : null,
                ]);
            }
        }
        
        return $space;
    }
}