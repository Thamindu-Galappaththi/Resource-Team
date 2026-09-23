<?php

namespace App\Enums;

enum MealType: string
{
    case BREAKFAST = 'breakfast';
    case LUNCH = 'lunch';
    case DINNER = 'dinner';
    case SNACKS = 'snacks';
    case EVENT_CATERING = 'event_catering';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
