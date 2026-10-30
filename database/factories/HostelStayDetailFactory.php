<?php

namespace Database\Factories;

use App\Models\HostelStayDetail;
use App\Models\Reservation;
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
            'guest_title' => 'Mr.',
            'guest_name' => fake()->name(),
            'employee_ids' => [fake()->unique()->bothify('EMP-####')],
            'check_in_at' => $checkIn,
            'check_out_at' => $checkIn->copy()->addDays(2)->setTime(5, 30),
            'number_of_guests' => 1,
            'special_requirements' => null,
        ];
    }
}
