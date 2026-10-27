<?php

namespace Database\Seeders;

use App\Enums\CanteenReservationStatus;
use App\Enums\MealType;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\CanteenReservation;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\Role;
use App\Models\User;
use App\Services\HostelReservationService;
use App\Services\ReservationBookingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

class SltEmployeeReservationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LocationSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            HostelRoomSeeder::class,
            ResourceSeeder::class,
        ]);

        Notification::fake();

        $employee = User::query()->where('email', 'slt@nebula.local')->first();
        if (! $employee) {
            $this->command?->warn('SLT employee slt@nebula.local was not found.');

            return;
        }

        $colleague = $this->colleague();
        $approver = User::query()->where('email', 'admin@nebula.local')->first()
            ?? User::query()->where('email', 'hostel@nebula.local')->first()
            ?? $employee;
        $canteenApprover = User::query()->where('email', 'canteen@nebula.local')->first()
            ?? $approver;
        $today = now(config('reservations.display_timezone', 'Asia/Colombo'))->startOfDay();

        $this->seedResourceBookings($employee, $colleague, $today);
        $this->seedHostelBookings($employee, $colleague, $approver, $today);
        $this->seedCanteenBookings($employee, $colleague, $canteenApprover, $today);

        $this->command?->info('SLT demo bookings are ready for slt@nebula.local (Password@123).');
        $this->command?->info('Colleague bookings use slt.colleague@nebula.local to test the privilege notice.');
    }

    private function colleague(): User
    {
        $role = Role::query()->where('slug', 'slt_employee')->firstOrFail();

        return User::query()->updateOrCreate(
            ['email' => 'slt.colleague@nebula.local'],
            [
                'name' => 'SLT Colleague',
                'nic' => '199001010015',
                'phone' => '0770000015',
                'location' => 'Welisara',
                'password' => 'Password@123',
                'role_id' => $role->id,
                'user_role' => $role->slug,
                'is_active' => true,
                'slt_employee' => true,
                'password_setup_at' => now(),
            ]
        );
    }

    private function seedResourceBookings(User $employee, User $colleague, $today): void
    {
        $bookings = app(ReservationBookingService::class);
        $serials = [
            'LH-WEL-B-002', 'MR-WEL-101', 'LT-WEL-001', 'LAB-WEL-03', 'PJ-WEL-001',
            'LH-WEL-A-001', 'MR-MOR-201', 'LT-PER-001', 'PJ-MOR-001', 'LH-MOR-A-001',
            'LT-WEL-002',
        ];
        $purposes = ['Team briefing', 'Project sync', 'Site visit', 'Hands-on training', 'Department review'];
        $outcomes = [null, ReservationStatus::APPROVED->value, ReservationStatus::APPROVED->value, null, ReservationStatus::CANCELLED->value];
        $rows = [];

        for ($index = 1; $index <= 15; $index++) {
            $morning = $index % 2 === 1;
            $rows[] = [
                'owner' => $employee,
                'serial' => $serials[($index - 1) % count($serials)],
                'days' => 7 + $index,
                'start' => $morning ? '09:00' : '14:00',
                'end' => $morning ? '11:00' : '16:00',
                'title' => sprintf('SLT Demo: Resource request %02d', $index),
                'purpose' => $purposes[($index - 1) % count($purposes)],
                'after' => $outcomes[($index - 1) % count($outcomes)],
            ];
        }

        $rows[] = ['owner' => $colleague, 'serial' => 'LH-WEL-A-001', 'days' => 1, 'start' => '14:00', 'end' => '16:00', 'title' => 'SLT Demo: Colleague Hall A', 'purpose' => 'Other team workshop', 'after' => ReservationStatus::APPROVED->value];
        $rows[] = ['owner' => $colleague, 'serial' => 'LT-WEL-002', 'days' => 2, 'start' => '14:00', 'end' => '16:00', 'title' => 'SLT Demo: Colleague laptop', 'purpose' => 'Field work', 'after' => null];

        foreach ($rows as $row) {
            $resource = Resource::query()->where('serial_number', $row['serial'])->where('status', 'active')->first();
            if (! $resource) {
                $this->command?->warn('Skipped resource booking '.$row['title'].': '.$row['serial'].' is missing.');
                continue;
            }

            $reservation = $this->firstOrCreateResourceBooking($bookings, $row, $resource, $today);
            if (! $reservation) {
                continue;
            }

            $this->applyResourceOutcome($bookings, $reservation, $row['owner'], $row['after']);
        }
    }

    /**
     * @param  array{owner: User, start: string, end: string, title: string, purpose: string, days: int}  $row
     */
    private function firstOrCreateResourceBooking(
        ReservationBookingService $bookings,
        array $row,
        Resource $resource,
        $today,
    ): ?Reservation {
        $existing = Reservation::query()
            ->where('type', ReservationType::STANDARD->value)
            ->where('title', $row['title'])
            ->first();
        if ($existing) {
            return $existing;
        }

        try {
            return $bookings->create([
                'resource_ids' => [$resource->id],
                'requester_id' => $row['owner']->id,
                'location_id' => $resource->location_id,
                'reservation_date' => $today->copy()->addDays($row['days'])->toDateString(),
                'start_time' => $row['start'],
                'end_time' => $row['end'],
                'purpose' => $row['purpose'],
                'title' => $row['title'],
                'attendee_count' => 8,
            ], $row['owner']);
        } catch (\Throwable $exception) {
            $this->command?->warn('Skipped resource booking '.$row['title'].': '.$exception->getMessage());

            return null;
        }
    }

    private function applyResourceOutcome(
        ReservationBookingService $bookings,
        Reservation $reservation,
        User $owner,
        ?string $after,
    ): void {
        if ($after === ReservationStatus::APPROVED->value && $reservation->status === ReservationStatus::PENDING_APPROVAL->value) {
            $reservation->update([
                'status' => ReservationStatus::APPROVED->value,
                'approved_at' => now(),
            ]);
            $bookings->confirm($reservation);

            return;
        }

        if ($after === ReservationStatus::CANCELLED->value && $reservation->canBeCancelled()) {
            try {
                $bookings->cancel($reservation, $owner, 'Demo cancelled booking.');
            } catch (\Throwable $exception) {
                $this->command?->warn('Could not cancel '.$reservation->title.': '.$exception->getMessage());
            }
        }
    }

    private function seedHostelBookings(User $employee, User $colleague, User $approver, $today): void
    {
        $service = app(HostelReservationService::class);
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
            $this->command?->warn('Skipped hostel demo bookings: hostel rooms or locations are missing.');

            return;
        }

        $slots = [
            ['room_type_id' => $single->id, 'location_id' => $welisara->id, 'guests' => 1],
            ['room_type_id' => $single->id, 'location_id' => $welisara->id, 'guests' => 1],
            ['room_type_id' => $double->id, 'location_id' => $welisara->id, 'guests' => 2],
            ['room_type_id' => $single->id, 'location_id' => $moratuwa->id, 'guests' => 1],
        ];
        $outcomes = [null, ReservationStatus::APPROVED->value, ReservationStatus::APPROVED->value, null, ReservationStatus::CANCELLED->value];
        $rows = [];

        for ($index = 1; $index <= 15; $index++) {
            $slot = $slots[($index - 1) % count($slots)];
            $rows[] = [
                'owner' => $employee,
                'title' => sprintf('SLT Demo: Hostel stay %02d', $index),
                'guest' => sprintf('SLT Guest %02d', $index),
                'phone' => sprintf('+94 77 101 2%03d', $index),
                'days' => 55 + ($index - 1) * 3,
                'nights' => 2,
                'room_type_id' => $slot['room_type_id'],
                'location_id' => $slot['location_id'],
                'guests' => $slot['guests'],
                'after' => $outcomes[($index - 1) % count($outcomes)],
                'requirements' => $index % 4 === 1 ? 'Ground floor if possible' : null,
            ];
        }

        $rows[] = [
            'owner' => $colleague,
            'title' => 'SLT Demo: Colleague hostel stay',
            'guest' => 'Ruwan Dissanayake',
            'phone' => '+94 77 101 2015',
            'days' => 4,
            'nights' => 2,
            'room_type_id' => $single->id,
            'location_id' => $welisara->id,
            'guests' => 1,
            'after' => ReservationStatus::APPROVED->value,
            'requirements' => null,
        ];

        foreach ($rows as $row) {
            $reservation = $this->firstOrCreateHostelBooking($service, $row, $today);
            if (! $reservation) {
                continue;
            }

            if ($row['after'] === ReservationStatus::APPROVED->value && $reservation->status === ReservationStatus::PENDING_APPROVAL->value) {
                try {
                    $service->updateApproval($reservation, $approver, ReservationStatus::APPROVED->value);
                } catch (\Throwable $exception) {
                    $this->command?->warn('Could not approve '.$row['title'].': '.$exception->getMessage());
                }
            }

            if ($row['after'] === ReservationStatus::CANCELLED->value && $reservation->canBeCancelled()) {
                try {
                    $service->cancel($reservation, $row['owner'], 'Demo cancelled hostel stay.');
                } catch (\Throwable $exception) {
                    $this->command?->warn('Could not cancel '.$row['title'].': '.$exception->getMessage());
                }
            }
        }
    }

    /**
     * @param  array{owner: User, title: string, guest: string, phone: string, days: int, nights: int, room_type_id: int, location_id: int, guests: int, requirements: ?string}  $row
     */
    private function firstOrCreateHostelBooking(HostelReservationService $service, array $row, $today): ?Reservation
    {
        $existing = Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->where('title', $row['title'])
            ->first();
        if ($existing) {
            return $existing;
        }

        $checkIn = $today->copy()->addDays($row['days']);

        try {
            return $service->create([
                'reservation_name' => $row['title'],
                'guest_name' => $row['guest'],
                'guest_phone' => $row['phone'],
                'check_in_date' => $checkIn->toDateString(),
                'check_out_date' => $checkIn->copy()->addDays($row['nights'])->toDateString(),
                'room_type_id' => $row['room_type_id'],
                'location_id' => $row['location_id'],
                'number_of_guests' => $row['guests'],
                'special_requirements' => $row['requirements'],
            ], $row['owner']);
        } catch (\Throwable $exception) {
            $this->command?->warn('Skipped hostel booking '.$row['title'].': '.$exception->getMessage());

            return null;
        }
    }

    private function seedCanteenBookings(User $employee, User $colleague, User $approver, $today): void
    {
        $welisara = Location::query()->where('name', 'Welisara')->first();
        $meals = [
            [MealType::BREAKFAST, '08:15', 'String hoppers and tea'],
            [MealType::LUNCH, '12:30', 'Rice and curry, 4 vegetarian'],
            [MealType::SNACKS, '15:00', 'Short eats and juice'],
            [MealType::DINNER, '18:30', 'Rice, dhal, and fish'],
            [MealType::EVENT_CATERING, '10:00', 'Welcome pack and tea station'],
        ];
        $statuses = [
            CanteenReservationStatus::PENDING,
            CanteenReservationStatus::APPROVED,
            CanteenReservationStatus::APPROVED,
            CanteenReservationStatus::PENDING,
            CanteenReservationStatus::CANCELLED,
        ];
        $rows = [];

        for ($index = 1; $index <= 15; $index++) {
            [$meal, $time, $details] = $meals[($index - 1) % count($meals)];
            $rows[] = [
                'owner' => $employee,
                'name' => sprintf('SLT Demo: Canteen order %02d', $index),
                'meal' => $meal,
                'days' => $index,
                'time' => $time,
                'orders' => 8 + $index,
                'status' => $statuses[($index - 1) % count($statuses)],
                'details' => $details,
            ];
        }

        $rows[] = ['owner' => $colleague, 'name' => 'SLT Demo: Colleague lunch', 'meal' => MealType::LUNCH, 'days' => 1, 'time' => '12:45', 'orders' => 8, 'status' => CanteenReservationStatus::APPROVED, 'details' => 'Standard lunch set'];

        foreach ($rows as $row) {
            $reviewed = in_array($row['status'], [
                CanteenReservationStatus::APPROVED,
                CanteenReservationStatus::CONFIRMED,
                CanteenReservationStatus::REJECTED,
            ], true);

            CanteenReservation::query()->updateOrCreate(
                ['reservation_name' => $row['name']],
                [
                    'requested_by_user_id' => $row['owner']->id,
                    'location_id' => $welisara?->id,
                    'meal_type' => $row['meal']->value,
                    'reservation_date' => $today->copy()->addDays($row['days'])->toDateString(),
                    'reservation_time' => $row['time'],
                    'number_of_orders' => $row['orders'],
                    'order_details' => $row['details'],
                    'special_remarks' => $row['owner']->is($employee) ? 'Demo SLT canteen order.' : 'Colleague order for privilege checks.',
                    'status' => $row['status']->value,
                    'approved_by_user_id' => $reviewed ? $approver->id : null,
                    'approval_comments' => null,
                ]
            );
        }
    }
}
