<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasCrossDatabaseRelationships
{
    /**
     * Create a cross-database many-to-many relationship.
     */
    public function crossDatabaseBelongsToMany(
        string $related,
        string $table,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $parentKey = null,
        string $relatedKey = null,
        string $relation = null
    ): BelongsToMany {
        // Get the related model instance
        $instance = $this->newRelatedInstance($related);

        // Get the parent and related keys
        $parentKey = $parentKey ?: $this->getKeyName();
        $relatedKey = $relatedKey ?: $instance->getKeyName();

        // If no relation name was given, use the calling method name
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // Create the relationship but with a custom query
        $query = $instance->newQuery();
        
        // Join with the pivot table in the current connection (tenant)
        $query->join($table, function ($join) use ($table, $instance, $relatedPivotKey) {
            $join->on($instance->getTable() . '.' . $instance->getKeyName(), '=', $table . '.' . $relatedPivotKey);
        });

        // Add where clause for the current model
        $query->where($table . '.' . $foreignPivotKey, '=', $this->getKey());

        // Create the BelongsToMany relationship
        return new BelongsToMany(
            $query,
            $this,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $relation
        );
    }
}