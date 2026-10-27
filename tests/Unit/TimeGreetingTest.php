<?php

namespace Tests\Unit;

use App\Support\TimeGreeting;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TimeGreetingTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function greetingHours(): array
    {
        return [
            'early morning stays night' => ['2026-10-20 04:59:00', 'Good night'],
            'five am is morning' => ['2026-10-20 05:00:00', 'Good morning'],
            'eleven is morning' => ['2026-10-20 11:59:00', 'Good morning'],
            'noon is afternoon' => ['2026-10-20 12:00:00', 'Good afternoon'],
            'three pm is afternoon' => ['2026-10-20 15:59:00', 'Good afternoon'],
            'four pm is evening' => ['2026-10-20 16:00:00', 'Good evening'],
            'eight pm is evening' => ['2026-10-20 20:59:00', 'Good evening'],
            'nine pm is night' => ['2026-10-20 21:00:00', 'Good night'],
            'midnight is night' => ['2026-10-20 00:00:00', 'Good night'],
        ];
    }

    #[DataProvider('greetingHours')]
    public function test_phrase_follows_colombo_time_of_day(string $colomboTime, string $expected): void
    {
        Carbon::setTestNow(Carbon::parse($colomboTime, 'Asia/Colombo'));

        $this->assertSame($expected, TimeGreeting::phrase());
    }

    public function test_utc_clock_does_not_shift_the_sri_lanka_greeting(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-20 10:44:00', 'UTC'));

        $this->assertSame('Good evening', TimeGreeting::phrase());
    }
}
