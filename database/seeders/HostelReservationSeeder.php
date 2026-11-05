<?php

namespace Database\Seeders;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\Location;
use App\Models\Reservation;
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

        $welisara = Location::query()->where('name', 'Welisara')->first();
        $moratuwa = Location::query()->where('name', 'Moratuwa')->first();

        if (! $welisara || ! $moratuwa) {
            return;
        }

        $service = app(HostelReservationService::class);
        $slots = [
            ['location_id' => $welisara->id, 'number_of_guests' => 1, 'bed_group' => 'male'],
            ['location_id' => $welisara->id, 'number_of_guests' => 1, 'bed_group' => 'female'],
            ['location_id' => $welisara->id, 'number_of_guests' => 2, 'bed_group' => 'female'],
            ['location_id' => $moratuwa->id, 'number_of_guests' => 1, 'bed_group' => 'permanent'],
        ];
        $outcomes = [
            null,
            ReservationStatus::APPROVED->value,
            ReservationStatus::CANCELLED->value,
            ReservationStatus::REJECTED->value,
        ];
        $guests = [
            ['Mr.', 'Kasun Madushanka', '+94 77 123 4567', 'Ground floor if possible'],
            ['Ms.', 'Amali Fernando', '+94 71 555 0198', null],
            ['Mr.', 'Nimal Perera', '+94 76 222 1188', 'Late check-in after 20:00'],
            ['Dr.', 'Ishara Jayasinghe', '+94 77 888 4400', 'Quiet room near the lecture halls'],
            ['Mr.', 'Tharindu Silva', '+94 75 111 2233', null],
            ['Miss', 'Sanduni Wijesinghe', '+94 70 444 5566', null],
            ['Mr.', 'Ruwan Dissanayake', '+94 77 321 0987', 'Twin beds if available'],
            ['Mrs.', 'Chathurika Perera', '+94 71 909 1122', null],
            ['Mr.', 'Malith Bandara', '+94 76 654 3210', null],
            ['Ms.', 'Harini Gunasekara', '+94 77 210 3344', 'Ground floor access'],
            ['Mr.', 'Pradeep Fernando', '+94 75 888 0011', null],
            ['Mrs.', 'Nadeesha Jayawardena', '+94 70 123 7788', null],
            ['Mr.', 'Isuru Rathnayake', '+94 77 456 0099', null],
            ['Prof.', 'Kavindi Silva', '+94 71 333 2211', 'Early check-in if possible'],
        ];

        foreach ($guests as $index => $guest) {
            [$guestTitle, $guestName, $phone, $requirements] = $guest;
            $slot = $slots[$index % count($slots)];
            $batch = intdiv($index, count($slots));
            $checkIn = now()->addDays(40 + ($batch * 3));

            $stay = [
                'guest_title' => $guestTitle,
                'guest_name' => $guestName,
                'employee_ids' => array_map(
                    fn (int $guestNumber) => sprintf('EMP-%03d%d', $index + 1, $guestNumber),
                    range(1, $slot['number_of_guests']),
                ),
                'guest_phone' => $phone,
                'check_in_date' => $checkIn->toDateString(),
                'check_in_time' => '11:00',
                'check_out_date' => $checkIn->copy()->addDays(2)->toDateString(),
                'check_out_time' => '10:00',
                'location_id' => $slot['location_id'],
                'number_of_guests' => $slot['number_of_guests'],
                'bed_group' => $slot['bed_group'],
                'special_requirements' => $requirements,
            ];
            $after = $outcomes[$index % count($outcomes)];

            $existing = Reservation::query()
                ->where('type', ReservationType::HOSTEL->value)
                ->whereHas('hostelStay', fn ($query) => $query->where('guest_name', $guestName))
                ->first();
            if ($existing) {
                $existing->hostelStay()->update(['bed_group' => $slot['bed_group']]);

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
