<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeCategory extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'color',
        'description',
        'is_billable_default',
    ];

    /**
     * Los atributos que deben convertirse.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_billable_default' => 'boolean',
    ];

    /**
     * Get the time entries for the category.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'category_id');
    }
}
