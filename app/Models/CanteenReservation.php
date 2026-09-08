<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanteenReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_code',
        'canteen_id',
        'reservation_name',
        'reservation_date',
        'reservation_time',
        'meal_type',
        'number_of_orders',
        'order_details',
        'special_requirements',
        'status',
        'created_by',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'number_of_orders' => 'integer',
    ];

    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
