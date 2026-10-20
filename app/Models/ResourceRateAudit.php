<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceRateAudit extends Model
{
    protected $fillable = ['resource_rate_id', 'actor_id', 'action', 'old_values', 'new_values', 'ip_address'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(ResourceRate::class, 'resource_rate_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
