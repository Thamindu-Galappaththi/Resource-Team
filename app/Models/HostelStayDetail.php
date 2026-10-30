<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelStayDetail extends Model
{
    use HasFactory;

    public const GUEST_TITLES = ['Mr.', 'Mrs.', 'Ms.', 'Miss', 'Dr.', 'Prof.', 'Rev.'];

    protected $fillable = [
        'reservation_id',
        'guest_title',
        'guest_name',
        'employee_ids',
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
            'employee_ids' => 'array',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function displayGuestName(): string
    {
        return trim(($this->guest_title ? $this->guest_title.' ' : '').$this->guest_name);
    }

    public function employeeIdList(): string
    {
        return collect($this->employee_ids ?? [])->filter()->implode(', ');
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
