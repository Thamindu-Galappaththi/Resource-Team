<?php

namespace App\Services;

use App\Enums\ReservationItemStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\ReservationConflictException;
use App\Models\Location;
use App\Models\ReservationItem;
use App\Models\Resource;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Each hostel location is one "Hostel Room / Bed" resource whose bed_counts hold
 * the beds per group (Permanent, Ladies, Male). Bookings take beds from a group;
 * the group lives on hostel_stay_details.bed_group and the beds on reservation_items.quantity.
 */
class HostelBedCapacity
{
    /**
     * @return array<string, array{label:string, short:string, capacity:int}>
     */
    public function groups(): array
    {
        return config('hostel.bed_groups', []);
    }

    public function groupCapacity(string $group): int
    {
        return (int) ($this->groups()[$group]['capacity'] ?? 0);
    }

    public function locationCapacity(): int
    {
        return array_sum(array_column($this->groups(), 'capacity'));
    }

    /**
     * @param  array<string, mixed>  $counts
     * @return array<string, int>
     */
    public function normalizeCounts(array $counts): array
    {
        return collect($this->groups())
            ->mapWithKeys(fn (array $group, string $key) => [$key => max(0, (int) ($counts[$key] ?? 0))])
            ->all();
    }

    public function hostelAt(int $locationId, bool $lock = false): ?Resource
    {
        return Resource::query()
            ->hostelRooms()
            ->where('location_id', $locationId)
            ->orderBy('id')
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();
    }

    /**
     * Beds a group already uses on a hostel during the window.
     *
     * @param  list<string>|null  $statuses
     */
    public function bookedBeds(int $resourceId, string $group, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $ignoreReservationId = null, ?array $statuses = null): int
    {
        return (int) round((float) ReservationItem::query()
            ->where('resource_id', $resourceId)
            ->whereIn('status', $statuses ?? ReservationStatus::blockingItemStatuses())
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoreReservationId, fn ($query) => $query->where('reservation_id', '!=', $ignoreReservationId))
            ->whereHas('reservation.hostelStay', fn ($stay) => $stay->where('bed_group', $group))
            ->sum('quantity'));
    }

    /**
     * Reserves beds for the guests at a location. Call inside a transaction: the hostel row is locked.
     *
     * @return array<int, int> resource_id => beds
     */
    public function allocate(int $locationId, string $group, int $guests, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $ignoreReservationId = null): array
    {
        $label = $this->groups()[$group]['label'] ?? $group;
        $hostel = $this->hostelAt($locationId, true);
        $capacity = $hostel?->bedsFor($group) ?? 0;

        if (! $hostel || $capacity === 0) {
            throw ValidationException::withMessages([
                'bed_group' => "No {$label} beds are set up at this location.",
            ]);
        }

        $free = max(0, $capacity - $this->bookedBeds($hostel->id, $group, $startsAt, $endsAt, $ignoreReservationId));
        if ($free < $guests) {
            throw new ReservationConflictException($free === 0
                ? "All {$label} beds are booked at this location for the selected dates."
                : "Only {$free} {$label} ".($free === 1 ? 'bed is' : 'beds are').' free at this location for the selected dates.');
        }

        return [$hostel->id => $guests];
    }

    /**
     * Confirmed beds plus this booking must still fit the group.
     *
     * @param  Collection<int, ReservationItem>  $items
     * @param  list<string>  $confirmedStatuses
     */
    public function assertConfirmable(Collection $items, int $reservationId, ?string $group, array $confirmedStatuses): void
    {
        if (! $group) {
            return;
        }
        $label = $this->groups()[$group]['label'] ?? $group;

        foreach ($items as $item) {
            $hostel = Resource::query()->whereKey($item->resource_id)->lockForUpdate()->first();
            if (! $hostel || ! $hostel->bed_counts) {
                continue;
            }
            $taken = $this->bookedBeds($hostel->id, $group, $item->starts_at, $item->ends_at, $reservationId, $confirmedStatuses);
            if ($taken + (int) round((float) $item->quantity) > $hostel->bedsFor($group)) {
                throw new ReservationConflictException("Not enough {$label} beds are left at {$hostel->location?->name} for these dates.");
            }
        }
    }

    /**
     * Bed occupancy for one day, used by the bus-style bed map.
     *
     * @return list<array{location:string, total:int, groups: list<array{key:string, label:string, short:string, capacity:int, occupied:int, pending:int, available:int}>}>
     */
    public function bedMap(CarbonInterface $day): array
    {
        $timezone = config('reservations.display_timezone', 'Asia/Colombo');
        $start = Carbon::parse($day->toDateString(), $timezone)->startOfDay()->utc();
        $end = $start->copy()->addDay();

        $hostels = Resource::query()
            ->hostelRooms()
            ->whereHas('location', fn ($query) => $query->whereIn('name', config('resources.locations', [])))
            ->with('location')
            ->get()
            ->sortBy(fn (Resource $hostel) => array_search($hostel->location?->name, Location::CAMPUS_ORDER, true))
            ->values();

        return $hostels->map(function (Resource $hostel) use ($start, $end) {
            $groups = collect($this->groups())->map(function (array $settings, string $key) use ($hostel, $start, $end) {
                $capacity = $hostel->bedsFor($key);
                $occupied = min($capacity, $this->bookedBeds($hostel->id, $key, $start, $end, null, [ReservationItemStatus::CONFIRMED->value]));
                $pending = min($capacity - $occupied, $this->bookedBeds($hostel->id, $key, $start, $end, null, [
                    ReservationItemStatus::HELD->value,
                    ReservationItemStatus::REQUESTED->value,
                ]));

                return [
                    'key' => $key,
                    'label' => $settings['label'],
                    'short' => $settings['short'],
                    'capacity' => $capacity,
                    'occupied' => $occupied,
                    'pending' => $pending,
                    'available' => max(0, $capacity - $occupied - $pending),
                ];
            })->values()->all();

            return [
                'location' => $hostel->location?->name ?? $hostel->name_model,
                'total' => array_sum(array_column($groups, 'capacity')),
                'groups' => $groups,
            ];
        })->all();
    }
}
