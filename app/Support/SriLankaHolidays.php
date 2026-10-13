<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Sri Lankan public, bank, mercantile, and Poya holidays.
 *
 * 2025–2026 use gazetted CBSL dates. Later years are generated (full moon in
 * Asia/Colombo, plus fixed national / religious days) until a gazette is published.
 */
class SriLankaHolidays
{
    /**
     * @return list<array{date: string, name: string, types: list<string>}>
     */
    public static function all(): array
    {
        $official = self::gazetted();
        $gazettedYears = [];
        foreach ($official as $holiday) {
            $gazettedYears[(int) substr($holiday['date'], 0, 4)] = true;
        }

        $holidays = $official;
        $endYear = max((int) CarbonImmutable::now('Asia/Colombo')->year + 8, 2035);
        for ($year = 2024; $year <= $endYear; $year++) {
            if (isset($gazettedYears[$year])) {
                continue;
            }
            $holidays = array_merge($holidays, self::generateYear($year));
        }

        usort($holidays, fn ($left, $right) => $left['date'] <=> $right['date']);

        return $holidays;
    }

    /**
     * FullCalendar events: a day tint plus a labelled all-day chip.
     *
     * @return list<array<string, mixed>>
     */
    public static function calendarEvents(): array
    {
        $grouped = [];
        foreach (self::all() as $holiday) {
            $grouped[$holiday['date']][] = $holiday;
        }

        $events = [];
        foreach ($grouped as $date => $holidays) {
            $names = array_values(array_unique(array_column($holidays, 'name')));
            $types = array_values(array_unique(array_merge(...array_column($holidays, 'types'))));
            $kind = self::kind($types);
            $title = implode(' / ', $names);
            $typeLabel = self::typeLabel($types);

            $events[] = [
                'id' => 'holiday-bg-'.$date,
                'title' => $title,
                'start' => $date,
                'allDay' => true,
                'display' => 'background',
                'classNames' => ['rc-holiday-bg', 'rc-holiday-bg-'.$kind],
                'extendedProps' => [
                    'isHoliday' => true,
                    'holidayKind' => $kind,
                    'holidayTypes' => $types,
                    'holidayTypeLabel' => $typeLabel,
                    'holidayImage' => self::imageFor($title),
                ],
            ];
            $events[] = [
                'id' => 'holiday-'.$date,
                'title' => $title,
                'start' => $date,
                'allDay' => true,
                'editable' => false,
                'classNames' => ['rc-holiday-event', 'rc-holiday-'.$kind],
                'extendedProps' => [
                    'isHoliday' => true,
                    'holidayKind' => $kind,
                    'holidayTypes' => $types,
                    'holidayTypeLabel' => $typeLabel,
                    'holidayImage' => self::imageFor($title),
                ],
            ];
        }

        return $events;
    }

    /**
     * Holidays keyed by Y-m-d for a given month.
     *
     * @return array<string, array{date: string, title: string, kind: string, types: list<string>, typeLabel: string, image: string, names: list<string>}>
     */
    public static function forMonth(int $year, int $month): array
    {
        $prefix = sprintf('%04d-%02d-', $year, $month);
        $grouped = [];

        foreach (self::all() as $holiday) {
            if (! str_starts_with($holiday['date'], $prefix)) {
                continue;
            }
            $grouped[$holiday['date']][] = $holiday;
        }

        $days = [];
        foreach ($grouped as $date => $holidays) {
            $names = array_values(array_unique(array_column($holidays, 'name')));
            $types = array_values(array_unique(array_merge(...array_column($holidays, 'types'))));
            $title = implode(' / ', $names);
            $days[$date] = [
                'date' => $date,
                'title' => $title,
                'kind' => self::kind($types),
                'types' => $types,
                'typeLabel' => self::typeLabel($types),
                'image' => self::imageFor($title),
                'names' => $names,
            ];
        }

        return $days;
    }

    /**
     * @param  list<string>  $types
     */
    public static function kind(array $types): string
    {
        return match (true) {
            in_array('poya', $types, true) => 'poya',
            in_array('mercantile', $types, true) => 'mercantile',
            in_array('bank', $types, true) && ! in_array('public', $types, true) => 'bank',
            default => 'public',
        };
    }

