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

    public function label(): string
    {
        return match ($this) {
            self::REQUESTED => 'Requested',
            self::HELD => 'Held',
            self::CONFIRMED => 'Confirmed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
