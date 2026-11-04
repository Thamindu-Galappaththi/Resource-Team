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
        'guest_phone_encrypted',
        'check_in_at',
        'check_out_at',
        'number_of_guests',
        'bed_group',
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

    public function bedGroupLabel(): ?string
    {
        return config('hostel.bed_groups.'.$this->bed_group.'.label');
    }

    public function employeeIdList(): string
    {
        return collect($this->employee_ids ?? [])->filter()->implode(', ');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
