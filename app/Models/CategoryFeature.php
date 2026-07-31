<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryFeature extends Model
{
    use HasFactory;

    /**
     * Mass-assignable fields — these mirror the JS payload sent per
     * feature row: { name, enabled, options }.
     */
    protected $fillable = [
        'resource_category_id',
        'name',
        'enabled',
        'options',
    ];

    /**
     * Cast "enabled" to a real PHP boolean instead of a 0/1 string,
     * so front-end JSON responses come back as true/false.
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Inverse of ResourceCategory::features(). Each feature row
     * belongs to exactly one category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'resource_category_id');
    }
}