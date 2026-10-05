<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Notifications\ReservationCreated;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReservationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_create_reservation_succeeds_and_submits_for_approval(): void
    {
        Notification::fake();

        $user = User::factory()->role('coordinator')->create();
        $resource = Resource::factory()->create(['name_model' => 'Hall A', 'status' => 'active']);

        $this->actingAs($user)->post(route('reservations.store'), $this->payload($resource, $user, [
            'purpose' => 'CCNA Batch 12 practical lab',
        ]))->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'requester_id' => $user->id,
            'purpose' => 'CCNA Batch 12 practical lab',
            'status' => ReservationStatus::PENDING_APPROVAL->value,
        ]);
        $this->assertDatabaseHas('reservation_items', [
            'resource_id' => $resource->id,
            'status' => 'held',
            'resource_name_snapshot' => 'Hall A',
        ]);
        $this->assertDatabaseHas('reservation_status_history', [
            'to_status' => ReservationStatus::PENDING_APPROVAL->value,
        ]);

        Notification::assertSentTo($user, ReservationCreated::class);
    }

    public function test_overlapping_booking_is_rejected(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $resource = Resource::factory()->create(['status' => 'active']);
        $payload = $this->payload($resource, $user, [
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($user)->post(route('reservations.store'), $payload)->assertRedirect();

        $this->actingAs($user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), $this->payload($resource, $user, [
                'start_time' => '10:00',
                'end_time' => '11:00',
                'purpose' => 'Overlapping attempt',
            ]))
            ->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_back_to_back_slots_do_not_conflict(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $resource = Resource::factory()->create(['status' => 'active']);

        $this->actingAs($user)->post(route('reservations.store'), $this->payload($resource, $user, [
            'start_time' => '09:00',
            'end_time' => '10:00',
            'purpose' => 'Morning session',
        ]))->assertRedirect();

        $this->actingAs($user)->post(route('reservations.store'), $this->payload($resource, $user, [
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Next session',
        ]))->assertRedirect();

        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_past_date_is_rejected(): void
    {
        $user = User::factory()->role('slt_employee')->create();
        $resource = Resource::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), $this->payload($resource, $user, [
                'reservation_date' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('reservation_date');
    }

    public function test_inactive_resource_cannot_be_booked(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $resource = Resource::factory()->create(['status' => 'under_maintenance']);

        $this->actingAs($user)
            ->post(route('reservations.store'), $this->payload($resource, $user))
            ->assertSessionHasErrors('resource_id');
    }

    public function test_cancel_requires_reason_and_releases_the_slot(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $resource = Resource::factory()->create(['status' => 'active']);

        $this->actingAs($user)->post(route('reservations.store'), $this->payload($resource, $user));
        $reservation = Reservation::query()->first();

        $this->actingAs($user)
            ->post(route('reservations.cancel', $reservation), [])
            ->assertSessionHasErrors('cancellation_reason');

        $this->actingAs($user)
            ->post(route('reservations.cancel', $reservation), [
                'cancellation_reason' => 'Batch postponed',
            ])
            ->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED->value,
        ]);
        $this->assertDatabaseHas('reservation_items', [
            'reservation_id' => $reservation->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_existing_reservations_filter_by_status(): void
    {
        $admin = User::factory()->role('admin')->create();
        $requester = User::factory()->role('slt_employee')->create();

        Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'purpose' => 'Pending hall booking',
        ]);
        Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'status' => ReservationStatus::CANCELLED->value,
            'purpose' => 'Cancelled hall booking',
        ]);

        $this->actingAs($admin)
            ->get(route('reservations.index', ['status' => ReservationStatus::PENDING_APPROVAL->value]))
            ->assertOk()
            ->assertSee('Pending hall booking')
            ->assertDontSee('Cancelled hall booking');
    }

    public function test_calendar_and_create_pages_render(): void
    {
        $user = User::factory()->role('coordinator')->create();

        $this->actingAs($user)->get(route('reservations.calendar'))->assertOk()->assertSee('Reservation Calendar');
        $this->actingAs($user)->get(route('reservations.create'))->assertOk()->assertSee('Purpose of Reservation');
        $this->actingAs($user)->get(route('reservations.index'))->assertOk()->assertSee('Existing Reservations');
    }

    public function test_nebula_sms_user_cannot_create_but_can_view_list(): void
    {
        $user = User::factory()->role('nebula_sms_user')->create();

        $this->actingAs($user)->get(route('reservations.create'))->assertForbidden();
        $this->actingAs($user)->get(route('reservations.index'))->assertOk();
        $this->actingAs($user)->get(route('reservations.calendar'))->assertOk();
    }

    public function test_location_dropdown_uses_campus_order(): void
    {
        $user = User::factory()->role('coordinator')->create();
        \App\Models\Location::factory()->create(['name' => 'Peradeniya']);
        \App\Models\Location::factory()->create(['name' => 'Welisara']);
        \App\Models\Location::factory()->create(['name' => 'Moratuwa']);

        $response = $this->actingAs($user)->getJson(route('reservations.lookups'));

        $response->assertOk();
        $this->assertSame(
            ['Welisara', 'Moratuwa', 'Peradeniya'],
            collect($response->json('locations'))->pluck('name')->all()
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Resource $resource, User $user, array $overrides = []): array
    {
        return array_merge([
            'resource_id' => $resource->id,
            'resource_ids' => [$resource->id],
            'requester_id' => $user->id,
            'location_id' => $resource->location_id,
            'reservation_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'purpose' => 'Training session',
            'attendee_count' => 10,
        ], $overrides);
    }
}
