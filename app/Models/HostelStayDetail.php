<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelStayDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'guest_name',
        'guest_identity_type',
        'guest_identity_encrypted',
        'guest_phone_encrypted',
        'check_in_at',
        'check_out_at',
        'room_type_id',
        'room_category_id',
        'number_of_guests',
        'special_requirements',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'room_type_id');
    }

    public function roomCategory(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'room_category_id');
    }
}
