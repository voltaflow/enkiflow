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
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    use HasFactory, SoftDeletes, HasDemoFlag;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'user_id',
        'client_id',
        'status',
        'budget',
        'due_date',
        'completed_at',
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
        'budget' => 'decimal:2',
    ];

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client that owns the project.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to only include active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include completed projects.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Mark the project as completed.
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the project as active.
     */
    public function markAsActive()
    {
        $this->update([
            'status' => 'active',
            'completed_at' => null,
        ]);
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the tags for the project.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Get the time entries for the project.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Get all users assigned to this project.
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->using(ProjectUser::class)
            ->withPivot([
                'role',
                'custom_rate',
                'all_current_projects',
                'all_future_projects'
            ])
            ->withTimestamps();
    }

    /**
     * Get users with a specific role on this project.
     */
    public function getUsersByRole(string $role): \Illuminate\Database\Eloquent\Collection
    {
        return $this->assignedUsers()->wherePivot('role', $role)->get();
    }

    /**
     * Get all managers for this project.
     */
    public function managers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getUsersByRole('manager');
    }

    /**
     * Get all members for this project.
     */
    public function members(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getUsersByRole('member');
    }

    /**
     * Get all viewers for this project.
     */
    public function viewers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getUsersByRole('viewer');
    }

    /**
     * Check if a user is assigned to this project.
     */
    public function hasUser(User $user): bool
    {
        return DB::connection('tenant')
            ->table('project_user')
            ->where('project_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Check if a user has a specific role on this project.
     */
    public function userHasRole(User $user, string $role): bool
    {
        return DB::connection('tenant')
            ->table('project_user')
            ->where('project_id', $this->id)
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->exists();
    }
}
