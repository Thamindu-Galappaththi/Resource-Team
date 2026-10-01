<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingLockDay extends Model
{
    protected $fillable = [
        'resource_id',
        'lock_date',
    ];

    protected function casts(): array
    {
        return [
            'lock_date' => 'date',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
