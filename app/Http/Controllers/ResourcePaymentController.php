<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\ResourceReservationFeeCalculator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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
            'reference' => ['required', 'string', 'regex:/^(RRS|HST)-[0-9]{4}-[0-9]{6}$/i'],
            'extra_hours' => ['sometimes', 'numeric', 'min:0', 'max:24', 'multiple_of:0.5'],
        ], [
            'reference.regex' => 'Enter a reservation reference in the format RRS-YYYY-000000 or HST-YYYY-000000.',
        ]);
        $reference = strtoupper(trim($data['reference']));
        $reservation = Reservation::query()
            ->with(['requester', 'createdBy', 'items'])
            ->whereIn('status', [ReservationStatus::CONFIRMED->value, ReservationStatus::IN_PROGRESS->value, ReservationStatus::COMPLETED->value])
            ->where('reference', $reference)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'No payable reservation was found for that reference.'], 404);
        }

        $start = substr((string) $reservation->start_time, 0, 5);
        $end = substr((string) $reservation->end_time, 0, 5);
        $hours = $this->reservedHours($reservation->reservation_date?->toDateString(), $start, $end);
        $breakdown = $fees->breakdown($reservation, (float) ($data['extra_hours'] ?? 0));
        $payload = [
            'reference' => $reservation->reference,
            'title' => $reservation->title ?: $reservation->purpose ?: 'Resource reservation',
            'resource' => $reservation->resourceNames() ?: 'Resource reservation',
            'requester' => $reservation->requester?->name ?? $reservation->createdBy?->name ?? 'Reservation user',
            'bill_to_email' => $reservation->requester?->email ?? $reservation->createdBy?->email,
            'date' => $reservation->reservation_date?->format('M d, Y'),
            'reservation_status' => $reservation->statusEnum()->label(),
            'issue_date' => now()->format('d M Y'),
            'start' => $start,
            'end' => $end,
            'hours' => round($hours, 2),
            'payment_status' => 'pending',
            'payment_method' => 'online',
            'currency' => 'LKR',
            'rate_note' => $breakdown['rate_note'],
        ] + $breakdown;

        return response()->json($payload);
    }

    public function confirmUsage(Request $request, ResourceReservationFeeCalculator $fees): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'regex:/^(RRS|HST)-[0-9]{4}-[0-9]{6}$/i'],
            'extra_hours' => ['required', 'numeric', 'min:0', 'max:24', 'multiple_of:0.5'],
        ], [
            'reference.regex' => 'Enter a reservation reference in the format RRS-YYYY-000000 or HST-YYYY-000000.',
            'extra_hours.multiple_of' => 'Extra hours must be entered in 0.5 hour increments.',
        ]);
        $reservation = Reservation::query()
            ->with(['requester', 'createdBy', 'items'])
            ->whereIn('status', [ReservationStatus::CONFIRMED->value, ReservationStatus::IN_PROGRESS->value, ReservationStatus::COMPLETED->value])
            ->where('reference', strtoupper(trim($data['reference'])))
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'No payable reservation was found for that reference.'], 404);
        }

        $breakdown = $fees->breakdown($reservation, (float) $data['extra_hours']);
        if (! $breakdown['has_rate']) {
            return response()->json(['message' => $breakdown['rate_note']], 422);
        }
        DB::table('resource_payment_usage_audits')->insert([
            'reservation_id' => $reservation->id,
            'actor_id' => $request->user()->id,
            'extra_hours' => $breakdown['usage_extension_hours'],
            'fee_breakdown' => json_encode($breakdown, JSON_THROW_ON_ERROR),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Usage confirmed and recorded.',
            'reference' => $reservation->reference,
            'payment_status' => 'pending',
            ...$breakdown,
        ]);
    }

    public function print(Request $request, string $reference, ResourceReservationFeeCalculator $fees): View|Response
    {
        validator(['reference' => $reference], [
            'reference' => ['required', 'string', 'regex:/^(RRS|HST)-[0-9]{4}-[0-9]{6}$/i'],
        ])->validate();

        $data = $request->validate(['extra_hours' => ['sometimes', 'numeric', 'min:0', 'max:24', 'multiple_of:0.5']]);
        $reservation = Reservation::query()
            ->with(['requester', 'createdBy', 'items'])
            ->whereIn('status', [ReservationStatus::CONFIRMED->value, ReservationStatus::IN_PROGRESS->value, ReservationStatus::COMPLETED->value])
            ->where('reference', strtoupper($reference))
            ->firstOrFail();

        return view('payments.resource-invoice-print', [
            'reservation' => $reservation,
            'breakdown' => $fees->breakdown($reservation, (float) ($data['extra_hours'] ?? 0)),
            'issueDate' => now(),
        ]);
    }

    private function reservedHours(?string $date, string $start, string $end): float
    {
        if (! $date) {
            return 0;
        }

        $startsAt = Carbon::parse($date.' '.$start);
        $endsAt = Carbon::parse($date.' '.$end);
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            $endsAt->addDay();
        }

        return round($startsAt->diffInMinutes($endsAt) / 60, 2);
    }
}
