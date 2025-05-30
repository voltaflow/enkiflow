<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'description',
        'started_at',
        'is_running',
        'current_duration',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'is_running' => 'boolean',
        'current_duration' => 'integer',
    ];

    /**
     * Get the user that owns the timer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project that the timer is tracking.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the task that the timer is tracking.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Stop the timer and create a time entry.
     */
    public function stop(): TimeEntry
    {
        $endTime = now();
        $duration = $endTime->diffInSeconds($this->started_at) + $this->current_duration;

        // Create time entry
        $timeEntry = TimeEntry::create([
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'description' => $this->description,
            'started_at' => $this->started_at,
            'ended_at' => $endTime,
            'duration' => $duration,
            'is_manual' => false,
            'created_via' => 'timer',
        ]);

        // Delete the timer
        $this->delete();

        return $timeEntry;
    }

    /**
     * Pause the timer.
     */
    public function pause(): void
    {
        if ($this->is_running) {
            $currentTime = now();
            $this->current_duration += $currentTime->diffInSeconds($this->started_at);
            $this->is_running = false;
            $this->save();
        }
    }

    /**
     * Resume the timer.
     */
    public function resume(): void
    {
        if (! $this->is_running) {
            $this->started_at = now();
            $this->is_running = true;
            $this->save();
        }
    }

    /**
     * Get the total duration in seconds (including paused time).
     */
    public function getTotalDurationAttribute(): int
    {
        if ($this->is_running) {
            return $this->current_duration + now()->diffInSeconds($this->started_at);
        }

        return $this->current_duration;
    }

    /**
     * Scope a query to only include running timers.
     */
    public function scopeRunning($query)
    {
        return $query->where('is_running', true);
    }

    /**
     * Scope a query to only include timers for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
