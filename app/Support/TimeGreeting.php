<?php

namespace App\Support;

use Carbon\CarbonInterface;

class TimeGreeting
{
    public static function phrase(?CarbonInterface $at = null): string
    {
        $timezone = config('reservations.display_timezone', 'Asia/Colombo');
        $hour = ($at ?? now($timezone))->timezone($timezone)->hour;

        return match (true) {
            $hour >= 5 && $hour < 12 => 'Good morning',
            $hour >= 12 && $hour < 16 => 'Good afternoon',
            $hour >= 16 && $hour < 21 => 'Good evening',
            default => 'Good night',
        };
    }
}
