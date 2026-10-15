<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Resource;
use App\Services\ReservationBookingService;
use App\Support\SriLankaHolidays;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ResourceCalendarController extends Controller
{
    public function __construct(private readonly ReservationBookingService $bookings) {}

    public function index(Request $request)
    {
        $reservations = Reservation::query()
            ->with(['requester', 'location', 'items.resource.type.category'])
            ->visibleTo($request->user())
            ->where('status', '!=', ReservationStatus::DRAFT->value)
            ->get();

        $conflicts = $this->conflicts($reservations);
        $events = $reservations->flatMap(function (Reservation $reservation) use ($conflicts) {
            return $reservation->items
                ->filter(fn (ReservationItem $item) => $item->starts_at && $item->ends_at && $item->starts_at->lt($item->ends_at))
                ->flatMap(fn (ReservationItem $item) => $this->calendarItemSegments($reservation, $item, $conflicts));
        })->values();

        $resourceRecords = Resource::query()
            ->with(['type.category', 'location'])
            ->orderBy('name_model')
            ->get();

        $today = now($this->bookings->timezone())->toDateString();

        return view('resources.calendar', [
            'calendarEvents' => $events,
            'holidayEvents' => SriLankaHolidays::calendarEvents(),
            'resources' => $resourceRecords,
            'schedulerResources' => $resourceRecords->map(fn (Resource $resource) => [
                'id' => (string) $resource->id,
                'name' => $resource->name_model,
                'type' => $resource->type?->name,
                'category' => $resource->type?->category?->name ?? 'Uncategorized',
                'categoryId' => (string) $resource->type?->resource_category_id,
                'location' => $resource->location?->name,
                'locationId' => (string) $resource->location_id,
                'status' => $resource->status,
                'bookable' => $resource->status === 'active',
            ])->values(),
            'categories' => $resourceRecords->map(fn (Resource $resource) => $resource->type?->category)
                ->filter()->unique('id')->sortBy('name')->values(),
            'locations' => Location::query()->ordered()->get(['id', 'name']),
            'stats' => [
                'myResources' => $resourceRecords->where('status', 'active')->count(),
                'todayBookings' => $reservations->filter(fn ($reservation) => $reservation->reservation_date?->toDateString() === $today)->count(),
                'pendingApprovals' => $reservations->where('status', ReservationStatus::PENDING_APPROVAL->value)->count(),
                'thisMonth' => $reservations->filter(fn ($reservation) => $reservation->reservation_date?->isSameMonth(now($this->bookings->timezone())))->count(),
            ],
            'canReserve' => $request->user()?->can('create', Reservation::class) ?? false,
        ]);
    }

    /**
     * @param  Collection<int, Reservation>  $reservations
     * @return array<int, bool>
     */
    private function conflicts(Collection $reservations): array
    {
        $blockingStatuses = ReservationStatus::blockingItemStatuses();
        $bookedItems = $reservations->flatMap(fn (Reservation $reservation) =>
            $reservation->items
                ->filter(fn ($item) => in_array($item->status, $blockingStatuses, true) && $item->starts_at && $item->ends_at)
                ->map(fn ($item) => [
                    'reservation_id' => $reservation->id,
                    'resource_id' => $item->resource_id,
                    'starts_at' => $item->starts_at,
                    'ends_at' => $item->ends_at,
                ])
        );
        $conflicts = [];
        foreach ($bookedItems->groupBy('resource_id') as $items) {
            $items = $items->sortBy(fn ($item) => $item['starts_at']->getTimestamp())->values();
            for ($i = 0; $i < $items->count(); $i++) {
                for ($j = $i + 1; $j < $items->count(); $j++) {
                    if (! $items[$i]['starts_at']->lt($items[$j]['ends_at'])) {
                        break;
                    }
                    if ($items[$i]['reservation_id'] !== $items[$j]['reservation_id']
                        && $items[$i]['ends_at']->gt($items[$j]['starts_at'])) {
                        $conflicts[$items[$i]['reservation_id']] = true;
                        $conflicts[$items[$j]['reservation_id']] = true;
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * @param  array<int, bool>  $conflicts
     * @return list<array<string, mixed>>
     */
    private function calendarItemSegments(Reservation $reservation, ReservationItem $item, array $conflicts): array
    {
        $timezone = $this->bookings->timezone();
        $start = $item->starts_at->timezone($timezone);
        $end = $item->ends_at->timezone($timezone);
        $minutes = max(0, (int) $start->diffInMinutes($end));
        $overnight = $start->toDateString() !== $end->copy()->subSecond()->toDateString();
        $duration = trim(implode(' ', array_filter([
            intdiv($minutes, 60) ? intdiv($minutes, 60).' hr' : null,
            $minutes % 60 ? ($minutes % 60).' min' : null,
        ]))) ?: 'Less than a minute';
        if ($reservation->type === ReservationType::HOSTEL->value && $overnight) {
            $nights = max(1, (int) $start->copy()->startOfDay()->diffInDays($end->copy()->subSecond()->startOfDay()));
            $duration = $nights === 1 ? '1 night' : $nights.' nights';
        }
        $conflict = isset($conflicts[$reservation->id]);
        $resourceName = $item->resource_name_snapshot ?: $item->resource?->name_model ?: $reservation->title ?: '—';
        $segments = [];
        $segmentStart = $start->copy();

        while ($segmentStart->lt($end)) {
            $midnight = $segmentStart->copy()->startOfDay()->addDay();
            $segmentEnd = $end->lt($midnight) ? $end->copy() : $midnight;
            $segments[] = [
                'id' => $reservation->id.'-'.$item->id.'-'.$segmentStart->toDateString(),
                'reservationId' => $reservation->id,
                'title' => $resourceName,
                'start' => $segmentStart->toIso8601String(),
                'end' => $segmentEnd->toIso8601String(),
                'extendedProps' => [
                    'reservationId' => $reservation->id,
                    'reference' => $reservation->reference,
                    'purpose' => $reservation->title ?: $reservation->purpose,
                    'reservationStart' => $start->toIso8601String(),
                    'reservationEnd' => $end->toIso8601String(),
                    'resource' => $resourceName,
                    'resourceId' => (string) $item->resource_id,
                    'categoryId' => (string) $item->resource?->type?->resource_category_id,
                    'owner' => $reservation->requester?->name ?? 'Unknown',
                    'location' => $reservation->location?->name ?? '—',
                    'locationId' => (string) $reservation->location_id,
                    'duration' => $duration,
                    'reservationType' => $reservation->type,
                    'status' => $reservation->statusEnum()->label(),
                    'statusKey' => $reservation->status,
                    'statusValue' => $reservation->status,
                    'conflict' => $conflict,
                    'blocking' => in_array($item->status, ['held', 'confirmed'], true),
                    'url' => $this->calendarReservationUrl($reservation),
                ],
            ];
            $segmentStart = $segmentEnd;
        }

        return $segments;
    }

    private function calendarReservationUrl(Reservation $reservation): ?string
    {
        $user = request()->user();
        if ($reservation->type === ReservationType::HOSTEL->value && $user?->can('viewHostel', $reservation)) {
            return route('hostel.show', $reservation);
        }

        if ($user?->can('view', $reservation) && $user?->hasPermission('reservations.index')) {
            return route('reservations.show', $reservation);
        }

        return null;
    }
}
