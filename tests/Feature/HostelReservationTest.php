<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use App\Models\User;
use App\Notifications\NewReservationPending;
use App\Notifications\ReservationCreated;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HostelReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_create_hostel_stay_reuses_reservation_engine(): void
    {
        Notification::fake();

        $actor = User::factory()->role('coordinator')->create();
        $manager = User::factory()->role('hostel_manager')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($actor)
            ->post(route('hostel.store'), $this->payload($setup, [
                'reservation_name' => 'Summer Internship 2026 Group',
                'guest_name' => 'Kasun Madushanka',
                'special_requirements' => 'Ground floor if possible',
            ]))
            ->assertRedirect();

        $reservation = Reservation::query()->first();

        $this->assertNotNull($reservation);
        $this->assertSame(ReservationType::HOSTEL->value, $reservation->type);
        $this->assertStringStartsWith('HST-'.now()->format('Y').'-', $reservation->reference);
        $this->assertSame(ReservationStatus::PENDING_APPROVAL->value, $reservation->status);
        $this->assertSame('Summer Internship 2026 Group', $reservation->title);

        $this->assertDatabaseHas('hostel_stay_details', [
            'reservation_id' => $reservation->id,
            'guest_name' => 'Kasun Madushanka',
            'room_type_id' => $setup['type']->id,
            'number_of_guests' => 1,
            'special_requirements' => 'Ground floor if possible',
        ]);
        $this->assertDatabaseHas('reservation_items', [
            'reservation_id' => $reservation->id,
            'resource_id' => $setup['room']->id,
            'status' => 'held',
        ]);

        Notification::assertSentTo($actor, ReservationCreated::class);
        Notification::assertSentTo($manager, NewReservationPending::class);
    }

    public function test_overlapping_stay_on_the_same_room_is_rejected(): void
    {
        $user = User::factory()->role('slt_employee')->create();
        $setup = $this->hostelRoom();
        $checkIn = now()->addDays(3)->toDateString();
        $checkOut = now()->addDays(6)->toDateString();

        $this->actingAs($user)
            ->post(route('hostel.store'), $this->payload($setup, [
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'guest_name' => 'First Guest',
            ]))
            ->assertRedirect();

        $this->actingAs($user)
            ->from(route('hostel.create'))
            ->post(route('hostel.store'), $this->payload($setup, [
                'check_in_date' => now()->addDays(4)->toDateString(),
                'check_out_date' => now()->addDays(7)->toDateString(),
                'guest_name' => 'Overlapping Guest',
            ]))
            ->assertSessionHasErrors('check_in_date');

        $this->assertDatabaseCount('reservations', 1);
        $this->assertDatabaseCount('hostel_stay_details', 1);
    }

    public function test_next_guest_can_check_in_on_checkout_day(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($user)
            ->post(route('hostel.store'), $this->payload($setup, [
                'check_in_date' => now()->addDays(2)->toDateString(),
                'check_out_date' => now()->addDays(4)->toDateString(),
                'guest_name' => 'Departing Guest',
            ]))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('hostel.store'), $this->payload($setup, [
                'check_in_date' => now()->addDays(4)->toDateString(),
                'check_out_date' => now()->addDays(6)->toDateString(),
                'guest_name' => 'Arriving Guest',
            ]))
            ->assertRedirect();

        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_auto_assigns_next_free_room_of_the_same_category(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $first = $this->hostelRoom('Single');
        $second = Resource::factory()->create([
            'resource_type_id' => $first['type']->id,
            'location_id' => $first['location']->id,
            'name_model' => 'Welisara Single 999',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('hostel.store'), $this->payload($first, [
            'guest_name' => 'Guest One',
        ]))->assertRedirect();

        $this->actingAs($user)->post(route('hostel.store'), $this->payload($first, [
            'guest_name' => 'Guest Two',
        ]))->assertRedirect();

        $assigned = Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->with('items')
            ->get()
            ->flatMap(fn (Reservation $reservation) => $reservation->items->pluck('resource_id'))
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            collect([$first['room']->id, $second->id])->sort()->values()->all(),
            $assigned
        );
    }

    public function test_hostel_manager_cannot_create_but_can_view_and_cancel(): void
    {
        $coordinator = User::factory()->role('coordinator')->create();
        $manager = User::factory()->role('hostel_manager')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($coordinator)
            ->post(route('hostel.store'), $this->payload($setup, ['guest_name' => 'Nimal Perera']))
            ->assertRedirect();

        $reservation = Reservation::query()->first();

        $this->actingAs($manager)->get(route('hostel.create'))->assertForbidden();
        $this->actingAs($manager)->post(route('hostel.store'), $this->payload($setup))->assertForbidden();
        $this->actingAs($manager)->get(route('hostel.index'))->assertOk()->assertSee('Nimal Perera');
        $this->actingAs($manager)->get(route('hostel.show', $reservation))->assertOk()->assertSee('Nimal Perera');

        $this->actingAs($manager)
            ->post(route('hostel.cancel', $reservation), [
                'cancellation_reason' => 'Guest no longer attending',
            ])
            ->assertRedirect(route('hostel.index'));

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED->value,
        ]);
    }

    public function test_index_filters_by_guest_name_and_hides_standard_reservations(): void
    {
        $admin = User::factory()->role('admin')->create();
        $setup = $this->hostelRoom();
        $actor = User::factory()->role('slt_employee')->create();

        $this->actingAs($actor)->post(route('hostel.store'), $this->payload($setup, [
            'guest_name' => 'Kasun Madushanka',
            'reservation_name' => 'Internship stay',
        ]));
        $this->actingAs($actor)->post(route('hostel.store'), $this->payload($setup, [
            'guest_name' => 'Amali Fernando',
            'reservation_name' => 'Conference stay',
            'check_in_date' => now()->addDays(10)->toDateString(),
            'check_out_date' => now()->addDays(12)->toDateString(),
        ]));

        Reservation::factory()->create([
            'requester_id' => $actor->id,
            'created_by_user_id' => $actor->id,
            'type' => ReservationType::STANDARD->value,
            'title' => 'Hall booking must not appear',
            'purpose' => 'Hall booking must not appear',
        ]);

        $this->actingAs($admin)
            ->get(route('hostel.index', ['search' => 'Kasun']))
            ->assertOk()
            ->assertSee('Kasun Madushanka')
            ->assertDontSee('Amali Fernando')
            ->assertDontSee('Hall booking must not appear');
    }

    public function test_create_page_lists_locations_in_campus_order(): void
    {
        $user = User::factory()->role('coordinator')->create();
        Location::factory()->create(['name' => 'Peradeniya']);
        Location::factory()->create(['name' => 'Welisara']);
        Location::factory()->create(['name' => 'Moratuwa']);

        $html = $this->actingAs($user)->get(route('hostel.create'))->assertOk()->getContent();
        $welisara = strpos($html, 'Welisara');
        $moratuwa = strpos($html, 'Moratuwa');
        $peradeniya = strpos($html, 'Peradeniya');

        $this->assertNotFalse($welisara);
        $this->assertTrue($welisara < $moratuwa && $moratuwa < $peradeniya);
    }

    public function test_checkout_must_be_after_checkin(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $setup = $this->hostelRoom();
        $date = now()->addDay()->toDateString();

        $this->actingAs($user)
            ->from(route('hostel.create'))
            ->post(route('hostel.store'), $this->payload($setup, [
                'check_in_date' => $date,
                'check_out_date' => $date,
            ]))
            ->assertSessionHasErrors('check_out_date');
    }

    /**
     * @return array{location: Location, type: ResourceType, room: Resource}
     */
    private function hostelRoom(string $typeName = 'Single'): array
    {
        $location = Location::factory()->create(['name' => 'Welisara']);
        $category = ResourceCategory::query()->firstOrCreate(
            ['name' => config('hostel.category_name', 'Hostel Room')]
        );
        $type = ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $category->id, 'name' => $typeName],
            ['description' => $typeName.' hostel room']
        );
        $room = Resource::factory()->create([
            'resource_type_id' => $type->id,
            'location_id' => $location->id,
            'name_model' => 'Welisara '.$typeName.' 101',
            'status' => 'active',
        ]);

        return compact('location', 'type', 'room');
    }

    /**
     * @param  array{location: Location, type: ResourceType, room: Resource}  $setup
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $setup, array $overrides = []): array
    {
        return array_merge([
            'reservation_name' => 'Hostel stay',
            'guest_name' => 'Test Guest',
            'check_in_date' => now()->addDay()->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
            'room_type_id' => $setup['type']->id,
            'location_id' => $setup['location']->id,
            'number_of_guests' => 1,
            'special_requirements' => null,
        ], $overrides);
    }
}
