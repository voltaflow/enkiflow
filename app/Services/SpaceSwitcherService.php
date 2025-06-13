<?php

namespace App\Services;

use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SpaceSwitcherService
{
    /**
     * Get all spaces for a user with minimal data for the space switcher.
     */
    public function getSpacesForUser(User $user): Collection
    {
        $cacheKey = "user:{$user->id}:spaces";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            return $user->spaces()
                ->with('domains:id,tenant_id,domain')
                ->get(['tenants.id', 'tenants.name'])
                ->map(function ($space) {
                    return [
                        'id' => (string) $space->id,
                        'name' => $space->name,
                        'domains' => $space->domains->map(function ($domain) {
                            return ['domain' => $domain->domain];
                        })->toArray(),
                    ];
                });
        });
    }
    
    /**
     * Get the current space for a user.
     */
    public function getCurrentSpace(): ?Space
    {
        if (function_exists('tenant') && tenant()) {
            return tenant();
        }
        
        return null;
    }
    
    /**
     * Clear the spaces cache for a user.
     */
    public function clearSpacesCache(User $user): void
    {
        Cache::forget("user:{$user->id}:spaces");
    }
}