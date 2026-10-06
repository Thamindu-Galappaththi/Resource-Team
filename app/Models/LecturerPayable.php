<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturerPayable extends Model
{
    use HasFactory;

    protected $fillable = [
        'lecture_session_id',
        'lecturer_id',
        'rate_card_id',
        'hours',
        'hourly_rate_minor',
        'net_amount_minor',
        'currency',
        'rate_snapshot',
        'status',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'hourly_rate_minor' => 'integer',
        'net_amount_minor' => 'integer',
        'rate_snapshot' => 'array',
    ];

    /**
     * Lecture session associated with this payable.
     */
    public function lectureSession(): BelongsTo
    {
        return $this->belongsTo(LectureSession::class);
    }

    /**
     * Lecturer who is being paid.
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /**
     * Rate card used to calculate this payable.
     */
    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }
}