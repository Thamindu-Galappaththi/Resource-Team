<?php

namespace App\Models;

use App\Models\LecturerProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LectureSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'lecturer_id',
        'session_date',
        'hours',
        'qualification',
        'department',
        'location',
        'status',
    ];

    protected $casts = [
        'session_date' => 'date',
        'hours' => 'decimal:2',
    ];

    /**
     * Lecturer who delivered this session.
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(LecturerProfile::class, 'lecturer_id');
    }

    /**
     * Payable generated for this lecture session.
     */
    public function payable(): HasOne
    {
        return $this->hasOne(LecturerPayable::class);
    }
}