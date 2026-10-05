<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RateCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'currency',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Rules belonging to this rate card.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(RateRule::class);
    }

    /**
     * Lecturer payables calculated using this rate card.
     */
    public function lecturerPayables(): HasMany
    {
        return $this->hasMany(LecturerPayable::class);
    }
}