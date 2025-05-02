<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Space extends BaseTenant implements TenantWithDatabase
{
    use HasFactory, HasDatabase, HasDomains;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'id',
        'name',
        'owner_id',
        'data',
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
}
