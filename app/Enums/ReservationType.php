<?php

namespace App\Enums;

enum ReservationType: string
{
    case STANDARD = 'standard';
    case EVENT = 'event';
    case HOSTEL = 'hostel';
    case CANTEEN = 'canteen';
    case LECTURE = 'lecture';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
