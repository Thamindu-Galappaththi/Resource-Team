<?php

namespace App\Services;

use App\Enums\ReservationItemStatus;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Exceptions\ReservationConflictException;
use App\Models\HostelStayDetail;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pattern for intern-owned modules (canteen, lecture, events):
 * 1. Reuse ReservationBookingService — never a second overlap engine.
 * 2. Set reservations.type and write a 1:1 details table (this file: hostel_stay_details).
 * 3. Book ordinary resources (here: category "Hostel Room"; form "room category" = ResourceType).
 * 4. Add Policy methods, FormRequest, Feature tests that prove reuse + RBAC.
 */
class HostelReservationService
{
    public function __construct(private readonly ReservationBookingService $bookings) {}

    /**
     * @param  array{reservation_name:string,guest_name:string,check_in_date:string,check_out_date:string,room_type_id:int,location_id:int,number_of_guests:int,special_requirements?:string|null,requester_id?:int}  $payload
     */
    public function create(array $payload, User $actor): Reservation
    {
        $roomType = ResourceType::query()->with('category')->find($payload['room_type_id']);

        if (! $roomType || $roomType->category?->name !== config('hostel.category_name', 'Hostel Room')) {
            throw ValidationException::withMessages([
                'room_type_id' => 'Select a valid hostel room category.',
            ]);
        }

        [$startsAt, $endsAt] = $this->stayRange($payload['check_in_date'], $payload['check_out_date']);

        return DB::transaction(function () use ($payload, $actor, $roomType, $startsAt, $endsAt) {
            $room = $this->firstAvailableRoom(
                (int) $payload['room_type_id'],
                (int) $payload['location_id'],
                $startsAt,
                $endsAt,
            );

            $reservation = $this->bookings->create([
                'resource_ids' => [$room->id],
                'requester_id' => $payload['requester_id'] ?? $actor->id,
                'location_id' => $payload['location_id'],
                'type' => ReservationType::HOSTEL->value,
                'title' => $payload['reservation_name'],
                'purpose' => 'Hostel stay for '.$payload['guest_name'],
                'attendee_count' => $payload['number_of_guests'],
                'reservation_date' => $payload['check_in_date'],
                'end_date' => $payload['check_out_date'],
                'start_time' => config('hostel.check_in_time', '11:00'),
                'end_time' => config('hostel.check_out_time', '24:00'),
            ], $actor);

            HostelStayDetail::query()->create([
                'reservation_id' => $reservation->id,
                'guest_name' => $payload['guest_name'],
                'guest_identity_type' => filled($payload['guest_identity_number'] ?? null) ? 'id_number' : null,
                'guest_identity_encrypted' => filled($payload['guest_identity_number'] ?? null)
                    ? Crypt::encryptString($payload['guest_identity_number'])
                    : null,
                'guest_phone_encrypted' => filled($payload['guest_phone'] ?? null)
                    ? Crypt::encryptString($payload['guest_phone'])
                    : null,
                'check_in_at' => $startsAt,
                'check_out_at' => $endsAt,
                'room_type_id' => $roomType->id,
                'room_category_id' => $roomType->resource_category_id,
                'number_of_guests' => $payload['number_of_guests'],
                'special_requirements' => $payload['special_requirements'] ?? null,
            ]);

            return $reservation->load(['hostelStay.roomType', 'items', 'location', 'requester']);
        });
    }

    public function cancel(Reservation $reservation, User $actor, string $reason): Reservation
    {
        return $this->bookings->cancel($reservation, $actor, $reason);
    }

    public function updateApproval(Reservation $reservation, User $actor, string $status, ?string $reason = null): Reservation
    {
        if (! in_array($status, [ReservationStatus::APPROVED->value, ReservationStatus::REJECTED->value], true)) {
            throw ValidationException::withMessages([
                'status' => 'Select a valid approval action.',
            ]);
        }

        return DB::transaction(function () use ($reservation, $actor, $status, $reason) {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if ($reservation->status !== ReservationStatus::PENDING_APPROVAL->value) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending hostel reservations can be approved or rejected.',
                ]);
            }

            if ($this->checkInHasPassed($reservation)) {
                $this->expirePendingReservation($reservation);

                return $reservation->load(['hostelStay.roomType', 'items', 'location', 'requester']);
            }

            $items = $reservation->items()->orderBy('resource_id')->lockForUpdate()->get();
            if ($status === ReservationStatus::APPROVED->value) {
                foreach ($items as $item) {
                    $this->bookings->lockResourcesAndAssertNoOverlap(
                        [$item->resource_id],
                        $item->starts_at,
                        $item->ends_at,
                        $reservation->id,
                        [ReservationItemStatus::CONFIRMED->value],
                    );
                }
            }

            $from = $reservation->status;
            $reservation->update([
                'status' => $status,
                'approved_at' => $status === ReservationStatus::APPROVED->value ? now() : null,
            ]);

            $reservation->items()
                ->whereIn('status', [ReservationItemStatus::HELD->value, ReservationItemStatus::CONFIRMED->value])
                ->update([
                    'status' => $status === ReservationStatus::APPROVED->value
                        ? ReservationItemStatus::CONFIRMED->value
                        : ReservationItemStatus::CANCELLED->value,
                ]);

