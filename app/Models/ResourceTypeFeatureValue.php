<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceTypeFeatureValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_type_id',
        'category_feature_id',
        'value',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'resource_type_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(CategoryFeature::class, 'category_feature_id');
    }
}