    /**
     * @param  list<string>  $types
     */
    public static function typeLabel(array $types): string
    {
        $labels = [
            'public' => 'Government / public',
            'mercantile' => 'Mercantile',
            'poya' => 'Poya',
            'bank' => 'Bank',
        ];

        return implode(' · ', array_values(array_filter(array_map(fn ($type) => $labels[$type] ?? null, $types))));
    }

    private static function imageFor(string $title): string
    {
        $name = strtolower($title);

        return match (true) {
            str_contains($name, 'vesak') => 'vesak.svg',
            str_contains($name, 'poya') => 'poya.svg',
            str_contains($name, 'independence') => 'independence.svg',
            str_contains($name, 'christmas') => 'christmas.svg',
            str_contains($name, 'new year') => 'newyear.svg',
            str_contains($name, 'pongal') => 'pongal.svg',
            str_contains($name, 'sivarathri') => 'sivarathri.svg',
            str_contains($name, 'deepavali') => 'deepavali.svg',
            str_contains($name, 'good friday') => 'goodfriday.svg',
            str_contains($name, 'may day') => 'mayday.svg',
            str_contains($name, 'id-ul') || str_contains($name, 'milad') || str_contains($name, 'ramazan') || str_contains($name, 'hadji') => 'islamic.svg',
            default => 'public.svg',
        };
    }

