<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectUser extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_user';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'custom_rate',
        'all_current_projects',
        'all_future_projects',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_rate' => 'decimal:2',
        'all_current_projects' => 'boolean',
        'all_future_projects' => 'boolean',
    ];

    /**
     * Get the project.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the connection name.
     * This ensures the pivot table uses the tenant connection.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        // Force the pivot table to use the tenant connection
        return 'tenant';
    }
}