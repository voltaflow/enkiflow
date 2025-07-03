<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Space extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasFactory;

    // Set the domain model to use (needs to be public static)
    public static $domainModel = \Stancl\Tenancy\Database\Models\Domain::class;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * The table associated with the model.
     */
    protected $table = 'tenants';

    /**
     * Define the actual database columns (not virtual columns).
     * This prevents Stancl/Tenancy from putting these in the data column.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name', 
            'slug',
            'owner_id',
            'status',
            'auto_tracking_enabled',
            'trial_ends_at',
        ];
    }

    /**
     * Get a tenant by domain name, supporting subdomains.
     *
     * @param  string  $domain
     * @return self|null
     */
    public static function whereHasDomain($domain)
    {
        $segments = explode('.', $domain);
        $subdomain = $segments[0] ?? null;

        if ($subdomain) {
            // Buscar primero por dominio exacto
            $tenant = static::whereHas('domains', function ($query) use ($domain) {
                $query->where('domain', $domain);
            })->first();

            if ($tenant) {
                return $tenant;
            }

            // Si no se encuentra, buscar por subdominio
            return static::whereHas('domains', function ($query) use ($subdomain) {
                $query->where('domain', $subdomain);
            })->first();
        }

        // Fallback a la búsqueda normal por dominio completo
        return static::whereHas('domains', function ($query) use ($domain) {
            $query->where('domain', $domain);
        })->first();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'owner_id',
        'data',
        'auto_tracking_enabled',
        'status',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'plan',
        'member_count',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'trial_ends_at' => 'datetime',
        'auto_tracking_enabled' => 'boolean',
    ];

    /**
     * The owner of the space.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * The users that belong to the space.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'space_users', 'tenant_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the plan attribute from data.
     *
     * @return string|null
     */
    public function getPlanAttribute()
    {
        return $this->data['plan'] ?? null;
    }

    /**
     * Get the member count for the space.
     *
     * @return int
     */
    public function getMemberCountAttribute()
    {
        return $this->users()->count();
    }

    /**
     * Update subscription quantity based on member count.
     */
    public function syncMemberCount()
    {
        $owner = $this->owner;

        if ($owner && $owner->hasStripeId() && $owner->subscribed('default')) {
            $memberCount = $this->users()->count();
            $owner->subscription('default')->updateQuantity($memberCount);

            return true;
        }

        return false;
    }

    /**
     * Generate a subdomain from the company name.
     */
    public static function generateSubdomain(string $companyName): string
    {
        // Convert to lowercase and replace spaces with hyphens
        $subdomain = strtolower($companyName);
        $subdomain = preg_replace('/[^a-z0-9]+/', '-', $subdomain);
        $subdomain = trim($subdomain, '-');

        // Ensure subdomain is not empty
        if (empty($subdomain)) {
            $subdomain = 'space';
        }

        // Ensure subdomain is unique
        $originalSubdomain = $subdomain;
        $counter = 1;

        while (static::whereHas('domains', function ($query) use ($subdomain) {
            $query->where('domain', $subdomain);
        })->exists()) {
            $subdomain = $originalSubdomain.'-'.$counter;
            $counter++;
        }

        return $subdomain;
    }

    /**
     * Check if the space is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if auto tracking is enabled.
     */
    public function hasAutoTrackingEnabled(): bool
    {
        return $this->auto_tracking_enabled === true;
    }

    /**
     * Get the invitations for the space.
     */
    public function invitations()
    {
        return $this->hasMany(Invitation::class, 'tenant_id', 'id');
    }

    /**
     * Check if a user with the given email is already a member of this space.
     */
    public function hasMemberWithEmail(string $email): bool
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return false;
        }
        
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if there's a pending invitation for the given email.
     */
    public function hasPendingInvitationForEmail(string $email): bool
    {
        return $this->invitations()
            ->where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists();
    }

}
