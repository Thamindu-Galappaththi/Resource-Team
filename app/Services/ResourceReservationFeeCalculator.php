<?php

namespace App\Services;

use App\Enums\ReservationItemStatus;
use App\Models\Reservation;
use App\Models\ResourceRate;
use App\Models\ResourcePaymentDiscountRule;
use App\Models\Resource;
use App\Services\AnnexureRateCardImporter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ResourceReservationFeeCalculator
{
    /**
     * Resolve current rates and calculate every amount in integer cents.
     * extraHours is additional usage beyond the reserved duration.
     */
    public function breakdown(Reservation $reservation, float $extraHours = 0): array
    {
        $extraHours = round(min(24, max(0, $extraHours)), 2, PHP_ROUND_HALF_UP);
        $reservationDate = $reservation->reservation_date?->toDateString() ?? now()->toDateString();
        $baseMinor = 0;
        $extraMinor = 0;
        $baseDuration = 0.0;
        $extraRatesMinor = 0;
        $hasRate = true;
        $lines = [];
        $bookingType = $reservation->booking_type ?: $this->inferBookingType($reservation);
        $dailyBooking = $bookingType === 'full_day';
        $bookedHours = $dailyBooking ? 8.0 : 4.0;
        $bookingLabel = match ($bookingType) {
            'full_day' => 'Full day',
            'half_day_afternoon' => 'Half day afternoon',
            default => 'Half day morning',
        };
        $timeLabel = match ($bookingType) {
            'full_day' => '09:00–17:00',
            'half_day_afternoon' => '13:00–17:00',
            default => '09:00–13:00',
        };

        foreach ($reservation->items as $item) {
            if ($item->status === ReservationItemStatus::CANCELLED->value) {
                continue;
            }

            $resource = $item->resource;
            $typeId = $resource?->resource_type_id;
            if (! $typeId) {
                $hasRate = false;
                continue;
            }

            $rate = $this->resolveRate($resource, $reservationDate);
            if (! $rate) {
                $hasRate = false;
                continue;
            }

            $quantity = max(1, (float) ($item->quantity ?? 1));
            $billableExtraHours = $extraHours > 0
                ? ceil(round($extraHours * 2, 8)) / 2
                : 0.0;
            $unitBaseFee = $dailyBooking
                ? (float) ($rate->daily_rate ?? $rate->base_fee)
                : (float) ($rate->half_day_rate ?? $rate->base_fee);
            $lineBaseMinor = $this->toMinor($unitBaseFee * $quantity);
            $lineRateMinor = $this->toMinor($rate->extra_hour_rate * $quantity);
            $lineExtraMinor = (int) round($lineRateMinor * $billableExtraHours, 0, PHP_ROUND_HALF_UP);

            $baseMinor += $lineBaseMinor;
            $extraRatesMinor += $lineRateMinor;
            $extraMinor += $lineExtraMinor;
            $baseDuration = max($baseDuration, $bookedHours);
            $lines[] = [
                'resource' => $item->resource_name_snapshot ?: $resource->name_model,
                'quantity' => $quantity,
                'base_fee' => $this->toMajor($lineBaseMinor),
                'base_unit_fee' => $this->toMajor($this->toMinor($unitBaseFee)),
                'base_duration_hours' => $bookedHours,
                'booking_type' => $bookingLabel,
                'booking_time' => $timeLabel,
                'extra_hours' => $billableExtraHours,
                'extra_hourly_rate' => $this->toMajor($lineRateMinor),
                'extra_hourly_unit_rate' => $this->toMajor($this->toMinor($rate->extra_hour_rate)),
                'extra_hour_fee' => $this->toMajor($lineExtraMinor),
                'subtotal_minor' => $lineBaseMinor + $lineExtraMinor,
                'tax_percent' => (float) $rate->tax_percent,
            ];
        }

        // Do not issue a partial invoice when one of the reservation items
        // has no applicable rate.
        if (! $hasRate || $lines === []) {
            $baseMinor = $extraMinor = $extraRatesMinor = 0;
            $lines = [];
        }

        $subtotalMinor = $baseMinor + $extraMinor;
        $category = Str::slug((string) ($reservation->requester?->user_type ?: $reservation->requester?->roleSlug() ?: 'default'), '_');
        $discountRule = ResourcePaymentDiscountRule::query()->where('customer_category', $category)->first();
        $configuredDiscount = $discountRule
            ? ($discountRule->is_active ? (float) $discountRule->discount_percent : 0)
            : (float) config('payments.resource_rate_discounts.'.$category, 0);
        $discountPercent = min(100, max(0, $configuredDiscount));
        $discountMinor = (int) round($subtotalMinor * $discountPercent / 100, 0, PHP_ROUND_HALF_UP);
        $taxMinor = 0;
        $allocatedDiscount = 0;
        foreach ($lines as $index => $line) {
            $lineDiscount = $index === array_key_last($lines)
                ? $discountMinor - $allocatedDiscount
                : (int) floor($line['subtotal_minor'] * $discountPercent / 100);
            $allocatedDiscount += $lineDiscount;
            $taxMinor += (int) round(($line['subtotal_minor'] - $lineDiscount) * $line['tax_percent'] / 100, 0, PHP_ROUND_HALF_UP);
        }
        $taxRates = array_values(array_unique(array_column($lines, 'tax_percent')));
        $taxPercent = count($taxRates) === 1 ? (float) $taxRates[0] : null;
        $totalMinor = max(0, $subtotalMinor - $discountMinor + $taxMinor);

        return [
            'base_fee' => $this->toMajor($baseMinor),
            'extra_hours' => array_sum(array_column($lines, 'extra_hours')),
            'billable_extra_hours' => array_sum(array_column($lines, 'extra_hours')),
            'usage_extension_hours' => $extraHours,
            'booking_type' => $bookingLabel,
            'extra_hourly_rate' => $this->toMajor($extraRatesMinor),
            'extra_hour_fee' => $this->toMajor($extraMinor),
            'subtotal' => $this->toMajor($subtotalMinor),
            'discount_percent' => $discountPercent,
            'discount' => $this->toMajor($discountMinor),
            'tax_percent' => $taxPercent,
            'tax_fee' => $this->toMajor($taxMinor),
            'service_fee' => 0.0,
            'total' => $this->toMajor($totalMinor),
            'base_duration_hours' => $baseDuration,
            'has_rate' => $hasRate && count($lines) > 0,
            'has_extra_hourly_rate' => $extraRatesMinor > 0,
            'rate_note' => $hasRate && count($lines) > 0 ? null : 'Rate details are not available for this resource and reservation date.',
            'lines' => $lines,
        ];
    }

    private function resolveRate(Resource $resource, string $date): ?ResourceRate
    {
        $locationId = $resource->location_id;
        $base = ResourceRate::query()
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date));

        $specificAtLocation = (clone $base)->where('resource_id', $resource->id)->where('location_id', $locationId)->orderByDesc('effective_from')->first();
        if ($specificAtLocation) return $specificAtLocation;

        $normalizedName = AnnexureRateCardImporter::normalizeResourceName($resource->name_model);
        $locationRate = (clone $base)->where('location_id', $locationId)->whereNull('resource_id')->get()
            ->first(fn (ResourceRate $rate) => AnnexureRateCardImporter::normalizeResourceName($rate->resource_name) === $normalizedName);
        if ($locationRate) return $locationRate;

        $specificGlobal = (clone $base)->where('resource_id', $resource->id)->whereNull('location_id')->orderByDesc('effective_from')->first();
        if ($specificGlobal) return $specificGlobal;

        return (clone $base)->whereNull('location_id')->whereNull('resource_id')->get()
            ->sortByDesc('effective_from')
            ->first(fn (ResourceRate $rate) => AnnexureRateCardImporter::normalizeResourceName($rate->resource_name) === $normalizedName);
    }

    private function inferBookingType(Reservation $reservation): string
    {
        $start = substr((string) $reservation->start_time, 0, 5);
        $end = substr((string) $reservation->end_time, 0, 5);
        if ($start === '09:00' && $end === '17:00') return 'full_day';
        if ($start === '13:00' && $end === '17:00') return 'half_day_afternoon';
        if ($start === '09:00' && $end === '13:00') return 'half_day_morning';
        [$startHour, $startMinute] = array_map('intval', explode(':', $start));
        [$endHour, $endMinute] = array_map('intval', explode(':', $end));
        $durationMinutes = (($endHour * 60 + $endMinute) - ($startHour * 60 + $startMinute) + 1440) % 1440;
        if ($durationMinutes >= 7 * 60) return 'full_day';
        return $startHour >= 13 ? 'half_day_afternoon' : 'half_day_morning';
    }

    private function itemHours(mixed $startsAt, mixed $endsAt, Reservation $reservation): float
    {
        if ($startsAt && $endsAt) {
            return max(0, ($endsAt->getTimestamp() - $startsAt->getTimestamp()) / 3600);
        }

        [$sh, $sm] = array_map('intval', explode(':', substr((string) $reservation->start_time, 0, 5)));
        [$eh, $em] = array_map('intval', explode(':', substr((string) $reservation->end_time, 0, 5)));
        $minutes = (($eh * 60 + $em) - ($sh * 60 + $sm) + 1440) % 1440;

        return $minutes / 60;
    }

    private function toMinor(float|int|string $amount): int
    {
        return (int) round((float) $amount * 100, 0, PHP_ROUND_HALF_UP);
    }

    private function toMajor(int $minor): float
    {
        return round($minor / 100, 2, PHP_ROUND_HALF_UP);
    }
}
