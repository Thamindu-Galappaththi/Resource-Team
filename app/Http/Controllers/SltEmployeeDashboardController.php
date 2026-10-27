<?php

namespace App\Http\Controllers;

use App\Enums\CanteenReservationStatus;
use App\Enums\MealType;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\CanteenReservation;
use App\Models\Reservation;
use App\Services\DashboardService;
use App\Support\SriLankaHolidays;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SltEmployeeDashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboards) {}

    public function resource(Request $request): View
    {
        return $this->module($request, 'resource');
    }

    public function hostel(Request $request): View
    {
        return $this->module($request, 'hostel');
    }

    public function canteen(Request $request): View
    {
        return $this->module($request, 'canteen');
    }

    public function hostelCalendar(Request $request): View
    {
        $this->authorize('viewAnyHostel', Reservation::class);

        return view('dashboards.slt-fullcalendar', $this->sharedCalendarPage(
            $request,
            kind: 'hostel',
            title: 'Hostel reservation calendar',
            subtitle: 'Click a date to view stays or create one for that slot.',
            createUrl: route('hostel.create'),
            createLabel: 'Create Hostel Reservation',
            eventsUrl: route('hostel.calendar.events'),
            statsUrl: route('hostel.calendar.stats'),
            calendarUrl: route('hostel.calendar'),
            stats: $this->hostelCalendarStatsData($this->month($request)),
        ));
    }

    public function hostelCalendarEvents(Request $request): JsonResponse
    {
        $this->authorize('viewAnyHostel', Reservation::class);

        return response()->json($this->hostelCalendarEventsData($request));
    }

    public function hostelCalendarStats(Request $request): JsonResponse
    {
        $this->authorize('viewAnyHostel', Reservation::class);

        return response()->json(['stats' => $this->hostelCalendarStatsData($this->month($request))]);
    }

    public function canteenCalendar(Request $request): View
    {
        $this->authorize('viewAny', CanteenReservation::class);

        return view('dashboards.slt-fullcalendar', $this->sharedCalendarPage(
            $request,
            kind: 'canteen',
            title: 'Canteen reservation calendar',
            subtitle: 'Click a date to view orders or create one for that slot.',
            createUrl: route('canteen.create'),
            createLabel: 'Create reservation',
            eventsUrl: route('canteen.calendar.events'),
            statsUrl: route('canteen.calendar.stats'),
            calendarUrl: route('canteen.calendar'),
            stats: $this->canteenCalendarStatsData($this->month($request)),
        ));
    }

    public function canteenCalendarEvents(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CanteenReservation::class);

        return response()->json($this->canteenCalendarEventsData($request));
    }

    public function canteenCalendarStats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CanteenReservation::class);

        return response()->json(['stats' => $this->canteenCalendarStatsData($this->month($request))]);
    }

    private function module(Request $request, string $key): View
    {
        $hub = $this->dashboards->sltHub($request->user());
        $card = collect($hub['cards'])->firstWhere('key', $key);

        abort_unless($card, 404);

        return view('dashboards.slt-module', [
            'kind' => $key,
            'title' => $card['title'],
            'subtitle' => $card['blurb'],
            'as_of' => $hub['as_of'],
            'image' => $card['image'],
            'count' => $card['count'],
            'pending' => $card['pending'],
            'actions' => $card['actions'],
        ]);
    }

    private function month(Request $request): Carbon
    {
        $now = now(config('reservations.display_timezone', 'Asia/Colombo'));
        $year = $request->filled('year') ? max(2000, min(2100, (int) $request->year)) : $now->year;
        $month = $request->filled('month') ? max(1, min(12, (int) $request->month)) : $now->month;

        return Carbon::create($year, $month, 1, 0, 0, 0, $now->timezoneName)->startOfMonth();
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, mixed>
     */
    private function sharedCalendarPage(
        Request $request,
        string $kind,
        string $title,
        string $subtitle,
        string $createUrl,
        string $createLabel,
        string $eventsUrl,
        string $statsUrl,
        string $calendarUrl,
        array $stats,
    ): array {
        $month = $this->month($request);

        return [
            'kind' => $kind,
            'title' => $title,
            'subtitle' => $subtitle,
            'createUrl' => $createUrl,
            'createLabel' => $createLabel,
            'eventsUrl' => $eventsUrl,
            'statsUrl' => $statsUrl,
            'calendarUrl' => $calendarUrl,
            'canCreate' => true,
            'year' => $month->year,
            'month' => $month->month,
            'stats' => $stats,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function hostelCalendarEventsData(Request $request): array
    {
        $range = $this->eventRange($request);
        $user = $request->user();
        $events = [];

        $reservations = Reservation::query()
            ->with(['hostelStay.roomType', 'location', 'requester'])
            ->where('type', ReservationType::HOSTEL->value)
            ->where('status', '!=', ReservationStatus::DRAFT->value)
            ->where(function ($query) use ($range) {
                $query->whereBetween('reservation_date', [$range['start']->toDateString(), $range['end']->toDateString()])
                    ->orWhereHas('hostelStay', function ($stay) use ($range) {
                        $stay->where('check_in_at', '<', $range['end'])
                            ->where('check_out_at', '>', $range['start']);
                    });
            })
            ->get();

        foreach ($reservations as $reservation) {
            $event = $this->hostelEvent($reservation, $user);
            if ($event) {
                $events[] = $event;
            }
        }

        return $this->withHolidays($events, $range);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function canteenCalendarEventsData(Request $request): array
    {
        $range = $this->eventRange($request);
        $user = $request->user();
        $events = [];

        $reservations = CanteenReservation::query()
            ->with(['location', 'requestedBy'])
            ->whereDate('reservation_date', '>=', $range['start']->toDateString())
            ->whereDate('reservation_date', '<', $range['end']->toDateString())
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->get();

        foreach ($reservations as $reservation) {
            $event = $this->canteenEvent($reservation, $user);
            if ($event) {
                $events[] = $event;
            }
        }

        return $this->withHolidays($events, $range);
    }

    /**
     * @return array<string, int>
     */
    private function hostelCalendarStatsData(Carbon $month): array
    {
        $reservations = Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->where('status', '!=', ReservationStatus::DRAFT->value)
            ->where(function ($query) use ($month) {
                $query->where(function ($inner) use ($month) {
                    $inner->whereYear('reservation_date', $month->year)
                        ->whereMonth('reservation_date', $month->month);
                })->orWhereHas('hostelStay', function ($stay) use ($month) {
                    $stay->where('check_in_at', '<', $month->copy()->endOfMonth()->endOfDay())
                        ->where('check_out_at', '>', $month->copy()->startOfMonth()->startOfDay());
                });
            })
            ->get();

        $today = now($month->timezoneName)->toDateString();

        return [
            'month' => $reservations->count(),
            'today' => $reservations->filter(fn (Reservation $reservation) => in_array($today, $this->hostelDates($reservation, $month), true))->count(),
            'pending' => $reservations->where('status', ReservationStatus::PENDING_APPROVAL->value)->count(),
            'approved' => $reservations->whereIn('status', [
                ReservationStatus::APPROVED->value,
                ReservationStatus::CONFIRMED->value,
            ])->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function canteenCalendarStatsData(Carbon $month): array
    {
        $reservations = CanteenReservation::query()
            ->whereYear('reservation_date', $month->year)
            ->whereMonth('reservation_date', $month->month)
            ->get();

        $today = now($month->timezoneName)->toDateString();

        return [
            'month' => $reservations->count(),
            'today' => $reservations->filter(fn (CanteenReservation $reservation) => $reservation->reservation_date?->toDateString() === $today)->count(),
            'pending' => $reservations->where('status', CanteenReservationStatus::PENDING->value)->count(),
            'approved' => $reservations->whereIn('status', CanteenReservationStatus::accepted())->count(),
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function eventRange(Request $request): array
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        return [
            'start' => Carbon::parse($data['start'])->startOfDay(),
            'end' => Carbon::parse($data['end'])->startOfDay(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @param  array{start: Carbon, end: Carbon}  $range
     * @return list<array<string, mixed>>
     */
    private function withHolidays(array $events, array $range): array
    {
        $start = $range['start']->toDateString();
        $end = $range['end']->toDateString();
        $holidays = collect(SriLankaHolidays::calendarEvents())->filter(function (array $event) use ($start, $end) {
            $date = substr((string) ($event['start'] ?? ''), 0, 10);

            return $date >= $start && $date < $end;
        });

        return collect($events)->merge($holidays)->values()->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function hostelEvent(Reservation $reservation, $user): ?array
    {
        $stay = $reservation->hostelStay;
        $timezone = config('reservations.display_timezone', 'Asia/Colombo');
        $mine = $this->ownsReservation($reservation, $user);
        $status = ReservationStatus::tryFrom((string) $reservation->status);
        $title = $mine
            ? ($stay?->guest_name ?: ($reservation->title ?: 'Hostel stay'))
            : ($stay?->roomType?->name ?? $reservation->location?->name ?? 'Hostel stay');

        if ($stay?->check_in_at && $stay->check_out_at) {
            $start = $stay->check_in_at->copy()->timezone($timezone)->startOfDay();
            $end = $stay->check_out_at->copy()->timezone($timezone)->startOfDay()->addDay();
        } elseif ($reservation->reservation_date) {
            $start = $reservation->reservation_date->copy()->timezone($timezone)->startOfDay();
            $end = $start->copy()->addDay();
        } else {
            return null;
        }

        return $this->calendarEvent(
            id: 'hostel-'.$reservation->id,
            reservationId: $reservation->id,
            title: $title,
            start: $start,
            end: $end,
            allDay: true,
            resource: $title,
            location: $reservation->location?->name ?? '—',
            status: $status?->label() ?? (string) $reservation->status,
            statusValue: (string) $reservation->status,
            mine: $mine,
            url: $mine ? route('hostel.show', $reservation) : null,
            reference: $mine ? $reservation->reference : null,
            owner: $mine ? ($reservation->requester?->name ?? 'Unknown') : null,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function canteenEvent(CanteenReservation $reservation, $user): ?array
    {
        $iso = $reservation->reservation_date?->toDateString();
        if (! $iso) {
            return null;
        }

        $timezone = config('reservations.display_timezone', 'Asia/Colombo');
        $time = substr((string) $reservation->reservation_time, 0, 5) ?: '00:00';
        $start = Carbon::parse($iso.' '.$time, $timezone);
        $end = $start->copy()->addHour();
        $status = CanteenReservationStatus::tryFrom((string) $reservation->status);
        $meal = MealType::tryFrom((string) $reservation->meal_type);
        $mine = (int) $reservation->requested_by_user_id === (int) $user->id;
        $title = $mine
            ? ($reservation->reservation_name ?: ($meal?->label() ?? 'Canteen order'))
            : ($reservation->location?->name ?: ($meal?->label() ?? 'Canteen order'));

        return $this->calendarEvent(
            id: 'canteen-'.$reservation->id,
            reservationId: $reservation->id,
            title: $title,
            start: $start,
            end: $end,
            allDay: false,
            resource: $title,
            location: $reservation->location?->name ?? '—',
            status: $status?->label() ?? (string) $reservation->status,
            statusValue: match ($status) {
                CanteenReservationStatus::PENDING => ReservationStatus::PENDING_APPROVAL->value,
                CanteenReservationStatus::APPROVED, CanteenReservationStatus::CONFIRMED => ReservationStatus::APPROVED->value,
                CanteenReservationStatus::REJECTED => ReservationStatus::REJECTED->value,
                CanteenReservationStatus::CANCELLED => ReservationStatus::CANCELLED->value,
                CanteenReservationStatus::COMPLETED => ReservationStatus::COMPLETED->value,
                default => ReservationStatus::PENDING_APPROVAL->value,
            },
            mine: $mine,
            url: $mine ? route('canteen.show', $reservation) : null,
            reference: $mine ? $reservation->reservation_ref : null,
            owner: $mine ? ($reservation->requestedBy?->name ?? null) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calendarEvent(
        string $id,
        int $reservationId,
        string $title,
        Carbon $start,
        Carbon $end,
        bool $allDay,
        string $resource,
        string $location,
        string $status,
        string $statusValue,
        bool $mine,
        ?string $url,
        ?string $reference,
        ?string $owner,
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'allDay' => $allDay,
            'classNames' => $mine ? ['is-mine'] : [],
            'extendedProps' => [
                'reservationId' => $reservationId,
                'reference' => $reference,
                'purpose' => $mine ? $title : null,
                'reservationStart' => $start->toIso8601String(),
                'reservationEnd' => $end->toIso8601String(),
                'resource' => $resource,
                'owner' => $owner,
                'location' => $location,
                'status' => $status,
                'statusKey' => $statusValue,
                'statusValue' => $statusValue,
                'mine' => $mine,
                'url' => $url,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function hostelDates(Reservation $reservation, Carbon $month): array
    {
        $stay = $reservation->hostelStay;
        if ($stay?->check_in_at && $stay->check_out_at) {
            $start = $stay->check_in_at->copy()->timezone($month->timezoneName)->startOfDay();
            $end = $stay->check_out_at->copy()->timezone($month->timezoneName)->startOfDay()->subSecond();
            $cursor = $start->greaterThan($month) ? $start : $month->copy();
            $limit = $end->lessThan($month->copy()->endOfMonth()) ? $end : $month->copy()->endOfMonth();
            $dates = [];
            while ($cursor->lte($limit)) {
                $dates[] = $cursor->toDateString();
                $cursor->addDay();
            }

            return $dates !== [] ? $dates : [$reservation->reservation_date?->toDateString() ?: $month->toDateString()];
        }

        $iso = $reservation->reservation_date?->toDateString();

        return $iso ? [$iso] : [];
    }

    private function ownsReservation(Reservation $reservation, $user): bool
    {
        return (int) $reservation->requester_id === (int) $user->id
            || (int) $reservation->created_by_user_id === (int) $user->id;
    }

}
