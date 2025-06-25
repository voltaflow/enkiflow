<?php

namespace App\Traits;

trait HasDemoFlag
{
    /**
     * Inicializar el trait HasDemoFlag.
     */
    public function initializeHasDemoFlag()
    {
        $this->fillable[] = 'is_demo';
        $this->casts['is_demo'] = 'boolean';
    }

    /**
     * Scope para filtrar solo registros de demostración.
     */
    public function scopeOnlyDemo($query)
    {
        return $query->where('is_demo', true);
    }

    /**
     * Scope para excluir registros de demostración.
     */
    public function scopeWithoutDemo($query)
    {
        return $query->where('is_demo', false);
    }

    /**
     * Marcar el registro como dato de demostración.
     */
    public function markAsDemo()
    {
        $this->is_demo = true;
        $this->save();
        
        return $this;
    }

    /**
     * Verificar si el registro es un dato de demostración.
     */
    public function isDemo(): bool
    {
        return (bool) $this->is_demo;
    }

    /**
     * Modificar el nombre para mostrar el indicador [DEMO].
     */
    public function getDisplayNameAttribute()
    {
        $nameField = $this->getNameField();
        
        if ($this->isDemo() && isset($this->attributes[$nameField])) {
            return '[DEMO] ' . $this->attributes[$nameField];
        }
        
        return $this->attributes[$nameField] ?? null;
    }

    /**
     * Obtener el campo que contiene el nombre del modelo.
     */
    protected function getNameField(): string
    {
        return property_exists($this, 'nameField') ? $this->nameField : 'name';
    }
}