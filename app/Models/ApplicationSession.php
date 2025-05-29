<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'app_name',
        'window_title',
        'start_time',
        'end_time',
        'duration_seconds',
        'linked_time_entry_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    /**
     * Get the user that owns the application session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the linked time entry.
     */
    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class, 'linked_time_entry_id');
    }

    /**
     * End the application session.
     */
    public function end(): void
    {
        if (!$this->end_time) {
            $this->end_time = now();
            $this->duration_seconds = $this->end_time->diffInSeconds($this->start_time);
            $this->save();
        }
    }

    /**
     * Check if the session is active.
     */
    public function isActive(): bool
    {
        return $this->end_time === null;
    }

    /**
     * Get the app category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AppCategory::class, 'app_name', 'app_name');
    }

    /**
     * Scope a query to only include active sessions.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('end_time');
    }

    /**
     * Scope a query to only include sessions for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include sessions for a specific date range.
     */
    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include sessions for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start_time', today());
    }

    /**
     * Get the productivity level based on app category.
     */
    public function getProductivityLevelAttribute(): string
    {
        if ($this->category) {
            return $this->category->productivity_level;
        }

        return 'neutral';
    }
}