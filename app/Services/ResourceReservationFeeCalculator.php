<?php

namespace App\Services;

use App\Enums\ReservationItemStatus;
use App\Models\Reservation;

class ResourceReservationFeeCalculator
{
    /**
     * Build a deterministic fee breakdown using integer minor units.
     * Rate snapshots may store a fixed amount (`amount`/`rate`) or an hourly
     * rate (`hourly_rate` in major units, or `hourly_rate_minor`).
     *
     * @return array{base_fee: float, extra_hours: float, extra_hourly_rate: float, extra_hour_fee: float, tax_fee: float, service_fee: float, discount: float, total: float, has_rate: bool, has_extra_hourly_rate: bool}
     */
    public function breakdown(Reservation $reservation, float $extraHours = 0): array
    {
        $reservedHours = max(0, $this->minutesBetween($reservation->start_time, $reservation->end_time) / 60);
        $extraHours = $this->roundHours(min(24, max(0, $extraHours)));
        $baseMinor = 0;
        $extraHourlyMinor = 0;
        $hasRate = false;

        foreach ($reservation->items as $item) {
            if ($item->status === ReservationItemStatus::CANCELLED->value) {
                continue;
            }

            $snapshot = $item->rate_snapshot;
            if (! is_array($snapshot)) {
                continue;
            }
            $itemHours = $this->itemHours($item->starts_at, $item->ends_at, $reservedHours);
            $quantity = max(0, (float) ($item->quantity ?? 1));

            if (isset($snapshot['amount_minor']) || isset($snapshot['rate_minor'])) {
                $baseMinor += (int) round(max(0, (int) ($snapshot['amount_minor'] ?? $snapshot['rate_minor'])) * $quantity, 0, PHP_ROUND_HALF_UP);
                $hasRate = true;
            } elseif (isset($snapshot['amount']) || isset($snapshot['rate'])) {
                $baseMinor += (int) round($this->majorToMinor($snapshot['amount'] ?? $snapshot['rate']) * $quantity, 0, PHP_ROUND_HALF_UP);
                $hasRate = true;
            } elseif (isset($snapshot['hourly_rate_minor'])) {
                $hourlyMinor = max(0, (int) $snapshot['hourly_rate_minor']);
                $baseMinor += (int) round($hourlyMinor * $itemHours * $quantity, 0, PHP_ROUND_HALF_UP);
                $hasRate = true;
            } elseif (isset($snapshot['hourly_rate'])) {
                $hourlyMinor = $this->majorToMinor($snapshot['hourly_rate']);
                $baseMinor += (int) round($hourlyMinor * $itemHours * $quantity, 0, PHP_ROUND_HALF_UP);
                $hasRate = true;
            }

            if (isset($snapshot['hourly_rate_minor'])) {
                $extraHourlyMinor += (int) round(max(0, (int) $snapshot['hourly_rate_minor']) * $quantity, 0, PHP_ROUND_HALF_UP);
            } elseif (isset($snapshot['hourly_rate'])) {
                $extraHourlyMinor += (int) round($this->majorToMinor($snapshot['hourly_rate']) * $quantity, 0, PHP_ROUND_HALF_UP);
            }
        }

        $extraFeeMinor = (int) round($extraHourlyMinor * $extraHours, 0, PHP_ROUND_HALF_UP);

        return [
            'base_fee' => $this->minorToMajor($baseMinor),
            'extra_hours' => $extraHours,
            'extra_hourly_rate' => $this->minorToMajor($extraHourlyMinor),
            'extra_hour_fee' => $this->minorToMajor($extraFeeMinor),
            'tax_fee' => 0.0,
            'service_fee' => 0.0,
            'discount' => 0.0,
            'total' => $this->minorToMajor($baseMinor + $extraFeeMinor),
            'has_rate' => $hasRate && $baseMinor > 0,
            'has_extra_hourly_rate' => $extraHourlyMinor > 0,
        ];
    }

    private function minutesBetween(string $start, string $end): int
    {
        [$startHour, $startMinute] = array_map('intval', explode(':', substr($start, 0, 5)));
        [$endHour, $endMinute] = array_map('intval', explode(':', substr($end, 0, 5)));
        $minutes = (($endHour * 60) + $endMinute) - (($startHour * 60) + $startMinute);

        return max(0, $minutes);
    }

    private function itemHours(mixed $start, mixed $end, float $fallback): float
    {
        if (! $start || ! $end) {
            return $fallback;
        }

        $minutes = max(0, (int) floor(($end->getTimestamp() - $start->getTimestamp()) / 60));

        return $minutes / 60;
    }

    private function majorToMinor(mixed $amount): int
    {
        return max(0, (int) round((float) $amount * 100, 0, PHP_ROUND_HALF_UP));
    }

    private function minorToMajor(int $amount): float
    {
        return round($amount / 100, 2, PHP_ROUND_HALF_UP);
    }

    private function roundHours(float $hours): float
    {
        return round($hours * 100, 0, PHP_ROUND_HALF_UP) / 100;
    }
}
