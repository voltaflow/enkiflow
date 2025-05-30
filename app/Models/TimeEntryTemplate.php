<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntryTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'project_id',
        'task_id',
        'category_id',
        'default_hours',
        'is_billable',
        'is_active',
        'usage_count',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'default_hours' => 'decimal:2',
        'is_billable' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project associated with the template.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the task associated with the template.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the category associated with the template.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TimeCategory::class, 'category_id');
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include templates for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to order by usage frequency.
     */
    public function scopeByUsageFrequency($query)
    {
        return $query->orderBy('usage_count', 'desc')
            ->orderBy('last_used_at', 'desc');
    }

    /**
     * Increment usage count and update last used timestamp.
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Create a time entry from this template.
     */
    public function createTimeEntry(array $overrides = []): TimeEntry
    {
        $data = array_merge([
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'is_billable' => $this->is_billable,
            'started_at' => now(),
            'ended_at' => now()->addHours($this->default_hours),
            'duration' => $this->default_hours * 3600, // Convert hours to seconds
            'created_via' => 'template',
        ], $overrides);

        $this->recordUsage();

        return TimeEntry::create($data);
    }

    /**
     * Get suggested templates based on current context.
     */
    public static function getSuggestions($userId, $projectId = null, $limit = 5)
    {
        $query = static::forUser($userId)
            ->active()
            ->with(['project', 'task', 'category']);

        if ($projectId) {
            // Prioritize templates for the current project
            $query->orderByRaw('CASE WHEN project_id = ? THEN 0 ELSE 1 END', [$projectId]);
        }

        return $query->byUsageFrequency()
            ->limit($limit)
            ->get();
    }
}