            ReservationStatusHistory::query()->create([
                'reservation_id' => $reservation->id,
                'from_status' => $from,
                'to_status' => $status,
                'actor_id' => $actor->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            return $reservation->load(['hostelStay.roomType', 'items', 'location', 'requester']);
        });
    }

    public function completePastApprovedReservations(): int
    {
        $completedCount = 0;

        Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->where('status', ReservationStatus::APPROVED->value)
            ->whereHas('hostelStay', fn ($query) => $query->where('check_out_at', '<=', now('UTC')))
            ->select('id')
            ->chunkById(100, function ($reservations) use (&$completedCount): void {
                foreach ($reservations as $reservation) {
                    if ($this->completeApprovedAtCheckout($reservation->id)) {
                        $completedCount++;
                    }
                }
            });

        return $completedCount;
    }

    public function completeApprovedAtCheckout(int $reservationId): bool
    {
        return DB::transaction(function () use ($reservationId): bool {
            $reservation = Reservation::query()
                ->whereKey($reservationId)
                ->where('type', ReservationType::HOSTEL->value)
                ->where('status', ReservationStatus::APPROVED->value)
                ->lockForUpdate()
                ->first();

            $checkOutAt = $reservation?->hostelStay()->first()?->check_out_at;
            if (! $checkOutAt || $checkOutAt->greaterThan(now('UTC'))) {
                return false;
            }

            $reservation->update(['status' => ReservationStatus::COMPLETED->value]);
            ReservationStatusHistory::query()->create([
                'reservation_id' => $reservation->id,
                'from_status' => ReservationStatus::APPROVED->value,
                'to_status' => ReservationStatus::COMPLETED->value,
                'actor_id' => null,
                'reason' => 'Automatically completed at the scheduled check-out time.',
                'created_at' => now(),
            ]);

            return true;
        });
    }

    public function expireOverduePendingReservations(): int
    {
        $expiredCount = 0;

        Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->where('status', ReservationStatus::PENDING_APPROVAL->value)
            ->whereHas('hostelStay', fn ($query) => $query->where('check_in_at', '<=', now('UTC')))
            ->select('id')
            ->chunkById(100, function ($reservations) use (&$expiredCount): void {
                foreach ($reservations as $reservation) {
                    if ($this->expirePendingAtCheckIn($reservation->id)) {
                        $expiredCount++;
                    }
                }
            });

        return $expiredCount;
    }

    public function expirePendingAtCheckIn(int $reservationId): bool
    {
        return DB::transaction(function () use ($reservationId): bool {
            $reservation = Reservation::query()
                ->whereKey($reservationId)
                ->where('type', ReservationType::HOSTEL->value)
                ->where('status', ReservationStatus::PENDING_APPROVAL->value)
                ->lockForUpdate()
                ->first();

            if (! $reservation || ! $this->checkInHasPassed($reservation)) {
                return false;
            }

            $this->expirePendingReservation($reservation);

            return true;
        });
    }

    private function checkInHasPassed(Reservation $reservation): bool
    {
        $checkInAt = $reservation->hostelStay()->first()?->check_in_at;

        return $checkInAt !== null && $checkInAt->lessThanOrEqualTo(now());
    }

    private function expirePendingReservation(Reservation $reservation): void
    {
        $reservation->update(['status' => ReservationStatus::EXPIRED->value]);
        $reservation->items()
            ->where('status', ReservationItemStatus::HELD->value)
            ->update(['status' => ReservationItemStatus::CANCELLED->value]);

        ReservationStatusHistory::query()->create([
            'reservation_id' => $reservation->id,
            'from_status' => ReservationStatus::PENDING_APPROVAL->value,
            'to_status' => ReservationStatus::EXPIRED->value,
            'actor_id' => null,
            'reason' => 'Automatically expired because approval was still pending at the scheduled check-in time.',
            'created_at' => now(),
        ]);
    }

    public function stayRange(string $checkInDate, string $checkOutDate): array
    {
        try {
            return $this->bookings->utcDateTimeRange(
                $checkInDate,
                config('hostel.check_in_time', '11:00'),
                $checkOutDate,
                config('hostel.check_out_time', '24:00'),
            );
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                'check_out_date' => 'Check-out must be after check-in.',
            ]);
        }
    }

    public function firstAvailableRoom(int $roomTypeId, int $locationId, $startsAt, $endsAt): Resource
    {
        $rooms = Resource::query()
            ->hostelRooms()
            ->where('resource_type_id', $roomTypeId)
            ->where('location_id', $locationId)
            ->orderBy('name_model')
            ->lockForUpdate()
            ->get();

        if ($rooms->isEmpty()) {
            throw ValidationException::withMessages([
                'room_type_id' => 'No rooms of this category exist at the selected location.',
            ]);
        }

        foreach ($rooms as $room) {
            if (! $this->bookings->hasConflict([$room->id], $startsAt, $endsAt)) {
                return $room;
            }
        }

        throw new ReservationConflictException('No room is available for the selected category and dates.');
    }
}
