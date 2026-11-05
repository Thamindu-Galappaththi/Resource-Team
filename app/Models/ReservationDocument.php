<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationDocument extends Model
{
    public const DISK = 'local';

    protected $fillable = [
        'reservation_id',
        'uploaded_by_user_id',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function sizeLabel(): string
    {
        $size = (int) $this->size;

        return $size >= 1048576
            ? number_format($size / 1048576, 1).' MB'
            : max(1, (int) round($size / 1024)).' KB';
    }

    public function downloadUrl(): string
    {
        return route('reservations.documents.show', [$this->reservation_id, $this->id]);
    }
}
