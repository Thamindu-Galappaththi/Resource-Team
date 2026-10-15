<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\ResourceReservationFeeCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ResourcePaymentController extends Controller
{
    public function index(): View
    {
        return view('payments.resources');
    }

    public function lookup(Request $request, ResourceReservationFeeCalculator $fees): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'regex:/^(RRS|HST)-[0-9]{4}-[0-9]{6}$/'],
            'extra_hours' => ['sometimes', 'numeric', 'min:0', 'max:24'],
        ], [
            'reference.regex' => 'Enter a reservation reference in the format RRS-YYYY-000000 or HST-YYYY-000000.',
        ]);
        $reference = strtoupper(trim($data['reference']));
        $reservation = Reservation::query()
            ->with(['requester', 'createdBy', 'items'])
            ->whereNotIn('status', [ReservationStatus::DRAFT->value, ReservationStatus::CANCELLED->value, ReservationStatus::REJECTED->value])
            ->where('reference', $reference)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'No payable reservation was found for that reference.'], 404);
        }

        $start = substr((string) $reservation->start_time, 0, 5);
        $end = substr((string) $reservation->end_time, 0, 5);
        $hours = max(0, (strtotime($end) - strtotime($start)) / 3600);
        $breakdown = $fees->breakdown($reservation, (float) ($data['extra_hours'] ?? 0));
        $payload = [
            'reference' => $reservation->reference,
            'title' => $reservation->title ?: $reservation->purpose ?: 'Resource reservation',
            'resource' => $reservation->resourceNames() ?: 'Resource reservation',
            'requester' => $reservation->requester?->name ?? $reservation->createdBy?->name ?? 'Reservation user',
            'date' => $reservation->reservation_date?->format('M d, Y'),
            'start' => $start,
            'end' => $end,
            'hours' => round($hours, 2),
            'payment_status' => 'pending',
            'payment_method' => 'online',
            'currency' => 'LKR',
            'rate_note' => ! $breakdown['has_rate'] ? 'No rate is stored for this reservation; finance must configure rates before charging.' : (! $breakdown['has_extra_hourly_rate'] ? 'No hourly rate is stored for extra usage; the extra fee is zero.' : null),
        ] + $breakdown;

        return response()->json($payload);
    }

    public function print(Request $request, string $reference, ResourceReservationFeeCalculator $fees): View|Response
    {
        validator(['reference' => $reference], [
            'reference' => ['required', 'string', 'regex:/^(RRS|HST)-[0-9]{4}-[0-9]{6}$/'],
        ])->validate();

        $data = $request->validate(['extra_hours' => ['sometimes', 'numeric', 'min:0', 'max:24']]);
        $reservation = Reservation::query()
            ->with(['requester', 'createdBy', 'items'])
            ->whereNotIn('status', [ReservationStatus::DRAFT->value, ReservationStatus::CANCELLED->value, ReservationStatus::REJECTED->value])
            ->where('reference', strtoupper($reference))
            ->firstOrFail();

        return view('payments.resource-invoice-print', [
            'reservation' => $reservation,
            'breakdown' => $fees->breakdown($reservation, (float) ($data['extra_hours'] ?? 0)),
        ]);
    }
}
