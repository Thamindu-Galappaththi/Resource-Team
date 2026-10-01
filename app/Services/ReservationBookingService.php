<?php

namespace App\Services;

use App\Enums\ReservationItemStatus;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Exceptions\ReservationConflictException;
use App\Models\BookingLockDay;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\ReservationStatusHistory;
use App\Models\Resource;
use App\Models\User;
use App\Notifications\NewReservationPending;
use App\Notifications\ReservationCancelled;
use App\Notifications\ReservationCreated;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationBookingService
{
    public function timezone(): string
    {
        return config('reservations.display_timezone', 'Asia/Colombo');
    }

    /**
     * @param  array{reservation_date:string,start_time:string,end_time:string,resource_ids:array<int,int|string>,purpose:string,title?:string,attendee_count?:int|null,requester_id?:int,location_id?:int|null}  $payload
     */
    public function create(array $payload, User $actor): Reservation
    {
        if (! empty($payload['end_date'])) {
            [$startsAt, $endsAt] = $this->utcDateTimeRange(
                $payload['reservation_date'],
                $payload['start_time'],
                $payload['end_date'],
                $payload['end_time'],
            );
        } else {
            [$startsAt, $endsAt] = $this->utcRange(
                $payload['reservation_date'],
                $payload['start_time'],
                $payload['end_time'],
            );
        }

        $resourceIds = collect($payload['resource_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($resourceIds->isEmpty()) {
            throw ValidationException::withMessages([
                'resource_id' => 'Select a resource.',
            ]);
        }

        return DB::transaction(function () use ($payload, $actor, $startsAt, $endsAt, $resourceIds) {
            $resources = Resource::query()
                ->with('location')
                ->whereIn('id', $resourceIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($resources->count() !== $resourceIds->count()) {
                throw ValidationException::withMessages([
                    'resource_id' => 'One or more selected resources could not be found.',
                ]);
            }

            foreach ($resources as $resource) {
                if ($resource->status !== 'active') {
                    throw ValidationException::withMessages([
                        'resource_id' => $resource->name_model.' is not available for booking.',
                    ]);
                }
            }

            $this->lockDaysAndAssertNoOverlap($resourceIds, $startsAt, $endsAt);

            $primary = $resources[$resourceIds->first()];
            $locationId = $payload['location_id'] ?? $primary->location_id;
            $purpose = $payload['purpose'];
            $title = $payload['title'] ?? $purpose;

            $reservation = Reservation::query()->create([
                'requester_id' => $payload['requester_id'] ?? $actor->id,
                'created_by_user_id' => $actor->id,
                'location_id' => $locationId,
                'type' => $payload['type'] ?? ReservationType::STANDARD->value,
                'title' => $title,
                'description' => $payload['description'] ?? null,
                'purpose' => $purpose,
                'attendee_count' => $payload['attendee_count'] ?? null,
                'reservation_date' => $payload['reservation_date'],
                'start_time' => Carbon::parse($payload['start_time'])->format('H:i:s'),
                'end_time' => Carbon::parse($payload['end_time'])->format('H:i:s'),
                'status' => ReservationStatus::PENDING_APPROVAL->value,
                'submitted_at' => now(),
            ]);

            foreach ($resourceIds as $resourceId) {
                $resource = $resources[$resourceId];
                ReservationItem::query()->create([
                    'reservation_id' => $reservation->id,
                    'resource_id' => $resource->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => ReservationItemStatus::HELD->value,
                    'quantity' => 1,
                    'resource_name_snapshot' => $resource->name_model,
                ]);
            }

            $this->recordHistory($reservation, null, ReservationStatus::DRAFT->value, $actor, 'created');
            $this->recordHistory($reservation, ReservationStatus::DRAFT->value, ReservationStatus::PENDING_APPROVAL->value, $actor, 'submitted');

            $reservation->load(['requester', 'items', 'location']);
            $reservation->requester?->notify(new ReservationCreated($reservation));

            $approverRoles = ['super_admin', 'admin', 'coordinator'];
            if (($payload['type'] ?? ReservationType::STANDARD->value) === ReservationType::HOSTEL->value) {
                $approverRoles[] = 'hostel_manager';
            }

            User::query()
                ->whereHas('role', fn ($query) => $query->whereIn('slug', $approverRoles))
                ->whereKeyNot($reservation->requester_id)
                ->get()
                ->each(fn (User $approver) => $approver->notify(new NewReservationPending($reservation)));

            return $reservation;
        });
    }

    public function cancel(Reservation $reservation, User $actor, string $reason): Reservation
    {
        if (! $reservation->canBeCancelled()) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'This reservation cannot be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($reservation, $actor, $reason) {
            $from = $reservation->status;

            $reservation->update([
                'status' => ReservationStatus::CANCELLED->value,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $reservation->items()
                ->whereIn('status', [
                    ReservationItemStatus::REQUESTED->value,
                    ReservationItemStatus::HELD->value,
                    ReservationItemStatus::CONFIRMED->value,
                ])
                ->update(['status' => ReservationItemStatus::CANCELLED->value]);

            $this->recordHistory($reservation, $from, ReservationStatus::CANCELLED->value, $actor, $reason);

            $reservation->load(['requester', 'items', 'location']);
            $reservation->requester?->notify(new ReservationCancelled($reservation));

            return $reservation;
        });
    }

    public function hasConflict(Collection|array $resourceIds, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $ignoreReservationId = null): bool
    {
        return $this->conflictingItems($resourceIds, $startsAt, $endsAt, $ignoreReservationId)->isNotEmpty();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function utcRange(string $date, string $startTime, string $endTime): array
    {
        return $this->utcDateTimeRange($date, $startTime, $date, $endTime);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function utcDateTimeRange(string $startDate, string $startTime, string $endDate, string $endTime): array
    {
        $timezone = $this->timezone();
        $startsAt = Carbon::parse($startDate.' '.$startTime, $timezone)->utc();
        $endsAt = Carbon::parse($endDate.' '.$endTime, $timezone)->utc();

        if (! $startsAt->lt($endsAt)) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        return [$startsAt, $endsAt];
    }

    private function lockDaysAndAssertNoOverlap(Collection $resourceIds, CarbonInterface $startsAt, CarbonInterface $endsAt): void
    {
        $pairs = $this->lockPairs($resourceIds, $startsAt, $endsAt);
        if ($pairs === []) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        $now = now();
        $rows = array_map(function (array $pair) use ($now) {
            return [
                'resource_id' => $pair['resource_id'],
                'lock_date' => $pair['lock_date'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $pairs);

        BookingLockDay::query()->upsert(
            $rows,
            ['resource_id', 'lock_date'],
            ['updated_at']
        );

        $lockQuery = BookingLockDay::query();
        $lockQuery->where(function ($query) use ($pairs) {
            foreach ($pairs as $pair) {
                $query->orWhere(function ($inner) use ($pair) {
                    $inner->where('resource_id', $pair['resource_id'])
                        ->whereDate('lock_date', $pair['lock_date']);
                });
            }
        });

        $lockQuery->orderBy('resource_id')->orderBy('lock_date')->lockForUpdate()->get();

        $conflicts = $this->conflictingItems($resourceIds, $startsAt, $endsAt);
        if ($conflicts->isNotEmpty()) {
            throw new ReservationConflictException('Selected slot unavailable.');
        }
    }

    /**
     * @return list<array{resource_id:int, lock_date:string}>
     */
    private function lockPairs(Collection $resourceIds, CarbonInterface $startsAt, CarbonInterface $endsAt): array
    {
        $timezone = $this->timezone();
        $pairs = [];

        $localStart = Carbon::instance($startsAt)->timezone($timezone);
        $localEnd = Carbon::instance($endsAt)->timezone($timezone);
        $firstDay = $localStart->copy()->startOfDay();
        $lastDay = $localEnd->copy()->startOfDay();

        if ($localEnd->equalTo($lastDay)) {
            $lastDay->subDay();
        }

        foreach ($resourceIds->sort()->values() as $resourceId) {
            $cursor = $firstDay->copy();
            while ($cursor->lte($lastDay)) {
                $pairs[] = [
                    'resource_id' => (int) $resourceId,
                    'lock_date' => $cursor->toDateString(),
                ];
                $cursor->addDay();
            }
        }

        usort($pairs, function (array $a, array $b) {
            return [$a['resource_id'], $a['lock_date']] <=> [$b['resource_id'], $b['lock_date']];
        });

        return $pairs;
    }

    private function conflictingItems(Collection|array $resourceIds, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $ignoreReservationId = null): Collection
    {
        $ids = collect($resourceIds)->map(fn ($id) => (int) $id)->unique()->values();

        return ReservationItem::query()
            ->whereIn('resource_id', $ids)
            ->whereIn('status', ReservationStatus::blockingItemStatuses())
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoreReservationId, fn ($query) => $query->where('reservation_id', '!=', $ignoreReservationId))
            ->get();
    }

    private function recordHistory(Reservation $reservation, ?string $from, string $to, User $actor, ?string $reason): void
    {
        ReservationStatusHistory::query()->create([
            'reservation_id' => $reservation->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor->id,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
