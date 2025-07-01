<?php

namespace App\Models;

use App\Traits\HasDemoFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory, SoftDeletes, HasDemoFlag;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'name',
        'email',
        'phone',
        'address',
        'notes',
        'timezone',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'timezone' => 'America/Mexico_City',
    ];

    /**
     * Get the projects for the client.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the user that owns the client.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the invoices for the client (future implementation).
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the time entries through projects.
     */
    public function timeEntries()
    {
        return $this->hasManyThrough(TimeEntry::class, Project::class);
    }

    /**
     * Scope a query to only include active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive clients.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Get the display name attribute.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name . ($this->is_active ? '' : ' – Inactivo');
    }

    /**
     * Get the total hours tracked for this client.
     */
    public function getTotalHoursAttribute(): float
    {
        return $this->timeEntries()->sum('duration') / 3600;
    }

    /**
     * Get the total billable hours for this client.
     */
    public function getBillableHoursAttribute(): float
    {
        return $this->timeEntries()->where('is_billable', true)->sum('duration') / 3600;
    }

    /**
     * Get the count of active projects.
     */
    public function getActiveProjectsCountAttribute(): int
    {
        return $this->projects()->where('status', 'active')->count();
    }

    /**
     * Generate a unique slug for the client.
     */
    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = static::withTrashed()->where('slug', 'LIKE', "{$slug}%")->count();
        
        return $count ? "{$slug}-" . ($count + 1) : $slug;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Client $client) {
            if (empty($client->slug)) {
                $client->slug = static::generateSlug($client->name);
            }
        });
    }
}