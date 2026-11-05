<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourcePaymentInvoice extends Model
{
    protected $fillable = [
        'reservation_id',
        'invoice_number',
        'status',
        'extra_hours',
        'fee_breakdown',
        'issued_at',
        'paid_at',
        'payment_date',
        'paid_by',
        'payment_method',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'extra_hours' => 'decimal:2',
            'fee_breakdown' => 'array',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_date' => 'date',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
