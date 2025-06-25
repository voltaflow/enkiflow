<?php

namespace App\Models;

use App\Traits\HasDemoFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskState extends Model
{
    use HasFactory, HasDemoFlag;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'color',
        'position',
        'is_default',
        'is_completed',
        'is_demo',
    ];

    /**
     * Los atributos que deben convertirse.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_completed' => 'boolean',
        'is_demo' => 'boolean',
    ];

    /**
     * Obtener las tareas asociadas a este estado.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope para ordenar por posición.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Scope para obtener solo estados predeterminados.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope para obtener solo estados completados.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }
}