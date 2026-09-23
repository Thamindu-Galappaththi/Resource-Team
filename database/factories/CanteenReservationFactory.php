<?php

namespace Database\Factories;

use App\Enums\CanteenReservationStatus;
use App\Enums\MealType;
use App\Models\CanteenReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CanteenReservation>
 */
class CanteenReservationFactory extends Factory
{
    protected $model = CanteenReservation::class;

    public function definition(): array
    {
        $date = now()->addDay();

        return [
            'reservation_ref' => CanteenReservation::generateReservationRef(),
            'reservation_name' => fake()->sentence(3),
            'requested_by_user_id' => User::factory(),
            'location_id' => null,
            'meal_type' => fake()->randomElement(MealType::values()),
            'reservation_date' => $date->toDateString(),
            'reservation_time' => fake()->time('H:i', '12:00'),
            'number_of_orders' => fake()->numberBetween(1, 25),
            'order_details' => fake()->sentence(6),
            'special_remarks' => fake()->optional()->sentence(),
            'status' => CanteenReservationStatus::PENDING->value,
            'approved_by_user_id' => null,
            'approval_comments' => null,
        ];
    }
}
