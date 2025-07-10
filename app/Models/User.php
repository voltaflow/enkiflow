<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasFactory, Notifiable;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get spaces owned by the user.
     */
    public function ownedSpaces(): HasMany
    {
        return $this->hasMany(Space::class, 'owner_id');
    }

    /**
     * Get all spaces that the user belongs to.
     */
    public function spaces(): BelongsToMany
    {
        return $this->belongsToMany(Space::class, 'space_users', 'user_id', 'tenant_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Check if the user owns the given space.
     */
    public function ownsSpace(Space $space): bool
    {
        return $this->id === $space->owner_id;
    }

    /**
     * Get all projects assigned to the user in the current tenant.
     */
    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
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
     * Check if the user has access to all current projects.
     */
    public function hasAccessToAllCurrentProjects(): bool
    {
        return $this->assignedProjects()
            ->wherePivot('all_current_projects', true)
            ->exists();
    }

    /**
     * Check if the user has access to all future projects.
     */
    public function hasAccessToAllFutureProjects(): bool
    {
        return $this->assignedProjects()
            ->wherePivot('all_future_projects', true)
            ->exists();
    }

    /**
     * Get the accessible projects for the user (including all current/future access).
     */
    public function accessibleProjects()
    {
        $query = Project::query();

        // Check if user has access to all current projects
        if ($this->hasAccessToAllCurrentProjects()) {
            return $query;
        }

        // Otherwise, return only assigned projects
        return $query->whereIn('id', $this->assignedProjects()->pluck('projects.id'));
    }

    /**
     * Get the user's role for a specific project.
     */
    public function getRoleForProject(Project $project): ?string
    {
        $assignment = $this->assignedProjects()->find($project->id);
        return $assignment ? $assignment->pivot->role : null;
    }

    /**
     * Get the user's custom rate for a specific project.
     */
    public function getCustomRateForProject(Project $project): ?float
    {
        $assignment = $this->assignedProjects()->find($project->id);
        return $assignment ? $assignment->pivot->custom_rate : null;
    }
}
