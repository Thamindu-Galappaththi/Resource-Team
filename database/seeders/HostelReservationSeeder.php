<?php

namespace Database\Seeders;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\HostelReservationService;
use Illuminate\Database\Seeder;

class HostelReservationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(HostelRoomSeeder::class);

        $actor = User::query()->where('email', 'coordinator@nebula.local')->first()
            ?? User::query()->where('email', 'admin@nebula.local')->first()
            ?? User::query()->first();
        $manager = User::query()->where('email', 'hostel@nebula.local')->first()
            ?? $actor;

        if (! $actor) {
            return;
        }

        $categoryName = config('hostel.category_name', 'Hostel Room');
        $single = ResourceType::query()
            ->where('name', 'Single')
            ->whereHas('category', fn ($query) => $query->where('name', $categoryName))
            ->first();
        $double = ResourceType::query()
            ->where('name', 'Double')
            ->whereHas('category', fn ($query) => $query->where('name', $categoryName))
            ->first();
        $welisara = Location::query()->where('name', 'Welisara')->first();
        $moratuwa = Location::query()->where('name', 'Moratuwa')->first();

        if (! $single || ! $double || ! $welisara || ! $moratuwa) {
            return;
        }

        $service = app(HostelReservationService::class);
        $slots = [
            ['room_type_id' => $single->id, 'location_id' => $welisara->id, 'number_of_guests' => 1],
            ['room_type_id' => $single->id, 'location_id' => $welisara->id, 'number_of_guests' => 1],
            ['room_type_id' => $double->id, 'location_id' => $welisara->id, 'number_of_guests' => 2],
            ['room_type_id' => $single->id, 'location_id' => $moratuwa->id, 'number_of_guests' => 1],
        ];
        $outcomes = [
            null,
            ReservationStatus::APPROVED->value,
            ReservationStatus::CANCELLED->value,
            ReservationStatus::REJECTED->value,
        ];
        $guests = [
            ['Kasun Madushanka', 'Summer Internship 2026 Group', '+94 77 123 4567', 'Ground floor if possible'],
            ['Amali Fernando', 'Conference stay', '+94 71 555 0198', null],
            ['Nimal Perera', 'Moratuwa site visit lodging', '+94 76 222 1188', 'Late check-in after 20:00'],
            ['Dr. Ishara Jayasinghe', 'Guest lecturer accommodation', '+94 77 888 4400', 'Quiet room near the lecture halls'],
            ['Tharindu Silva', 'Workshop stay', '+94 75 111 2233', null],
            ['Sanduni Wijesinghe', 'Overflow request', '+94 70 444 5566', null],
            ['Ruwan Dissanayake', 'Audit team lodging', '+94 77 321 0987', 'Twin beds if available'],
            ['Chathurika Perera', 'Orientation week stay', '+94 71 909 1122', null],
            ['Malith Bandara', 'Vendor visit lodging', '+94 76 654 3210', null],
            ['Harini Gunasekara', 'Research attachment stay', '+94 77 210 3344', 'Ground floor access'],
            ['Pradeep Fernando', 'Sports meet lodging', '+94 75 888 0011', null],
            ['Nadeesha Jayawardena', 'Training cohort stay', '+94 70 123 7788', null],
            ['Isuru Rathnayake', 'Maintenance contractor stay', '+94 77 456 0099', null],
            ['Kavindi Silva', 'External examiner stay', '+94 71 333 2211', 'Early check-in if possible'],
        ];

        foreach ($guests as $index => $guest) {
            [$guestName, $title, $phone, $requirements] = $guest;
            $slot = $slots[$index % count($slots)];
            $batch = intdiv($index, count($slots));
            $checkIn = now()->addDays(40 + ($batch * 3));

            $stay = [
                'reservation_name' => $title,
                'guest_name' => $guestName,
                'guest_phone' => $phone,
                'check_in_date' => $checkIn->toDateString(),
                'check_out_date' => $checkIn->copy()->addDays(2)->toDateString(),
                'room_type_id' => $slot['room_type_id'],
                'location_id' => $slot['location_id'],
                'number_of_guests' => $slot['number_of_guests'],
                'special_requirements' => $requirements,
            ];
            $after = $outcomes[$index % count($outcomes)];

            if (Reservation::query()
                ->where('type', ReservationType::HOSTEL->value)
                ->where('title', $stay['reservation_name'])
                ->exists()) {
                continue;
            }

            try {
                $reservation = $service->create($stay, $actor);
            } catch (\Throwable) {
                continue;
            }

            if (! $manager || ! $after) {
                continue;
            }

            try {
                match ($after) {
                    ReservationStatus::APPROVED->value => $service->updateApproval(
                        $reservation,
                        $manager,
                        ReservationStatus::APPROVED->value,
                    ),
                    ReservationStatus::REJECTED->value => $service->updateApproval(
                        $reservation,
                        $manager,
                        ReservationStatus::REJECTED->value,
                        'Room mix does not match the requested stay.',
                    ),
                    ReservationStatus::CANCELLED->value => $service->cancel(
                        $reservation,
                        $actor,
                        'Stay no longer required.',
                    ),
                    default => null,
                };
            } catch (\Throwable) {
                // Safe to re-run: overlapping or already-processed sample rows are ignored.
            }
        }
    }
}
