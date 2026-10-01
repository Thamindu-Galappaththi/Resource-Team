<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Location;
use App\Models\ReservationItem;
use App\Models\Reservation;
use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceCalendarController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::query()->with(['requester', 'location', 'items.resource.type.category']);
        if (! $request->user()?->hasRole('super_admin', 'admin', 'coordinator', 'resource_owner')) {
            $query->where(function ($inner) use ($request) {
                $inner->where('requester_id', $request->user()?->id)
                    ->orWhere('created_by_user_id', $request->user()?->id);
            });
        }

        $reservations = $query->get();
        $events = $reservations->flatMap(fn (Reservation $reservation) => $reservation->items->map(fn (ReservationItem $item) => [
            'id' => $reservation->id.'-'.$item->id,
            'reservationId' => $reservation->id,
            'title' => $item->resource_name_snapshot ?: $item->resource?->name_model ?: $reservation->title,
            'start' => $reservation->reservation_date?->format('Y-m-d').'T'.substr((string) $reservation->start_time, 0, 8),
            'end' => $reservation->reservation_date?->format('Y-m-d').'T'.substr((string) $reservation->end_time, 0, 8),
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
        ]))->values();

        $resourceRecords = Resource::query()
            ->with(['type.category', 'location'])
            ->orderBy('name_model')
            ->get();

        $today = now(config('reservations.display_timezone', 'Asia/Colombo'))->toDateString();

        return view('resources.calendar', [
            'calendarEvents' => $events,
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
                'myResources' => Resource::query()->where('status', 'active')->count(),
                'todayBookings' => $reservations->filter(fn ($reservation) => $reservation->reservation_date?->toDateString() === $today)->count(),
                'pendingApprovals' => $reservations->where('status', ReservationStatus::PENDING_APPROVAL->value)->count(),
                'thisMonth' => $reservations->filter(fn ($reservation) => $reservation->reservation_date?->isSameMonth(now(config('reservations.display_timezone', 'Asia/Colombo'))))->count(),
            ],
            'canReserve' => $request->user()?->can('create', Reservation::class) ?? false,
        ]);
    }
}
