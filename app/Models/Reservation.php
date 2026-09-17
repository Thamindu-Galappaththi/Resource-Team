<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use HasFactory;

    public const BLOCKING_STATUSES = ['pending', 'approved'];

    protected $fillable = [
        'user_id',
        'resource_id',
        'location_id',
        'reservation_date',
        'start_time',
        'end_time',
        'special_requirements',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function addOns(): HasMany
    {
        return $this->hasMany(ReservationAddOn::class);
    }
}