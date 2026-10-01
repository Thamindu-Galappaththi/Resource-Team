<?php

namespace App\Enums;

enum ReservationItemStatus: string
{
    case REQUESTED = 'requested';
    case HELD = 'held';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
