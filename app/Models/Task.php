<?php

namespace App\Models;

use App\Traits\HasDemoFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes, HasDemoFlag;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'project_id',
        'user_id',
        'parent_task_id',
        'status',
        'priority',
        'position',
        'board_column',
        'estimated_hours',
        'due_date',
        'completed_at',
        'created_by',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'settings' => 'array',
        'priority' => 'integer',
        'position' => 'integer',
        'estimated_hours' => 'decimal:2',
    ];

    /**
     * Get the project that the task belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that is assigned to the task.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include pending tasks.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include in-progress tasks.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include completed tasks.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to order by priority (higher first).
     */
    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    /**
     * Mark the task as completed.
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Start working on the task (mark as in progress).
     */
    public function markAsInProgress()
    {
        $this->update([
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    /**
     * Reset the task to pending.
     */
    public function markAsPending()
    {
        $this->update([
            'status' => 'pending',
            'completed_at' => null,
        ]);
    }

    /**
     * Get the comments for this task.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the tags for the task.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Get the parent task.
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Get the subtasks.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Get the user who created the task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all assignees for the task.
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')
            ->withTimestamps();
    }

    /**
     * Get time entries for this task.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Check if the task has subtasks.
     */
    public function hasSubtasks(): bool
    {
        return $this->subtasks()->exists();
    }

    /**
     * Get total logged hours for this task.
     */
    public function getTotalLoggedHoursAttribute(): float
    {
        return $this->timeEntries()->sum('duration') / 3600;
    }

    /**
     * Get completion percentage based on logged vs estimated hours.
     */
    public function getCompletionPercentageAttribute(): int
    {
        if (! $this->estimated_hours || $this->estimated_hours == 0) {
            return $this->status === 'completed' ? 100 : 0;
        }

        $percentage = ($this->total_logged_hours / $this->estimated_hours) * 100;

        return min(100, round($percentage));
    }

    /**
     * Scope tasks by board column.
     */
    public function scopeInColumn($query, string $column)
    {
        return $query->where('board_column', $column);
    }

    /**
     * Scope to get root tasks (no parent).
     */
    public function scopeRootTasks($query)
    {
        return $query->whereNull('parent_task_id');
    }

    /**
     * Move task to a different column.
     */
    public function moveToColumn(string $column, ?int $position = null): void
    {
        $this->board_column = $column;

        if ($position !== null) {
            $this->position = $position;
        }

        // Update status based on column
        switch ($column) {
            case 'done':
                $this->status = 'completed';
                $this->completed_at = now();
                break;
            case 'in_progress':
                $this->status = 'in_progress';
                $this->completed_at = null;
                break;
            default:
                $this->status = 'pending';
                $this->completed_at = null;
        }

        $this->save();
    }

    /**
     * Reorder tasks in a column.
     */
    public static function reorderInColumn(int $projectId, string $column, array $taskIds): void
    {
        foreach ($taskIds as $position => $taskId) {
            static::where('id', $taskId)
                ->where('project_id', $projectId)
                ->update(['position' => $position]);
        }
    }
}
