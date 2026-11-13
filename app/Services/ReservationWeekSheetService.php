<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReservationWeekSheetService
{
    /**
     * @return array{
     *     start: Carbon,
     *     end: Carbon,
     *     label: string,
     *     generatedAt: Carbon,
     *     generatedBy: string,
     *     logo: string|null,
     *     filename: string,
     *     rows: Collection<int, array<string, mixed>>,
     *     groups: Collection<string, Collection>,
     *     specialCount: int
     * }
     */
    public function payload(?string $week, User $user): array
    {
        $tz = config('reservations.display_timezone', 'Asia/Colombo');
        $anchor = filled($week) ? Carbon::parse($week, $tz) : now($tz);
        $start = $anchor->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $anchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $reservations = Reservation::query()
            ->with(['requester', 'location', 'items'])
            ->whereNotIn('type', [ReservationType::HOSTEL->value, ReservationType::CANTEEN->value])
            ->whereIn('status', [
                ReservationStatus::APPROVED->value,
                ReservationStatus::CONFIRMED->value,
                ReservationStatus::IN_PROGRESS->value,
                ReservationStatus::COMPLETED->value,
            ])
            ->whereDate('reservation_date', '>=', $start->toDateString())
            ->whereDate('reservation_date', '<=', $end->toDateString())
            ->orderBy('reservation_date')
            ->orderBy('start_time')
            ->get();

        $rows = $reservations->map(function (Reservation $reservation) {
            $purpose = trim((string) ($reservation->purpose ?: ''));
            $title = trim((string) ($reservation->title ?: ''));
            $details = collect([
                ($title !== '' && $purpose !== '' && strcasecmp($title, $purpose) !== 0) ? $purpose : null,
                filled($reservation->lecturer_name) ? 'Lecturer: '.$reservation->lecturer_name : null,
                $reservation->special_approval ? 'Special approval' : null,
                $reservation->attendee_count ? $reservation->attendee_count.' attendees' : null,
            ])->filter()->implode(' · ');

            return [
                'reference' => $reservation->reference,
                'date_key' => $reservation->reservation_date?->toDateString(),
                'date' => $reservation->reservation_date?->format('D, d M Y') ?? '—',
                'time' => substr((string) $reservation->start_time, 0, 5).'–'.substr((string) $reservation->end_time, 0, 5),
                'location' => $reservation->location?->name ?? '—',
                'resources' => $reservation->resourceNames() ?: '—',
                'name' => $title !== '' ? $title : ($purpose !== '' ? $purpose : '—'),
                'details' => $details,
                'requester' => $reservation->requester?->name ?? '—',
                'special' => (bool) $reservation->special_approval,
                'status' => $reservation->statusEnum()->label(),
            ];
        })->values();

        $logo = public_path('images/logos/nebula.png');

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->format('d M Y').' – '.$end->format('d M Y'),
            'generatedAt' => now($tz),
            'generatedBy' => $user->name,
            'logo' => is_file($logo) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logo)) : null,
            'filename' => 'weekly-reservations-'.$start->toDateString(),
            'rows' => $rows,
            'groups' => $rows->groupBy('date'),
            'specialCount' => $rows->where('special', true)->count(),
        ];
    }
}
