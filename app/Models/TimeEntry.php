<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'task_id',
        'project_id',
        'category_id',
        'started_at',
        'ended_at',
        'duration',
        'description',
        'is_billable',
        'is_manual',
        'tags',
        'metadata',
    ];

    /**
     * Los atributos que deben convertirse.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration' => 'integer',
        'is_billable' => 'boolean',
        'is_manual' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the time entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the task that the time entry belongs to.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the project that the time entry belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the category that the time entry belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TimeCategory::class);
    }

    /**
     * Determine if the time entry is currently running.
     */
    public function isRunning(): bool
    {
        return $this->started_at && ! $this->ended_at;
    }

    /**
     * Stop the time entry.
     */
    public function stop(): void
    {
        $this->ended_at = now();
        $this->calculateDuration();
        $this->save();
    }

    /**
     * Calculate the duration of the time entry.
     */
    public function calculateDuration(): void
    {
        if ($this->started_at && $this->ended_at) {
            $this->duration = $this->ended_at->diffInSeconds($this->started_at);
        }
    }

    /**
     * Scope a query to only include running time entries.
     */
    public function scopeRunning($query)
    {
        return $query->whereNotNull('started_at')
            ->whereNull('ended_at');
    }

    /**
     * Scope a query to only include billable time entries.
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Scope a query to only include time entries for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get formatted duration in hours and minutes.
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);

        return $hours.'h '.$minutes.'m';
    }
}
