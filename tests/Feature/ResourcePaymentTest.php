<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Resource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourcePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_reference_lookup_returns_base_and_extra_fee_breakdown(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['hourly_rate_minor' => 12550]);

        $this->actingAs($user)
            ->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference, 'extra_hours' => 0.5]))
            ->assertOk()
            ->assertJsonPath('base_fee', 376.5)
            ->assertJsonPath('extra_hours', 0.5)
            ->assertJsonPath('extra_hourly_rate', 125.5)
            ->assertJsonPath('extra_hour_fee', 62.75)
            ->assertJsonPath('total', 439.25)
            ->assertJsonPath('payment_status', 'pending');
    }

    public function test_fee_without_extra_hours_returns_zero_extra_fee_and_rounds_half_up(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['hourly_rate_minor' => 10001], '09:00:00', '09:30:00');

        $this->actingAs($user)
            ->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference]))
            ->assertOk()
            ->assertJsonPath('base_fee', 50.01)
            ->assertJsonPath('extra_hours', 0)
            ->assertJsonPath('extra_hour_fee', 0)
            ->assertJsonPath('total', 50.01);
    }

    public function test_extra_hours_are_clamped_to_twenty_four_hours(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['hourly_rate_minor' => 100]);

        $this->actingAs($user)
            ->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference, 'extra_hours' => 24]))
            ->assertOk()
            ->assertJsonPath('extra_hours', 24)
            ->assertJsonPath('extra_hour_fee', 24);
    }

    public function test_reservation_without_extra_surcharge_returns_zero_extra_fee(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['amount_minor' => 25000]);

        $this->actingAs($user)
            ->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference, 'extra_hours' => 2]))
            ->assertOk()
            ->assertJsonPath('base_fee', 250)
            ->assertJsonPath('extra_hourly_rate', 0)
            ->assertJsonPath('extra_hour_fee', 0)
            ->assertJsonPath('total', 250);
    }

    public function test_lookup_validates_reference_format_and_reports_missing_reservation(): void
    {
        $user = User::factory()->role('admin')->create();

        $this->actingAs($user)
            ->getJson(route('payments.resources.lookup', ['reference' => 'not-a-reference']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reference');

        $this->actingAs($user)
            ->getJson(route('payments.resources.lookup', ['reference' => 'RRS-2026-999999']))
            ->assertNotFound()
            ->assertJsonPath('message', 'No payable reservation was found for that reference.');
    }

    public function test_print_invoice_uses_same_zero_extra_fee_fallback(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['amount_minor' => 25000]);

        $this->actingAs($user)
            ->get(route('payments.resources.print', ['reference' => $reservation->reference]))
            ->assertOk()
            ->assertSee('LKR 250.00')
            ->assertSee('LKR 0.00')
            ->assertSee('Pending');
    }

    private function reservationWithRate(array $snapshot, string $start = '09:00:00', string $end = '12:00:00'): Reservation
    {
        $reservation = Reservation::factory()->create([
            'reference' => 'RRS-2026-'.fake()->unique()->numerify('######'),
            'status' => 'confirmed',
            'start_time' => $start,
            'end_time' => $end,
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id,
            'resource_id' => Resource::factory()->create()->id,
            'starts_at' => $reservation->reservation_date->copy()->setTimeFromTimeString($start),
            'ends_at' => $reservation->reservation_date->copy()->setTimeFromTimeString($end),
            'rate_snapshot' => $snapshot,
        ]);

        return $reservation;
    }
}
