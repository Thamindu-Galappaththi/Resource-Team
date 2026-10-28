<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationItem;
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

        Notification::assertNotSentTo($user, ReservationCreated::class);
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

    public function test_missing_resource_only_reports_the_resource_field(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $resource = Resource::factory()->create();

        $this->actingAs($user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), [
                'location_id' => $resource->location_id,
                'category_id' => $resource->type->resource_category_id,
                'reservation_date' => now()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Missing resource',
            ])
            ->assertSessionHasErrors('resource_id')
            ->assertSessionDoesntHaveErrors('resource_ids');
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

        $this->actingAs($user)->post(route('reservations.store'), $this->payload($resource, $user, [
            'reservation_date' => now('Asia/Colombo')->addDays(2)->toDateString(),
        ]));
        $second = Reservation::query()->latest('id')->first();
        $this->actingAs($user)
            ->postJson(route('reservations.cancel', $second), [
                'cancellation_reason' => 'Room no longer needed',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Reservation cancelled successfully.');
        $this->assertSame(ReservationStatus::CANCELLED->value, $second->fresh()->status);
    }

    public function test_cancelled_and_rejected_reservations_hide_cancel_for_admin(): void
    {
        $admin = User::factory()->role('admin')->create();
        $requester = User::factory()->role('slt_employee')->create();

        $cancelled = Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'status' => ReservationStatus::CANCELLED->value,
            'purpose' => 'Cancelled hall booking',
        ]);
        $rejected = Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'status' => ReservationStatus::REJECTED->value,
            'purpose' => 'Rejected hall booking',
        ]);
        $pending = Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'purpose' => 'Pending hall booking',
        ]);

        $this->actingAs($admin)
            ->getJson(route('reservations.feed'))
            ->assertOk()
            ->assertJsonFragment(['id' => $cancelled->id, 'can_cancel' => false])
            ->assertJsonFragment(['id' => $rejected->id, 'can_cancel' => false])
            ->assertJsonFragment(['id' => $pending->id, 'can_cancel' => true]);

        $this->get(route('reservations.show', $cancelled))
            ->assertOk()
            ->assertDontSee('Cancel reservation', false);
        $this->get(route('reservations.show', $rejected))
            ->assertOk()
            ->assertDontSee('Cancel reservation', false);
        $this->get(route('reservations.show', $pending))
            ->assertOk()
            ->assertSee('Cancel reservation', false);

        $this->post(route('reservations.cancel', $cancelled), [
            'cancellation_reason' => 'again',
        ])->assertForbidden();
        $this->post(route('reservations.cancel', $rejected), [
            'cancellation_reason' => 'again',
        ])->assertForbidden();
    }

    public function test_existing_reservations_modal_item_status_matches_reservation_status(): void
    {
        $admin = User::factory()->role('admin')->create();
        $requester = User::factory()->role('slt_employee')->create();
        $resource = Resource::factory()->create(['status' => 'active', 'name_model' => 'Welisara Single 101']);

        $pending = Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'purpose' => 'Pending hostel stay',
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $pending->id,
            'resource_id' => $resource->id,
            'resource_name_snapshot' => 'Welisara Single 101',
            'status' => 'cancelled',
        ]);

        $rejected = Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'status' => ReservationStatus::REJECTED->value,
            'purpose' => 'Rejected hostel stay',
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $rejected->id,
            'resource_id' => $resource->id,
            'resource_name_snapshot' => 'Welisara Single 101',
            'status' => 'cancelled',
        ]);

        $payload = $this->actingAs($admin)
            ->getJson(route('reservations.feed'))
            ->assertOk()
            ->json('reservations');
        $pendingRow = collect($payload)->firstWhere('purpose', 'Pending hostel stay');
        $rejectedRow = collect($payload)->firstWhere('purpose', 'Rejected hostel stay');
        $this->assertSame('Pending approval', $pendingRow['items'][0]['status']);
        $this->assertSame('Rejected', $rejectedRow['items'][0]['status']);
        $this->assertNotSame('Cancelled', $pendingRow['items'][0]['status']);
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

        $this->actingAs($admin)
            ->getJson(route('reservations.feed', ['status' => ReservationStatus::PENDING_APPROVAL->value]))
            ->assertOk()
            ->assertJsonFragment(['purpose' => 'Pending hall booking'])
            ->assertJsonMissing(['purpose' => 'Cancelled hall booking']);

        $this->actingAs($admin)
            ->getJson(route('reservations.index', ['search' => 'tt']))
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('Existing Reservations', false);
    }

    public function test_calendar_and_create_pages_render(): void
    {
        $user = User::factory()->role('coordinator')->create();

        $this->actingAs($user)->get(route('reservations.calendar'))
            ->assertOk()
            ->assertSee('Reservation Calendar')
            ->assertSee('reservationCalendar')
            ->assertSee('id="refreshCalendarButton"', false)
            ->assertSee('data-slt-employee="0"', false)
            ->assertDontSee('is-slt-calendar', false)
            ->assertSee('fullcalendar@6.1.19', false)
            ->assertSee('js/reservation-calendar.js', false)
            ->assertSeeInOrder([
                'id="calendar-location"',
                'id="calendar-category"',
                'id="calendar-resource"',
                'id="calendar-status"',
            ], false);
        $this->actingAs($user)->get(route('reservations.create'))->assertOk()->assertSee('Purpose of Reservation');
        $this->actingAs($user)->get(route('reservations.index'))->assertOk()->assertSee('Existing Reservations');
    }

    public function test_calendar_json_returns_month_payload(): void
    {
        $user = User::factory()->role('coordinator')->create();

        $this->actingAs($user)
            ->getJson(route('reservations.calendar.stats', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertJsonPath('year', 2026)
            ->assertJsonPath('month', 10)
            ->assertJsonStructure(['label', 'cells', 'stats', 'reservationsByDate', 'holidaysByDate', 'prev', 'next'])
            ->assertJsonPath('holidaysByDate.2026-10-25.kind', 'poya');
    }

    public function test_calendar_events_include_each_resource_item(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $hallA = Resource::factory()->create(['name_model' => 'Hall A']);
        $hallB = Resource::factory()->create([
            'name_model' => 'Hall B',
            'location_id' => $hallA->location_id,
            'resource_type_id' => $hallA->resource_type_id,
        ]);

        $this->actingAs($user)->post(route('reservations.store'), $this->payload($hallA, $user, [
            'resource_id' => $hallA->id,
            'resource_ids' => [$hallA->id, $hallB->id],
            'purpose' => 'Dual hall booking',
        ]))->assertRedirect();

        $events = $this->actingAs($user)
            ->getJson(route('reservations.calendar.events', [
                'start' => now()->startOfMonth()->toIso8601String(),
                'end' => now()->endOfMonth()->addDay()->toIso8601String(),
            ]))
            ->assertOk()
            ->json();

        $resources = collect($events)->pluck('extendedProps.resource')->filter();
        $this->assertTrue($resources->contains(fn ($name) => str_contains((string) $name, 'Hall A')));
        $this->assertTrue($resources->contains(fn ($name) => str_contains((string) $name, 'Hall B')));
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

    public function test_calendar_filter_catalog_maps_resource_location_and_category(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $resource = Resource::factory()->create(['name_model' => 'Audio Mixer Pro']);

        $this->actingAs($user)->get(route('reservations.calendar'))
            ->assertOk()
            ->assertSee('filterCatalog', false)
            ->assertSee('"name":"Audio Mixer Pro"', false)
            ->assertSee('"location_id":"'.$resource->location_id.'"', false)
            ->assertSee('"category_id":"'.$resource->type->resource_category_id.'"', false);
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
            'category_id' => $resource->type->resource_category_id,
            'reservation_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'purpose' => 'Training session',
            'attendee_count' => 10,
        ], $overrides);
    }
}
