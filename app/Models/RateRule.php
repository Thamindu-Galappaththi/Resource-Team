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
        'conditions',
    ];

    protected $casts = [
        'hourly_rate_minor' => 'integer',
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