<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourcePaymentDiscountRule extends Model
{
    protected $fillable = ['customer_category', 'discount_percent', 'is_active', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['discount_percent' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ResourcePaymentDiscountAudit::class, 'discount_rule_id');
    }
}
