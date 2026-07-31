<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceType extends Model
{
    use HasFactory;

    /**
     * Mass-assignable fields — mirrors the Tab 2 form fields:
     * category_id (mapped to resource_category_id), resource_type
     * (mapped to "name"), and description.
     */
    protected $fillable = [
        'resource_category_id',
        'name',
        'description',
    ];

    /**
     * Inverse of ResourceCategory::types(). Each type belongs to
     * exactly one category (the dropdown on Tab 2).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'resource_category_id');
    }

    /**
     * A type has many resources created under it (Tab 3 rows).
     * One-to-many: one type -> many resources.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function featureValues(): HasMany
    {
        return $this->hasMany(ResourceTypeFeatureValue::class);
    }
}
