<?php

namespace Database\Factories;

use App\Models\HostelStayDetail;
use App\Models\Reservation;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HostelStayDetail>
 */
class HostelStayDetailFactory extends Factory
{
    protected $model = HostelStayDetail::class;

    public function definition(): array
    {
        $checkIn = now()->addDay()->setTime(8, 30);

        return [
            'reservation_id' => Reservation::factory(),
            'guest_name' => fake()->name(),
            'check_in_at' => $checkIn,
            'check_out_at' => $checkIn->copy()->addDays(2)->setTime(5, 30),
            'room_type_id' => ResourceType::factory(),
            'room_category_id' => ResourceCategory::factory(),
            'number_of_guests' => 1,
            'special_requirements' => null,
        ];
    }
}
