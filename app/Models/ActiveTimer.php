<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ActiveTimer extends Model
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
        'paused_at',
        'is_running',
        'is_paused',
        'duration',
        'paused_duration',
        'metadata',
        'sync_token',
        'last_synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_running' => 'boolean',
        'is_paused' => 'boolean',
        'duration' => 'integer',
        'paused_duration' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be appended.
     *
     * @var array<string>
     */
    protected $appends = ['current_duration', 'formatted_duration'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->sync_token)) {
                $model->sync_token = Str::uuid()->toString();
            }
        });
    }

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
     * Get the current duration including running time.
     */
    public function getCurrentDurationAttribute(): int
    {
        if (! $this->is_running || ! $this->started_at) {
            return $this->duration;
        }

        $now = now();
        $elapsed = $now->diffInSeconds($this->started_at);

        return $elapsed - $this->paused_duration;
    }

    /**
     * Get the formatted duration (HH:MM:SS).
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->current_duration;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * Pause the timer.
     */
    public function pause(): self
    {
        if ($this->is_running && ! $this->is_paused) {
            $this->update([
                'is_running' => false,
                'is_paused' => true,
                'paused_at' => now(),
                'duration' => $this->current_duration,
            ]);
        }

        return $this;
    }

    /**
     * Resume the timer.
     */
    public function resume(): self
    {
        if ($this->is_paused && $this->paused_at) {
            $pausedSeconds = now()->diffInSeconds($this->paused_at);
            
            $this->update([
                'is_running' => true,
                'is_paused' => false,
                'paused_at' => null,
                'paused_duration' => $this->paused_duration + $pausedSeconds,
            ]);
        }

        return $this;
    }

    /**
     * Stop the timer and create a time entry.
     */
    public function stop(): \App\Models\TimeEntry
    {
        $duration = $this->current_duration;

        // Create time entry
        $timeEntry = \App\Models\TimeEntry::create([
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'description' => $this->description,
            'started_at' => $this->started_at,
            'ended_at' => now(),
            'duration' => $duration,
            'is_manual' => false,
            'created_via' => 'timer',
            'is_billable' => true,
        ]);

        // Delete the active timer
        $this->delete();

        return $timeEntry;
    }

    /**
     * Sync timer state from client.
     */
    public function syncFromClient(array $data): self
    {
        $updateData = [];

        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        if (isset($data['project_id'])) {
            $updateData['project_id'] = $data['project_id'];
        }

        if (isset($data['task_id'])) {
            $updateData['task_id'] = $data['task_id'];
        }

        if (isset($data['is_running'])) {
            $updateData['is_running'] = $data['is_running'];
        }

        if (isset($data['is_paused'])) {
            $updateData['is_paused'] = $data['is_paused'];
        }

        if (isset($data['duration'])) {
            $updateData['duration'] = $data['duration'];
        }

        if (isset($data['paused_duration'])) {
            $updateData['paused_duration'] = $data['paused_duration'];
        }

        if (isset($data['metadata'])) {
            $updateData['metadata'] = array_merge($this->metadata ?? [], $data['metadata']);
        }

        $updateData['last_synced_at'] = now();

        $this->update($updateData);

        return $this;
    }

    /**
     * Convert to array for client.
     */
    public function toClientArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'description' => $this->description,
            'started_at' => $this->started_at?->toIso8601String(),
            'paused_at' => $this->paused_at?->toIso8601String(),
            'is_running' => (bool) $this->is_running,
            'is_paused' => (bool) $this->is_paused,
            'duration' => $this->duration,
            'paused_duration' => $this->paused_duration,
            'current_duration' => $this->current_duration,
            'formatted_duration' => $this->formatted_duration,
            'sync_token' => $this->sync_token,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'project' => $this->project,
            'task' => $this->task,
        ];
    }

    /**
     * Scope a query to only include timers for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include running timers.
     */
    public function scopeRunning($query)
    {
        return $query->where('is_running', true);
    }

    /**
     * Check if timer has been idle for too long.
     */
    public function isIdle(int $thresholdMinutes = 10): bool
    {
        if (! $this->is_running || ! $this->last_synced_at) {
            return false;
        }

        return $this->last_synced_at->diffInMinutes(now()) >= $thresholdMinutes;
    }
}