<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Location;
use App\Models\ReservationItem;
use App\Models\Reservation;
use App\Models\Resource;
use App\Support\SriLankaHolidays;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\Request;

class ResourceCalendarController extends Controller
{
    public function index(Request $request)
    {
        $reservations = Reservation::query()
            ->with(['requester', 'location', 'items.resource.type.category'])
            ->get();
        $timezone = config('reservations.display_timezone', 'Asia/Colombo');
        $events = $reservations->flatMap(function (Reservation $reservation) use ($timezone) {
            return $reservation->items->map(function (ReservationItem $item) use ($reservation, $timezone) {
                $start = $item->starts_at?->copy()->timezone($timezone)
                    ?: $this->localDateTime($reservation->reservation_date, $reservation->start_time, $timezone);
                $end = $item->ends_at?->copy()->timezone($timezone)
                    ?: $this->localDateTime($reservation->reservation_date, $reservation->end_time, $timezone);

                if (! $start) {
                    return null;
                }

                return [
                    'id' => $reservation->id.'-'.$item->id,
                    'reservationId' => $reservation->id,
                    'title' => $item->resource_name_snapshot ?: $item->resource?->name_model ?: $reservation->title,
                    'start' => $start->format('Y-m-d\TH:i:s'),
                    'end' => $end?->format('Y-m-d\TH:i:s'),
                    'extendedProps' => [
                        'resource' => $item->resource_name_snapshot ?: $item->resource?->name_model ?: $reservation->title,
                        'resourceId' => (string) $item->resource_id,
                        'categoryId' => (string) $item->resource?->type?->resource_category_id,
                        'owner' => $reservation->requester?->name,
                        'location' => $reservation->location?->name,
                        'locationId' => (string) $reservation->location_id,
                        'status' => $reservation->statusEnum()->label(),
                        'statusValue' => $reservation->status,
                        'blocking' => in_array($item->status, ['held', 'confirmed'], true),
                    ],
                ];
            })->filter()->values();
        })->values();

        $resourceRecords = Resource::query()
            ->with(['type.category', 'location'])
            ->orderBy('name_model')
            ->get();

        $today = now(config('reservations.display_timezone', 'Asia/Colombo'))->toDateString();

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
                'thisMonth' => $reservations->filter(fn ($reservation) => $reservation->reservation_date?->isSameMonth(now(config('reservations.display_timezone', 'Asia/Colombo'))))->count(),
            ],
            'canReserve' => $request->user()?->can('create', Reservation::class) ?? false,
        ]);
    }

    private function localDateTime(mixed $date, mixed $time, string $timezone): ?Carbon
    {
        if (! $date) {
            return null;
        }

        $dateString = $date instanceof DateTimeInterface
            ? $date->format('Y-m-d')
            : substr((string) $date, 0, 10);

        if (! preg_match('/\d{2}:\d{2}(?::\d{2})?/', (string) $time, $matches)) {
            return null;
        }

        $timeString = strlen($matches[0]) === 5 ? $matches[0].':00' : $matches[0];

        try {
            return Carbon::parse("{$dateString} {$timeString}", $timezone);
        } catch (\Throwable) {
            return null;
        }
    }
}
