<?php

namespace App\Services;

use App\Enums\ReservationItemStatus;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\HostelStayDetail;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pattern for intern-owned modules (canteen, lecture, events):
 * 1. Reuse ReservationBookingService — never a second overlap engine.
 * 2. Set reservations.type and write a 1:1 details table (this file: hostel_stay_details).
 * 3. Book ordinary resources (here: the location's "Hostel Room / Bed" record, quantity = beds in the guest's bed group).
 * 4. Add Policy methods, FormRequest, Feature tests that prove reuse + RBAC.
 */
class HostelReservationService
{
    public function __construct(
        private readonly ReservationBookingService $bookings,
        private readonly HostelBedCapacity $beds,
    ) {}

    /**
     * @param  array{guest_title:string,guest_name:string,employee_ids:array<int,string>,check_in_date:string,check_in_time:string,check_out_date:string,check_out_time:string,location_id:int,number_of_guests:int,bed_group:string,guest_phone?:string|null,special_requirements?:string|null,requester_id?:int,title?:string}  $payload
     */
    public function create(array $payload, User $actor): Reservation
    {
        [$startsAt, $endsAt] = $this->stayRange(
            $payload['check_in_date'],
            $payload['check_in_time'],
            $payload['check_out_date'],
            $payload['check_out_time'],
        );

        return DB::transaction(function () use ($payload, $actor, $startsAt, $endsAt) {
            $beds = $this->beds->allocate(
                (int) $payload['location_id'],
                $payload['bed_group'],
                (int) $payload['number_of_guests'],
                $startsAt,
                $endsAt,
            );
            $guest = $this->guestLabel($payload);

            $reservation = $this->bookings->create([
                'resource_ids' => array_keys($beds),
                'quantities' => $beds,
                'requester_id' => $payload['requester_id'] ?? $actor->id,
                'location_id' => $payload['location_id'],
                'type' => ReservationType::HOSTEL->value,
                'title' => $payload['title'] ?? 'Hostel stay for '.$guest,
                'purpose' => 'Hostel stay for '.$guest,
                'attendee_count' => $payload['number_of_guests'],
                'special_approval' => (bool) ($payload['special_approval'] ?? false),
                'reservation_date' => $payload['check_in_date'],
                'end_date' => $payload['check_out_date'],
                'start_time' => $payload['check_in_time'],
                'end_time' => $payload['check_out_time'],
            ], $actor);

            HostelStayDetail::query()->create([
                'reservation_id' => $reservation->id,
                'guest_title' => $payload['guest_title'],
                'guest_name' => $payload['guest_name'],
                'employee_ids' => array_values($payload['employee_ids']),
                'guest_phone_encrypted' => filled($payload['guest_phone'] ?? null)
                    ? Crypt::encryptString($payload['guest_phone'])
                    : null,
                'check_in_at' => $startsAt,
                'check_out_at' => $endsAt,
                'number_of_guests' => $payload['number_of_guests'],
                'bed_group' => $payload['bed_group'],
                'special_requirements' => $payload['special_requirements'] ?? null,
            ]);

            return $reservation->load(['hostelStay', 'items', 'location', 'requester']);
        });
    }

    public function updatePending(Reservation $reservation, array $payload, User $actor): Reservation
    {
        if ($reservation->type !== ReservationType::HOSTEL->value || ! $reservation->canBeEdited()) {
            throw ValidationException::withMessages([
                'guest_name' => 'This hostel reservation can no longer be edited.',
            ]);
        }

        [$startsAt, $endsAt] = $this->stayRange(
            $payload['check_in_date'],
            $payload['check_in_time'],
            $payload['check_out_date'],
            $payload['check_out_time'],
        );

        return DB::transaction(function () use ($payload, $actor, $startsAt, $endsAt, $reservation) {
            $beds = $this->beds->allocate(
                (int) $payload['location_id'],
                $payload['bed_group'],
                (int) $payload['number_of_guests'],
                $startsAt,
                $endsAt,
                $reservation->id,
            );
            $guest = $this->guestLabel($payload);

            $reservation = $this->bookings->updatePending($reservation, [
                'resource_ids' => array_keys($beds),
                'quantities' => $beds,
                'location_id' => $payload['location_id'],
                'title' => $payload['title'] ?? 'Hostel stay for '.$guest,
                'purpose' => 'Hostel stay for '.$guest,
                'attendee_count' => $payload['number_of_guests'],
                'reservation_date' => $payload['check_in_date'],
                'end_date' => $payload['check_out_date'],
                'start_time' => $payload['check_in_time'],
                'end_time' => $payload['check_out_time'],
            ] + (array_key_exists('special_approval', $payload) ? ['special_approval' => (bool) $payload['special_approval']] : []), $actor);

            $stay = $reservation->hostelStay;
            $stayPayload = [
                'guest_title' => $payload['guest_title'],
                'guest_name' => $payload['guest_name'],
                'employee_ids' => array_values($payload['employee_ids']),
                'check_in_at' => $startsAt,
                'check_out_at' => $endsAt,
                'number_of_guests' => $payload['number_of_guests'],
                'bed_group' => $payload['bed_group'],
                'special_requirements' => $payload['special_requirements'] ?? null,
            ];

            if (array_key_exists('guest_phone', $payload)) {
                $stayPayload['guest_phone_encrypted'] = filled($payload['guest_phone'])
                    ? Crypt::encryptString($payload['guest_phone'])
                    : null;
            }

            $stay?->update($stayPayload);

            return $reservation->load(['hostelStay', 'items', 'location', 'requester']);
        });
    }

    public function cancel(Reservation $reservation, User $actor, string $reason): Reservation
    {
        return $this->bookings->cancel($reservation, $actor, $reason);
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    public function updateApproval(Reservation $reservation, User $actor, string $status, ?string $reason = null, ?array $context = null): Reservation
    {
        if (! in_array($status, [ReservationStatus::APPROVED->value, ReservationStatus::REJECTED->value], true)) {
            throw ValidationException::withMessages([
                'status' => 'Select a valid approval action.',
            ]);
        }

        return DB::transaction(function () use ($reservation, $actor, $status, $reason, $context) {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if ($reservation->status !== ReservationStatus::PENDING_APPROVAL->value) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending hostel reservations can be approved or rejected.',
                ]);
            }

            if ($this->checkInHasPassed($reservation)) {
                $this->expirePendingReservation($reservation);

                return $reservation->load(['hostelStay', 'items', 'location', 'requester']);
            }

            $items = $reservation->items()->orderBy('resource_id')->lockForUpdate()->get();
            if ($status === ReservationStatus::APPROVED->value) {
                $this->beds->assertConfirmable(
                    $items,
                    $reservation->id,
                    $reservation->hostelStay()->value('bed_group'),
                    [ReservationItemStatus::CONFIRMED->value],
                );
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
                'context' => $context,
                'created_at' => now(),
            ]);

            return $reservation->load(['hostelStay', 'items', 'location', 'requester']);
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

    public function stayRange(string $checkInDate, string $checkInTime, string $checkOutDate, string $checkOutTime): array
    {
        try {
            [$startsAt, $endsAt] = $this->bookings->utcDateTimeRange($checkInDate, $checkInTime, $checkOutDate, $checkOutTime);
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                'check_out_date' => 'Check-out must be after check-in.',
            ]);
        }

        if ($startsAt->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'check_in_time' => 'Check-in must be in the future.',
            ]);
        }

        return [$startsAt, $endsAt];
    }

    private function guestLabel(array $payload): string
    {
        return trim(($payload['guest_title'] ?? '').' '.$payload['guest_name']);
    }
}
