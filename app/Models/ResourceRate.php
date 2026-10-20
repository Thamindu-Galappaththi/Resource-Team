<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceRate extends Model
{
    use HasFactory;

    protected $fillable = ['resource_type_id', 'resource_id', 'base_fee', 'base_duration_hours', 'extra_hour_rate', 'tax_percent', 'effective_from', 'effective_to', 'is_active', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['base_fee' => 'decimal:2', 'base_duration_hours' => 'decimal:2', 'extra_hour_rate' => 'decimal:2', 'tax_percent' => 'decimal:3', 'effective_from' => 'date', 'effective_to' => 'date', 'is_active' => 'boolean'];
    }

    public function resourceType(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ResourceRateAudit::class);
    }
}
