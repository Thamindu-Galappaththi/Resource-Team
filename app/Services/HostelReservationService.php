<?php

namespace App\Services;

use App\Enums\ReservationType;
use App\Exceptions\ReservationConflictException;
use App\Models\HostelStayDetail;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
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
    public function __construct(private readonly ReservationBookingService $bookings)
    {
    }

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
                'start_time' => config('hostel.check_in_time', '14:00'),
                'end_time' => config('hostel.check_out_time', '11:00'),
            ], $actor);

            HostelStayDetail::query()->create([
                'reservation_id' => $reservation->id,
                'guest_name' => $payload['guest_name'],
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

    public function stayRange(string $checkInDate, string $checkOutDate): array
    {
        try {
            return $this->bookings->utcDateTimeRange(
                $checkInDate,
                config('hostel.check_in_time', '14:00'),
                $checkOutDate,
                config('hostel.check_out_time', '11:00'),
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
