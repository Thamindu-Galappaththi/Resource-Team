<?php

namespace App\Enums;

enum CanteenReservationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
