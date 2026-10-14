<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourcePaymentController extends Controller
{
    public function index(): View
    {
        return view('payments.resources');
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate(['query' => ['required', 'string', 'min:2', 'max:120']]);
        $query = trim($data['query']);
        $reservation = Reservation::query()
            ->with(['requester', 'createdBy', 'items'])
            ->whereNotIn('status', [ReservationStatus::DRAFT->value, ReservationStatus::CANCELLED->value, ReservationStatus::REJECTED->value])
            ->where(function ($builder) use ($query) {
                $builder->where('reference', $query)
                    ->orWhereHas('items', fn ($items) => $items->where('resource_name_snapshot', 'like', '%'.$query.'%'));
            })
            ->orderByDesc('reservation_date')
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'No active reservation matched that reference or resource name.'], 404);
        }

        $start = substr((string) $reservation->start_time, 0, 5);
        $end = substr((string) $reservation->end_time, 0, 5);
        $hours = max(0, (strtotime($end) - strtotime($start)) / 3600);
        $rates = $reservation->items->map(fn ($item) => $item->rate_snapshot ?? [])->filter(fn ($rate) => is_array($rate) && count($rate));
        $base = $rates->sum(function ($rate) use ($hours) {
            $fixedAmount = $rate['amount'] ?? $rate['rate'] ?? null;
            if ($fixedAmount !== null) {
                return (float) $fixedAmount;
            }

            $hourlyRate = $rate['hourly_rate'] ?? null;
            if ($hourlyRate !== null) {
                return (float) $hourlyRate * $hours;
            }

            $hourlyRateMinor = $rate['hourly_rate_minor'] ?? null;
            if ($hourlyRateMinor !== null) {
                return ((float) $hourlyRateMinor / 100) * $hours;
            }

            return 0;
        });
        $extraHourlyRate = $rates->sum(function ($rate) {
            if (isset($rate['hourly_rate'])) {
                return max(0, (float) $rate['hourly_rate']);
            }

            if (isset($rate['hourly_rate_minor'])) {
                return max(0, (float) $rate['hourly_rate_minor'] / 100);
            }

            return 0;
        });

        return response()->json([
            'reference' => $reservation->reference,
            'title' => $reservation->title ?: $reservation->purpose ?: 'Resource reservation',
            'resource' => $reservation->resourceNames() ?: 'Resource reservation',
            'requester' => $reservation->requester?->name ?? $reservation->createdBy?->name ?? 'Reservation user',
            'date' => $reservation->reservation_date?->format('M d, Y'),
            'start' => $start,
            'end' => $end,
            'hours' => round($hours, 2),
            'base_fee' => round($base, 2),
            'extra_hourly_rate' => round($extraHourlyRate, 2),
            'has_rate' => $rates->isNotEmpty() && $base > 0,
            'has_extra_hourly_rate' => $extraHourlyRate > 0,
            'rate_note' => $rates->isEmpty() ? 'No rate is stored for this reservation; finance must configure rates before charging.' : ($extraHourlyRate <= 0 ? 'The reservation has a base amount, but no hourly rate is stored for extra usage.' : null),
        ]);
    }
}
