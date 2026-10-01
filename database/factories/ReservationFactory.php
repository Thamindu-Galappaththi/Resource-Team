<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $date = now()->addDay();

        return [
            'public_id' => (string) Str::ulid(),
            'reference' => 'RRS-'.$date->format('Y').'-'.fake()->unique()->numerify('######'),
            'requester_id' => User::factory(),
            'created_by_user_id' => null,
            'location_id' => null,
            'type' => ReservationType::STANDARD->value,
            'title' => 'CCNA Batch practical',
            'description' => null,
            'purpose' => 'CCNA Batch 12 practical lab',
            'attendee_count' => 24,
            'reservation_date' => $date->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'submitted_at' => now(),
            'version' => 1,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Reservation $reservation) {
            if (! $reservation->created_by_user_id && $reservation->requester_id) {
                $reservation->created_by_user_id = $reservation->requester_id;
            }
        })->afterCreating(function (Reservation $reservation) {
            if (! $reservation->created_by_user_id) {
                $reservation->update(['created_by_user_id' => $reservation->requester_id]);
            }
        });
    }
}
