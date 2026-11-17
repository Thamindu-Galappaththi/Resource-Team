<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_card_id',
        'qualification',
        'department',
        'location',
        'user_type',
        'hourly_rate_minor',
        'is_active',
        'conditions',
    ];

    protected $casts = [
        'hourly_rate_minor' => 'integer',
        'is_active' => 'boolean',
        'conditions' => 'array',
    ];

    /**
     * Rate card this rule belongs to.
     */
    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }
}