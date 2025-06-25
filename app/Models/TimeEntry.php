<?php

namespace App\Models;

use App\Traits\HasDemoFlag;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes, HasDemoFlag;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'weekly_timesheet_id',
        'task_id',
        'project_id',
        'category_id',
        'started_at',
        'ended_at',
        'duration',
        'description',
        'is_billable',
        'is_manual',
        'created_via',
        'created_from',
        'parent_entry_id',
        'locked',
        'locked_at',
        'locked_by',
        'tags',
        'metadata',
        'hourly_rate',
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
        'locked' => 'boolean',
        'locked_at' => 'datetime',
        'hourly_rate' => 'decimal:2',
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
     * Get the weekly timesheet this entry belongs to.
     */
    public function weeklyTimesheet(): BelongsTo
    {
        return $this->belongsTo(WeeklyTimesheet::class);
    }

    /**
     * Get the parent entry if this is a recurring entry.
     */
    public function parentEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class, 'parent_entry_id');
    }

    /**
     * Get child entries if this is a parent recurring entry.
     */
    public function childEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'parent_entry_id');
    }

    /**
     * Get the user who locked this entry.
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
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
            // Calculate duration as the difference from start to end
            $this->duration = $this->started_at->diffInSeconds($this->ended_at);
            
            // Ensure duration is never negative
            if ($this->duration < 0) {
                \Log::error('TimeEntry::calculateDuration - Negative duration detected', [
                    'id' => $this->id,
                    'started_at' => $this->started_at,
                    'ended_at' => $this->ended_at,
                    'duration' => $this->duration,
                ]);
                $this->duration = abs($this->duration);
            }
        }
    }
    
    /**
     * Set the duration attribute - ensure it's never negative.
     */
    public function setDurationAttribute($value)
    {
        if ($value < 0) {
            \Log::warning('TimeEntry::setDurationAttribute - Attempting to set negative duration', [
                'id' => $this->id,
                'value' => $value,
                'started_at' => $this->started_at,
                'ended_at' => $this->ended_at,
            ]);
            $this->attributes['duration'] = abs($value);
        } else {
            $this->attributes['duration'] = $value;
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

    /**
     * Get the application sessions linked to this time entry.
     */
    public function applicationSessions(): HasMany
    {
        return $this->hasMany(ApplicationSession::class, 'linked_time_entry_id');
    }

    /**
     * Get the activity logs for this time entry.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Scope a query to only include time entries for a specific date range.
     */
    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include time entries for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('started_at', today());
    }

    /**
     * Scope a query to only include unlocked entries.
     */
    public function scopeUnlocked($query)
    {
        return $query->where('locked', false);
    }

    /**
     * Scope a query to only include entries for a specific week.
     */
    public function scopeForWeek($query, $weekStart)
    {
        $start = Carbon::parse($weekStart)->startOfWeek();
        $end = $start->copy()->endOfWeek();

        return $query->whereBetween('started_at', [$start, $end]);
    }

    /**
     * Check if the entry is editable.
     */
    public function isEditable(): bool
    {
        return ! $this->locked && (! $this->weeklyTimesheet || $this->weeklyTimesheet->is_editable);
    }
}
