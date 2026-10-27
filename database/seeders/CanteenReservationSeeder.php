<?php

namespace Database\Seeders;

use App\Enums\CanteenReservationStatus;
use App\Enums\MealType;
use App\Models\CanteenReservation;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class CanteenReservationSeeder extends Seeder
{
    public function run(): void
    {
        $requesters = User::query()
            ->whereIn('email', [
                'coordinator@nebula.local',
                'slt@nebula.local',
                'sms@nebula.local',
                'management@nebula.local',
            ])
            ->get();
        $approver = User::query()->where('email', 'canteen@nebula.local')->first()
            ?? User::query()->where('email', 'admin@nebula.local')->first();
        $locations = Location::query()->ordered()->get();

        if ($requesters->isEmpty()) {
            return;
        }

        $rows = [
            ['Engineering team lunch', MealType::LUNCH, 0, '12:30', 24, CanteenReservationStatus::PENDING, 'Rice and curry, 4 vegetarian'],
            ['Morning briefing breakfast', MealType::BREAKFAST, 0, '08:15', 12, CanteenReservationStatus::PENDING, 'String hoppers and tea'],
            ['Lab demo snacks', MealType::SNACKS, 1, '15:00', 18, CanteenReservationStatus::PENDING, 'Short eats and juice'],
            ['Guest lecture lunch', MealType::LUNCH, 1, '12:45', 40, CanteenReservationStatus::PENDING, 'Buffet for visiting staff'],
            ['Night shift dinner', MealType::DINNER, 2, '18:30', 16, CanteenReservationStatus::PENDING, 'Rice, dhal, and fish'],
            ['Orientation catering', MealType::EVENT_CATERING, 2, '10:00', 80, CanteenReservationStatus::PENDING, 'Welcome pack and tea station'],
            ['Audit team lunch', MealType::LUNCH, 3, '12:15', 10, CanteenReservationStatus::PENDING, 'Low-spice meals'],
            ['Workshop snacks', MealType::SNACKS, 4, '15:30', 22, CanteenReservationStatus::PENDING, 'Sandwiches and fruit'],
            ['Approved campus lunch', MealType::LUNCH, 0, '12:00', 30, CanteenReservationStatus::APPROVED, 'Standard lunch set'],
            ['Approved breakfast briefing', MealType::BREAKFAST, 1, '08:00', 8, CanteenReservationStatus::APPROVED, 'Tea and buns'],
            ['Approved dinner service', MealType::DINNER, 3, '19:00', 14, CanteenReservationStatus::APPROVED, 'Chicken rice packets'],
            ['Approved event catering', MealType::EVENT_CATERING, 5, '11:00', 60, CanteenReservationStatus::APPROVED, 'Seminar refreshments'],
            ['Approved snack break', MealType::SNACKS, 6, '15:15', 20, CanteenReservationStatus::APPROVED, 'Biscuits and cordial'],
            ['Confirmed leftover lunch', MealType::LUNCH, 2, '12:20', 15, CanteenReservationStatus::CONFIRMED, 'Legacy confirmed order'],
            ['Rejected oversized lunch', MealType::LUNCH, 4, '12:40', 120, CanteenReservationStatus::REJECTED, 'Capacity exceeded'],
            ['Rejected late breakfast', MealType::BREAKFAST, 5, '09:30', 6, CanteenReservationStatus::REJECTED, 'Requested after cutoff'],
            ['Rejected dinner change', MealType::DINNER, 6, '18:00', 9, CanteenReservationStatus::REJECTED, 'Menu unavailable'],
            ['Cancelled snacks', MealType::SNACKS, 2, '15:45', 11, CanteenReservationStatus::CANCELLED, 'Session postponed'],
            ['Cancelled catering', MealType::EVENT_CATERING, 7, '10:30', 45, CanteenReservationStatus::CANCELLED, 'Event moved off-site'],
        ];

        foreach ($rows as $index => $row) {
            [$name, $meal, $dayOffset, $time, $orders, $status, $details] = $row;
            $requester = $requesters[$index % $requesters->count()];
            $location = $locations->isNotEmpty() ? $locations[$index % $locations->count()] : null;
            $reviewed = in_array($status, [
                CanteenReservationStatus::APPROVED,
                CanteenReservationStatus::CONFIRMED,
                CanteenReservationStatus::REJECTED,
            ], true);

            CanteenReservation::query()->updateOrCreate(
                ['reservation_name' => $name],
                [
                    'requested_by_user_id' => $requester->id,
                    'location_id' => $location?->id,
                    'meal_type' => $meal->value,
                    'reservation_date' => now()->addDays($dayOffset)->toDateString(),
                    'reservation_time' => $time,
                    'number_of_orders' => $orders,
                    'order_details' => $details,
                    'special_remarks' => $index % 3 === 0 ? 'Please plate vegetarian items separately.' : null,
                    'status' => $status->value,
                    'approved_by_user_id' => $reviewed ? $approver?->id : null,
                    'approval_comments' => $status === CanteenReservationStatus::REJECTED
                        ? 'Cannot proceed with this request.'
                        : null,
                ]
            );
        }
    }
}
