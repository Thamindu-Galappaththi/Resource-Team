<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceCategory extends Model
{
    use HasFactory;

    /**
     * Mass-assignable fields.
     * Only "name" — features are created/saved separately (see
     * ResourceCategoryController@store), not passed in directly here.
     */
    protected $fillable = [
        'name',
    ];

    /**
     * A category has many "Additional Features" rows (Tab 1's
     * repeater). One-to-many: one category -> many features.
     */
    public function features(): HasMany
    {
        return $this->hasMany(CategoryFeature::class);
    }

    /**
     * A category has many resource types (Tab 2 rows created under it).
     * One-to-many: one category -> many types.
     */
    public function types(): HasMany
    {
        return $this->hasMany(ResourceType::class);
    }
}