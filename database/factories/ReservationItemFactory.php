<?php

namespace Database\Factories;

use App\Enums\ReservationItemStatus;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationItem>
 */
class ReservationItemFactory extends Factory
{
    protected $model = ReservationItem::class;

    public function definition(): array
    {
        $start = now()->addDay()->setTime(3, 30);
        $resource = Resource::factory();

        return [
            'reservation_id' => Reservation::factory(),
            'resource_id' => $resource,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(3),
            'status' => ReservationItemStatus::HELD->value,
            'quantity' => 1,
            'resource_name_snapshot' => 'Hall A',
        ];
    }
}