    /**
     * @return list<array{date: string, name: string, types: list<string>}>
     */
    private static function gazetted(): array
    {
        return [
            ['date' => '2025-01-13', 'name' => 'Duruthu Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-01-14', 'name' => 'Tamil Thai Pongal Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2025-02-04', 'name' => 'Independence Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2025-02-12', 'name' => 'Navam Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-02-26', 'name' => 'Maha Sivarathri Day', 'types' => ['public', 'bank']],
            ['date' => '2025-03-13', 'name' => 'Medin Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-03-31', 'name' => 'Id-Ul-Fitr (Ramazan Festival Day)', 'types' => ['public', 'bank']],
            ['date' => '2025-04-12', 'name' => 'Bak Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-04-13', 'name' => 'Day prior to Sinhala & Tamil New Year Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2025-04-14', 'name' => 'Sinhala & Tamil New Year Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2025-04-15', 'name' => 'Special Bank Holiday', 'types' => ['bank']],
            ['date' => '2025-04-18', 'name' => 'Good Friday', 'types' => ['public', 'bank']],
            ['date' => '2025-05-01', 'name' => 'May Day (International Workers’ Day)', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2025-05-12', 'name' => 'Vesak Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-05-13', 'name' => 'Day following Vesak Full Moon Poya Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2025-06-07', 'name' => 'Id-Ul-Alha (Hadji Festival Day)', 'types' => ['public', 'bank']],
            ['date' => '2025-06-10', 'name' => 'Poson Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-07-10', 'name' => 'Esala Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-08-08', 'name' => 'Nikini Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-09-05', 'name' => 'Milad-Un-Nabi (Holy Prophet’s Birthday)', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2025-09-07', 'name' => 'Binara Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-10-06', 'name' => 'Vap Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-10-20', 'name' => 'Deepavali Festival Day', 'types' => ['public', 'bank']],
            ['date' => '2025-11-05', 'name' => 'Il Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-12-04', 'name' => 'Unduvap Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2025-12-25', 'name' => 'Christmas Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2026-01-03', 'name' => 'Duruthu Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-01-15', 'name' => 'Tamil Thai Pongal Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2026-02-01', 'name' => 'Navam Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-02-04', 'name' => 'Independence Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2026-02-15', 'name' => 'Maha Sivarathri Day', 'types' => ['public', 'bank']],
            ['date' => '2026-03-02', 'name' => 'Medin Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-03-21', 'name' => 'Id-Ul-Fitr (Ramazan Festival Day)', 'types' => ['public', 'bank']],
            ['date' => '2026-04-01', 'name' => 'Bak Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-04-03', 'name' => 'Good Friday', 'types' => ['public', 'bank']],
            ['date' => '2026-04-13', 'name' => 'Day prior to Sinhala & Tamil New Year Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2026-04-14', 'name' => 'Sinhala & Tamil New Year Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2026-05-01', 'name' => 'Vesak Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-05-01', 'name' => 'May Day (International Workers’ Day)', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2026-05-02', 'name' => 'Day following Vesak Full Moon Poya Day', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2026-05-28', 'name' => 'Id-Ul-Alha (Hadji Festival Day)', 'types' => ['public', 'bank']],
            ['date' => '2026-05-30', 'name' => 'Adhi Poson Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-06-29', 'name' => 'Poson Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-07-29', 'name' => 'Esala Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-08-26', 'name' => 'Milad-Un-Nabi (Holy Prophet’s Birthday)', 'types' => ['public', 'bank', 'mercantile']],
            ['date' => '2026-08-27', 'name' => 'Nikini Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-09-26', 'name' => 'Binara Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-10-25', 'name' => 'Vap Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-11-08', 'name' => 'Deepavali Festival Day', 'types' => ['public', 'bank']],
            ['date' => '2026-11-24', 'name' => 'Il Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-12-23', 'name' => 'Unduvap Full Moon Poya Day', 'types' => ['public', 'bank', 'poya']],
            ['date' => '2026-12-25', 'name' => 'Christmas Day', 'types' => ['public', 'bank', 'mercantile']],
        ];
    }

    /**
     * @return list<array{date: string, name: string, types: list<string>}>
     */
    private static function generateYear(int $year): array
    {
        $poya = self::poyaDays($year);
        $navam = self::firstNamedPoya($poya, 'Navam');
        $vap = self::firstNamedPoya($poya, 'Vap');
        $vesak = self::firstNamedPoya($poya, 'Vesak');

        $holidays = array_merge($poya, [
            self::holiday(sprintf('%d-01-15', $year), 'Tamil Thai Pongal Day', ['public', 'bank', 'mercantile']),
            self::holiday(sprintf('%d-02-04', $year), 'Independence Day', ['public', 'bank', 'mercantile']),
            self::holiday(sprintf('%d-04-13', $year), 'Day prior to Sinhala & Tamil New Year Day', ['public', 'bank', 'mercantile']),
            self::holiday(sprintf('%d-04-14', $year), 'Sinhala & Tamil New Year Day', ['public', 'bank', 'mercantile']),
            self::holiday(self::easterSunday($year)->subDays(2)->toDateString(), 'Good Friday', ['public', 'bank']),
            self::holiday(sprintf('%d-05-01', $year), 'May Day (International Workers’ Day)', ['public', 'bank', 'mercantile']),
            self::holiday(sprintf('%d-12-25', $year), 'Christmas Day', ['public', 'bank', 'mercantile']),
            self::holiday(self::islamicDate($year, 10, 1), 'Id-Ul-Fitr (Ramazan Festival Day)', ['public', 'bank']),
            self::holiday(self::islamicDate($year, 12, 10), 'Id-Ul-Alha (Hadji Festival Day)', ['public', 'bank']),
            self::holiday(self::islamicDate($year, 3, 12), 'Milad-Un-Nabi (Holy Prophet’s Birthday)', ['public', 'bank', 'mercantile']),
        ]);

        if ($navam) {
            $holidays[] = self::holiday(
                CarbonImmutable::parse($navam, 'Asia/Colombo')->addDays(14)->toDateString(),
                'Maha Sivarathri Day',
                ['public', 'bank']
            );
        }
        if ($vap) {
            $holidays[] = self::holiday(
                CarbonImmutable::parse($vap, 'Asia/Colombo')->addDays(14)->toDateString(),
                'Deepavali Festival Day',
                ['public', 'bank']
            );
        }
        if ($vesak) {
            $holidays[] = self::holiday(
                CarbonImmutable::parse($vesak, 'Asia/Colombo')->addDay()->toDateString(),
                'Day following Vesak Full Moon Poya Day',
                ['public', 'bank', 'mercantile']
            );
        }

        return array_values(array_filter(
            $holidays,
            fn ($holiday) => str_starts_with($holiday['date'], (string) $year)
        ));
    }

    /**
     * @param  list<array{date: string, name: string, types: list<string>}>  $poya
     */
    private static function firstNamedPoya(array $poya, string $name): ?string
    {
        foreach ($poya as $day) {
            if (str_contains($day['name'], $name.' Full Moon')) {
                return $day['date'];
            }
        }

        return null;
    }

    /**
     * @return list<array{date: string, name: string, types: list<string>}>
     */
    private static function poyaDays(int $year): array
    {
        $names = [
            1 => 'Duruthu', 2 => 'Navam', 3 => 'Medin', 4 => 'Bak', 5 => 'Vesak', 6 => 'Poson',
            7 => 'Esala', 8 => 'Nikini', 9 => 'Binara', 10 => 'Vap', 11 => 'Il', 12 => 'Unduvap',
        ];
        $byMonth = [];
        foreach (self::fullMoonDates($year) as $date) {
            $month = (int) substr($date, 5, 2);
            $byMonth[$month][] = $date;
        }

        $days = [];
        foreach ($byMonth as $month => $dates) {
            foreach ($dates as $index => $date) {
                $label = $names[$month];
                if ($index > 0) {
                    $next = $names[$month === 12 ? 1 : $month + 1];
                    $label = 'Adhi '.$next;
                }
                $days[] = self::holiday($date, $label.' Full Moon Poya Day', ['public', 'bank', 'poya']);
            }
        }

        return $days;
    }

    /**
     * @return list<string>
     */
    private static function fullMoonDates(int $year): array
    {
        $dates = [];
        $kStart = (int) floor(($year - 2000) * 12.3685) - 2;
        for ($k = $kStart; $k <= $kStart + 18; $k++) {
            $date = self::jdToColomboDate(self::moonPhaseJd($k + 0.5));
            if ((int) substr($date, 0, 4) === $year) {
                $dates[$date] = $date;
            }
        }

        return array_values($dates);
    }

    private static function moonPhaseJd(float $k): float
    {
        $T = $k / 1236.85;
        $jde = 2451550.09766 + 29.530588861 * $k + 0.00015437 * $T ** 2 - 0.000000150 * $T ** 3 + 0.00000000073 * $T ** 4;
        $e = 1 - 0.002516 * $T - 0.0000074 * $T ** 2;
        $m = deg2rad(fmod(2.5534 + 29.10535670 * $k - 0.0000014 * $T ** 2 - 0.00000011 * $T ** 3, 360));
        $mp = deg2rad(fmod(201.5643 + 385.81693528 * $k + 0.0107582 * $T ** 2 + 0.00001238 * $T ** 3, 360));
        $f = deg2rad(fmod(160.7108 + 390.67050284 * $k - 0.0016118 * $T ** 2 - 0.00000227 * $T ** 3, 360));

        return $jde
            - 0.40614 * sin($mp)
            + 0.17302 * $e * sin($m)
            + 0.01614 * sin(2 * $mp)
            + 0.01043 * sin(2 * $f)
            + 0.00734 * $e * sin($mp - $m)
            - 0.00515 * $e * sin($mp + $m)
            + 0.00209 * ($e ** 2) * sin(2 * $m)
            - 0.00111 * sin($mp - 2 * $f);
    }

    private static function jdToColomboDate(float $jd): string
    {
        $unix = (int) round(($jd - 2440587.5) * 86400);

        return CarbonImmutable::createFromTimestampUTC($unix)->timezone('Asia/Colombo')->toDateString();
    }

    private static function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day, 0, 0, 0, 'Asia/Colombo');
    }

    private static function islamicDate(int $gregorianYear, int $islamicMonth, int $islamicDay): string
    {
        $hy = (int) round(($gregorianYear - 622) * 33 / 32) - 1;
        for ($year = $hy; $year <= $hy + 3; $year++) {
            $date = self::islamicToGregorian($year, $islamicMonth, $islamicDay);
            if ((int) substr($date, 0, 4) === $gregorianYear) {
                return $date;
            }
        }

        return sprintf('%d-01-01', $gregorianYear);
    }

    private static function islamicToGregorian(int $year, int $month, int $day): string
    {
        $jd = intdiv(11 * $year + 3, 30) + 354 * $year + 30 * $month - intdiv($month - 1, 2) + $day + 1948440 - 385;

        return self::jdToColomboDate($jd + 0.5);
    }

    /**
     * @param  list<string>  $types
     * @return array{date: string, name: string, types: list<string>}
     */
    private static function holiday(string $date, string $name, array $types): array
    {
        return ['date' => $date, 'name' => $name, 'types' => $types];
    }
}
