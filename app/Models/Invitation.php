<?php

namespace App\Models;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invitation extends Model
{
    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'email',
        'role',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'invited_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the space that the invitation belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class, 'tenant_id', 'id');
    }

    /**
     * Get the user who sent the invitation.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the logs for this invitation.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(InvitationLog::class);
    }

    /**
     * Check if the invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    /**
     * Check if the invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->status === 'pending' && $this->expires_at->isPast());
    }

    /**
     * Check if the invitation is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if the invitation is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    /**
     * Mark the invitation as accepted.
     */
    public function markAsAccepted(): self
    {
        $this->status = 'accepted';
        $this->accepted_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark the invitation as expired.
     */
    public function markAsExpired(): self
    {
        $this->status = 'expired';
        $this->save();

        return $this;
    }

    /**
     * Mark the invitation as revoked.
     */
    public function markAsRevoked(): self
    {
        $this->status = 'revoked';
        $this->revoked_at = now();
        $this->save();

        return $this;
    }

    /**
     * Get the decoded JWT token data.
     */
    public function getDecodedTokenAttribute(): ?array
    {
        if (!$this->token) {
            return null;
        }
        
        try {
            return (array) JWT::decode($this->token, new Key(config('app.key'), 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}