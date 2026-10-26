<?php

namespace App\Http\Controllers;

use App\Enums\CanteenReservationStatus;
use App\Enums\MealType;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\CanteenReservation;
use App\Models\Reservation;
use App\Services\DashboardService;
use Carbon\Carbon;
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

        $user = $request->user();
        $month = $this->month($request);
        $items = [];

        $reservations = Reservation::query()
            ->with('hostelStay')
            ->where('type', ReservationType::HOSTEL->value)
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)->orWhere('created_by_user_id', $user->id);
            })
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

        foreach ($reservations as $reservation) {
            $status = ReservationStatus::tryFrom((string) $reservation->status);
            $event = [
                'title' => $reservation->hostelStay?->guest_name ?: ($reservation->title ?: 'Hostel stay'),
                'href' => route('hostel.show', $reservation),
                'status' => $status?->label() ?? (string) $reservation->status,
                'tone' => $this->reservationTone((string) $reservation->status),
            ];

            foreach ($this->hostelDates($reservation, $month) as $iso) {
                $items[$iso][] = $event;
            }
        }

        return view('dashboards.slt-calendar', $this->calendarViewData(
            kind: 'hostel',
            title: 'Hostel reservation calendar',
            subtitle: 'Your hostel stays for this month.',
            createUrl: route('hostel.create'),
            createLabel: 'Create Hostel Reservation',
            homeUrl: route('hostel.home'),
            month: $month,
            items: $items,
        ));
    }

    public function canteenCalendar(Request $request): View
    {
        $this->authorize('viewAny', CanteenReservation::class);

        $user = $request->user();
        $month = $this->month($request);
        $items = [];

        $reservations = CanteenReservation::query()
            ->where('requested_by_user_id', $user->id)
            ->whereYear('reservation_date', $month->year)
            ->whereMonth('reservation_date', $month->month)
            ->orderBy('reservation_time')
            ->get();

        foreach ($reservations as $reservation) {
            $status = CanteenReservationStatus::tryFrom((string) $reservation->status);
            $meal = MealType::tryFrom((string) $reservation->meal_type);
            $iso = $reservation->reservation_date?->toDateString();
            if (! $iso) {
                continue;
            }

            $items[$iso][] = [
                'title' => $reservation->reservation_name ?: ($meal?->label() ?? 'Canteen order'),
                'href' => route('canteen.show', $reservation),
                'status' => $status?->label() ?? (string) $reservation->status,
                'tone' => $status?->tone() ?? 'pending',
            ];
        }

        return view('dashboards.slt-calendar', $this->calendarViewData(
            kind: 'canteen',
            title: 'Canteen reservation calendar',
            subtitle: 'Your meal and catering orders for this month.',
            createUrl: route('canteen.create'),
            createLabel: 'Create reservation',
            homeUrl: route('canteen.home'),
            month: $month,
            items: $items,
        ));
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
     * @param  array<string, list<array<string, string>>>  $items
     * @return array<string, mixed>
     */
    private function calendarViewData(
        string $kind,
        string $title,
        string $subtitle,
        string $createUrl,
        string $createLabel,
        string $homeUrl,
        Carbon $month,
        array $items,
    ): array {
        $prev = $month->copy()->subMonth();
        $next = $month->copy()->addMonth();
        $today = now($month->timezoneName)->toDateString();
        $cells = [];

        for ($i = 0; $i < $month->dayOfWeek; $i++) {
            $cells[] = ['empty' => true];
        }

        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $date = $month->copy()->day($day);
            $iso = $date->toDateString();
            $dayItems = $items[$iso] ?? [];
            $cells[] = [
                'empty' => false,
                'day' => $day,
                'iso' => $iso,
                'isToday' => $iso === $today,
                'count' => count($dayItems),
                'items' => $dayItems,
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = ['empty' => true];
        }

        return compact('kind', 'title', 'subtitle', 'createUrl', 'createLabel', 'homeUrl', 'cells') + [
            'label' => $month->format('F Y'),
            'prev' => ['year' => $prev->year, 'month' => $prev->month],
            'next' => ['year' => $next->year, 'month' => $next->month],
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

    private function reservationTone(string $status): string
    {
        return match ($status) {
            ReservationStatus::APPROVED->value, ReservationStatus::CONFIRMED->value => 'approved',
            ReservationStatus::PENDING_APPROVAL->value, ReservationStatus::CHANGES_REQUESTED->value, ReservationStatus::DRAFT->value => 'pending',
            ReservationStatus::REJECTED->value, ReservationStatus::EXPIRED->value => 'rejected',
            ReservationStatus::CANCELLED->value => 'cancelled',
            ReservationStatus::COMPLETED->value => 'completed',
            default => 'pending',
        };
    }
}
