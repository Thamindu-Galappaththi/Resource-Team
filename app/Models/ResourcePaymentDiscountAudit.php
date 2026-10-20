<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourcePaymentDiscountAudit extends Model
{
    protected $fillable = ['discount_rule_id', 'actor_id', 'action', 'old_values', 'new_values', 'ip_address'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ResourcePaymentDiscountRule::class, 'discount_rule_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
