<?php

namespace Tests\Feature;

use App\Enums\ReservationItemStatus;
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
use Database\Seeders\HostelReservationSeeder;
use Database\Seeders\HostelRoomSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
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
                'guest_title' => 'Mr.',
                'guest_name' => 'Kasun Madushanka',
                'guest_phone' => '+94 77 123 4567',
                'number_of_guests' => 2,
                'employee_ids' => ['EMP-1001', 'EMP-1002'],
                'check_in_time' => '14:00',
                'check_out_time' => '10:30',
                'special_requirements' => 'Ground floor if possible',
            ]))
            ->assertRedirect();

        $reservation = Reservation::query()->first();

        $this->assertNotNull($reservation);
        $this->assertSame(ReservationType::HOSTEL->value, $reservation->type);
        $this->assertStringStartsWith('HST-'.now()->format('Y').'-', $reservation->reference);
        $this->assertSame(ReservationStatus::PENDING_APPROVAL->value, $reservation->status);
        $this->assertSame('Hostel stay for Mr. Kasun Madushanka', $reservation->title);

        $this->assertDatabaseHas('hostel_stay_details', [
            'reservation_id' => $reservation->id,
            'guest_title' => 'Mr.',
            'guest_name' => 'Kasun Madushanka',
            'room_type_id' => $setup['type']->id,
            'number_of_guests' => 2,
            'special_requirements' => 'Ground floor if possible',
        ]);
        $stay = $reservation->hostelStay()->firstOrFail();
        $tz = config('reservations.display_timezone', 'Asia/Colombo');
        $this->assertSame(['EMP-1001', 'EMP-1002'], $stay->employee_ids);
        $this->assertSame('14:00', $stay->check_in_at->timezone($tz)->format('H:i'));
        $this->assertSame('10:30', $stay->check_out_at->timezone($tz)->format('H:i'));
        $this->assertSame('+94 77 123 4567', Crypt::decryptString($stay->guest_phone_encrypted));
        $this->assertDatabaseHas('reservation_items', [
            'reservation_id' => $reservation->id,
            'resource_id' => $setup['room']->id,
            'status' => 'held',
        ]);

        Notification::assertNotSentTo($actor, ReservationCreated::class);
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

    public function test_each_guest_needs_an_employee_id(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($user)
            ->from(route('hostel.create'))
            ->post(route('hostel.store'), $this->payload($setup, [
                'number_of_guests' => 4,
                'employee_ids' => ['EMP-1', 'EMP-2'],
            ]))
            ->assertSessionHasErrors('employee_ids');

        $this->actingAs($user)
            ->from(route('hostel.create'))
            ->post(route('hostel.store'), $this->payload($setup, [
                'number_of_guests' => 2,
                'employee_ids' => ['EMP-1', ''],
            ]))
            ->assertSessionHasErrors('employee_ids.1');

        $this->actingAs($user)
            ->from(route('hostel.create'))
            ->post(route('hostel.store'), $this->payload($setup, [
                'guest_title' => 'Sir',
            ]))
            ->assertSessionHasErrors('guest_title');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_create_page_has_title_employee_ids_and_times_without_room_category(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $this->hostelRoom();

        $this->actingAs($user)->get(route('hostel.create'))
            ->assertOk()
            ->assertSee('name="guest_title"', false)
            ->assertSee('name="employee_ids[]"', false)
            ->assertSee('name="check_in_time"', false)
            ->assertSee('name="check_out_time"', false)
            ->assertSee('011 2954 069')
            ->assertSee('placeholder="010456"', false)
            ->assertDontSee('name="reservation_name"', false)
            ->assertDontSee('name="room_type_id"', false)
            ->assertDontSee('name="guest_identity_number"', false)
            ->assertDontSee('ext. 404')
            ->assertDontSee('The system assigns a free room');
    }

    public function test_next_guest_can_check_in_on_the_day_after_checkout(): void
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
                'check_in_date' => now()->addDays(5)->toDateString(),
                'check_out_date' => now()->addDays(6)->toDateString(),
                'guest_name' => 'Arriving Guest',
            ]))
            ->assertRedirect();

        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_auto_assigns_next_free_room_at_the_location(): void
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

    public function test_pending_hostel_row_shows_manager_approval_actions_and_approving_confirms_room(): void
    {
        $coordinator = User::factory()->role('coordinator')->create();
        $manager = User::factory()->role('hostel_manager')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($coordinator)
            ->post(route('hostel.store'), $this->payload($setup))
            ->assertRedirect();

        $reservation = Reservation::query()->firstOrFail();

        $this->actingAs($manager)
            ->get(route('hostel.index'))
            ->assertOk()
            ->assertSee('Approve')
            ->assertSee('Reject');

        $this->post(route('hostel.approval', $reservation), [
            'status' => ReservationStatus::APPROVED->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::APPROVED->value,
        ]);
        $this->assertDatabaseHas('reservation_items', [
            'reservation_id' => $reservation->id,
            'status' => 'confirmed',
        ]);

        $this->get(route('hostel.index'))
            ->assertOk()
            ->assertDontSee('id="approveModal-')
            ->assertDontSee('id="rejectModal-');
    }

    public function test_cancelled_rejected_and_approved_stays_hide_repeat_actions(): void
    {
        $admin = User::factory()->role('admin')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($admin)
            ->post(route('hostel.store'), $this->payload($setup))
            ->assertRedirect();

        $reservation = Reservation::query()->firstOrFail();

        $reservation->update(['status' => ReservationStatus::CANCELLED->value]);
        $this->get(route('hostel.index'))
            ->assertOk()
            ->assertSee('Approve unavailable', false)
            ->assertSee('Reject unavailable', false)
            ->assertSee('Cancel unavailable', false)
            ->assertDontSee('Approve reservation '.$reservation->reference, false)
            ->assertDontSee('Reject reservation '.$reservation->reference, false)
            ->assertDontSee('Cancel reservation '.$reservation->reference, false);
        $this->post(route('hostel.cancel', $reservation), ['cancellation_reason' => 'again'])->assertForbidden();
        $this->post(route('hostel.approval', $reservation), [
            'status' => ReservationStatus::APPROVED->value,
        ])->assertForbidden();

        $reservation->update(['status' => ReservationStatus::REJECTED->value]);
        $this->get(route('hostel.index'))
            ->assertOk()
            ->assertSee('Approve unavailable', false)
            ->assertSee('Reject unavailable', false)
            ->assertSee('Cancel unavailable', false)
            ->assertDontSee('Approve reservation '.$reservation->reference, false)
            ->assertDontSee('Reject reservation '.$reservation->reference, false)
            ->assertDontSee('Cancel reservation '.$reservation->reference, false);
        $this->post(route('hostel.approval', $reservation), [
            'status' => ReservationStatus::REJECTED->value,
            'reason' => 'again',
        ])->assertForbidden();

        $reservation->update(['status' => ReservationStatus::APPROVED->value]);
        $this->get(route('hostel.index'))
            ->assertOk()
            ->assertSee('Approve unavailable', false)
            ->assertSee('Reject unavailable', false)
            ->assertDontSee('Approve reservation '.$reservation->reference, false)
            ->assertDontSee('Reject reservation '.$reservation->reference, false)
            ->assertSee('Cancel reservation '.$reservation->reference, false);
    }

    public function test_hostel_approval_rejects_a_room_already_confirmed_for_overlapping_dates(): void
    {
        $coordinator = User::factory()->role('coordinator')->create();
        $manager = User::factory()->role('hostel_manager')->create();
        $setup = $this->hostelRoom();
        $stayDates = [
            'check_in_date' => now()->addDays(3)->toDateString(),
            'check_out_date' => now()->addDays(6)->toDateString(),
        ];

        $this->actingAs($coordinator)->post(route('hostel.store'), $this->payload($setup, $stayDates))->assertRedirect();
        $firstReservation = Reservation::query()->firstOrFail();
        $firstItem = $firstReservation->items()->firstOrFail();
        $firstItem->update(['status' => 'cancelled']);

        $this->actingAs($coordinator)->post(route('hostel.store'), $this->payload($setup, $stayDates))->assertRedirect();
        $secondReservation = Reservation::query()->whereKeyNot($firstReservation->id)->firstOrFail();

        $firstItem->update(['status' => 'confirmed']);
        $firstReservation->update(['status' => ReservationStatus::APPROVED->value]);

        $this->actingAs($manager)
            ->from(route('hostel.index'))
            ->post(route('hostel.approval', $secondReservation), [
                'status' => ReservationStatus::APPROVED->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('reservations', [
            'id' => $secondReservation->id,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
        ]);
        $this->assertDatabaseHas('reservation_items', [
            'reservation_id' => $secondReservation->id,
            'status' => 'held',
        ]);
    }

    public function test_index_filters_by_guest_name_and_hides_standard_reservations(): void
    {
        $admin = User::factory()->role('admin')->create();
        $setup = $this->hostelRoom();
        $actor = User::factory()->role('slt_employee')->create();

        $this->actingAs($actor)->post(route('hostel.store'), $this->payload($setup, [
            'guest_name' => 'Kasun Madushanka',
            'employee_ids' => ['EMP-7788'],
        ]));
        $this->actingAs($actor)->post(route('hostel.store'), $this->payload($setup, [
            'guest_name' => 'Amali Fernando',
            'employee_ids' => ['EMP-9911'],
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
            ->assertSee('EMP-7788')
            ->assertDontSee('Amali Fernando')
            ->assertDontSee('Hall booking must not appear');

        $this->get(route('hostel.index', ['search' => 'EMP-9911']))
            ->assertOk()
            ->assertSee('Amali Fernando')
            ->assertDontSee('Kasun Madushanka');
    }

    public function test_pending_hostel_reservation_expires_at_checkin_and_releases_held_room(): void
    {
        $actor = User::factory()->role('coordinator')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($actor)->post(route('hostel.store'), $this->payload($setup))->assertRedirect();
        $reservation = Reservation::query()->firstOrFail();
        $pastCheckIn = now('UTC')->subMinute();

        $reservation->hostelStay()->update([
            'check_in_at' => $pastCheckIn,
            'check_out_at' => $pastCheckIn->copy()->addDay(),
        ]);
        $reservation->items()->update([
            'starts_at' => $pastCheckIn,
            'ends_at' => $pastCheckIn->copy()->addDay(),
        ]);

        $this->artisan('hostel:expire-pending-reservations')
            ->expectsOutput('Expired 1 pending hostel reservation(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::EXPIRED->value,
        ]);
        $this->assertDatabaseHas('reservation_items', [
            'reservation_id' => $reservation->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('reservation_status_history', [
            'reservation_id' => $reservation->id,
            'from_status' => ReservationStatus::PENDING_APPROVAL->value,
            'to_status' => ReservationStatus::EXPIRED->value,
            'actor_id' => null,
        ]);
    }

    public function test_hostel_pages_expire_overdue_requests_without_running_the_scheduler(): void
    {
        $actor = User::factory()->role('coordinator')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($actor)->post(route('hostel.store'), $this->payload($setup))->assertRedirect();
        $reservation = Reservation::query()->firstOrFail();
        $checkIn = $reservation->hostelStay()->firstOrFail()->check_in_at;

        $this->travelTo($checkIn->copy()->subSecond());
        $this->get(route('hostel.index'))->assertOk()->assertSee('Pending');
        $this->assertSame(ReservationStatus::PENDING_APPROVAL->value, $reservation->fresh()->status);

        $this->travelTo($checkIn);
        $this->get(route('hostel.index', ['status' => ReservationStatus::EXPIRED->value]))
            ->assertOk()->assertSee($reservation->reference)->assertSee('Expired')
            ->assertDontSee('id="approveModal-');
        $this->assertSame(ReservationStatus::EXPIRED->value, $reservation->fresh()->status);
        $this->assertSame(ReservationItemStatus::CANCELLED->value, $reservation->items()->firstOrFail()->status);

        $this->get(route('hostel.show', $reservation))->assertOk()->assertSee('Expired');
        $this->get(route('hostel.index'))->assertOk();
        $this->assertSame(1, $reservation->statusHistory()->where('to_status', ReservationStatus::EXPIRED->value)->count());
    }

    public function test_opening_hostel_details_expires_an_overdue_pending_request(): void
    {
        $actor = User::factory()->role('coordinator')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($actor)->post(route('hostel.store'), $this->payload($setup))->assertRedirect();
        $reservation = Reservation::query()->firstOrFail();
        $this->travelTo($reservation->hostelStay()->firstOrFail()->check_in_at);

        $this->get(route('hostel.show', $reservation))->assertOk()->assertSee('Expired');
        $this->assertSame(ReservationStatus::EXPIRED->value, $reservation->fresh()->status);
        $this->assertSame(ReservationItemStatus::CANCELLED->value, $reservation->items()->firstOrFail()->status);
    }

    public function test_approval_after_checkin_expires_pending_request_instead(): void
    {
        $coordinator = User::factory()->role('coordinator')->create();
        $manager = User::factory()->role('hostel_manager')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($coordinator)->post(route('hostel.store'), $this->payload($setup))->assertRedirect();
        $reservation = Reservation::query()->firstOrFail();
        $reservation->hostelStay()->update(['check_in_at' => now()->subMinute()]);

        $this->actingAs($manager)
            ->from(route('hostel.index'))
            ->post(route('hostel.approval', $reservation), ['status' => ReservationStatus::APPROVED->value])
            ->assertRedirect(route('hostel.index'))
            ->assertSessionHas('success', 'Reservation expired at check-in; the held room was released and was not approved.');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::EXPIRED->value,
        ]);
        $this->assertDatabaseHas('reservation_items', [
            'reservation_id' => $reservation->id,
            'status' => ReservationItemStatus::CANCELLED->value,
        ]);
        $this->assertDatabaseHas('reservation_status_history', [
            'reservation_id' => $reservation->id,
            'to_status' => ReservationStatus::EXPIRED->value,
        ]);
    }

    public function test_create_page_lists_only_hostel_locations_in_campus_order(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $setup = $this->hostelRoom();
        Location::factory()->create(['name' => 'Peradeniya']);
        $moratuwa = Location::factory()->create(['name' => 'Moratuwa']);
        Resource::factory()->create([
            'resource_type_id' => $setup['type']->id,
            'location_id' => $moratuwa->id,
            'name_model' => 'Moratuwa Single 101',
            'status' => 'active',
        ]);

        $html = $this->actingAs($user)->get(route('hostel.create'))->assertOk()->getContent();
        $welisaraAt = strpos($html, 'Welisara');
        $moratuwaAt = strpos($html, 'Moratuwa');

        $this->assertNotFalse($welisaraAt);
        $this->assertTrue($welisaraAt < $moratuwaAt);
        $this->assertStringNotContainsString('Peradeniya', $html);
    }

    public function test_guest_phone_is_required(): void
    {
        $user = User::factory()->role('coordinator')->create();
        $setup = $this->hostelRoom();

        $this->actingAs($user)
            ->from(route('hostel.create'))
            ->post(route('hostel.store'), $this->payload($setup, ['guest_phone' => '']))
            ->assertSessionHasErrors('guest_phone');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_approved_stays_complete_at_checkout_through_pages_and_command(): void
    {
        $actor = User::factory()->role('coordinator')->create();
        $setup = $this->hostelRoom();
        $this->actingAs($actor)->post(route('hostel.store'), $this->payload($setup))->assertRedirect();
        $reservation = Reservation::query()->firstOrFail();
        $reservation->update(['status' => ReservationStatus::APPROVED->value]);
        $reservation->items()->update(['status' => ReservationItemStatus::CONFIRMED->value]);
        $checkout = $reservation->hostelStay()->firstOrFail()->check_out_at;

        $this->travelTo($checkout->copy()->subSecond());
        $this->artisan('hostel:complete-reservations')->expectsOutput('Completed 0 hostel reservation(s).')->assertSuccessful();
        $this->get(route('hostel.show', $reservation))->assertOk();
        $this->assertSame(ReservationStatus::APPROVED->value, $reservation->fresh()->status);

        $this->travelTo($checkout);
        $this->get(route('hostel.show', $reservation))->assertOk()->assertSee('Completed');
        $this->assertSame(ReservationStatus::COMPLETED->value, $reservation->fresh()->status);
        $this->get(route('hostel.index', ['status' => 'completed']))->assertOk()->assertSee($reservation->reference);
        $this->artisan('hostel:complete-reservations')->expectsOutput('Completed 0 hostel reservation(s).')->assertSuccessful();
        $this->assertSame(1, $reservation->statusHistory()->where('to_status', 'completed')->count());
        $this->assertSame('confirmed', $reservation->items()->firstOrFail()->status);

        // Verify the list and command can each perform the same transition.
        $reservation->refresh()->update(['status' => 'approved']);
        $this->get(route('hostel.index'))->assertOk();
        $this->assertSame('completed', $reservation->fresh()->status);

        $reservation->refresh()->update(['status' => 'approved']);
        $this->artisan('hostel:complete-reservations')->expectsOutput('Completed 1 hostel reservation(s).')->assertSuccessful();
        foreach (['cancelled', 'rejected', 'expired', 'pending_approval'] as $status) {
            $reservation->update(['status' => $status]);
            $this->artisan('hostel:complete-reservations')->expectsOutput('Completed 0 hostel reservation(s).')->assertSuccessful();
            $this->assertSame($status, $reservation->fresh()->status);
        }
    }

    public function test_hostel_reservation_seeder_creates_sample_stays(): void
    {
        $this->seed([
            LocationSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            HostelRoomSeeder::class,
            HostelReservationSeeder::class,
        ]);

        $stays = Reservation::query()->where('type', ReservationType::HOSTEL->value);

        $this->assertGreaterThanOrEqual(12, $stays->count());
        $this->assertDatabaseHas('hostel_stay_details', ['guest_name' => 'Kasun Madushanka']);
        $this->assertTrue($stays->clone()->where('status', ReservationStatus::PENDING_APPROVAL->value)->exists());
        $this->assertTrue($stays->clone()->where('status', ReservationStatus::APPROVED->value)->exists());
        $this->assertTrue($stays->clone()->where('status', ReservationStatus::CANCELLED->value)->exists());
        $this->assertTrue($stays->clone()->where('status', ReservationStatus::REJECTED->value)->exists());
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
                'check_in_time' => '12:00',
                'check_out_date' => $date,
                'check_out_time' => '12:00',
            ]))
            ->assertSessionHasErrors('check_out_date');

        $this->actingAs($user)
            ->post(route('hostel.store'), $this->payload($setup, [
                'check_in_date' => $date,
                'check_in_time' => '09:00',
                'check_out_date' => $date,
                'check_out_time' => '18:00',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    /**
     * @return array{location: Location, type: ResourceType, room: resource}
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
     * @param  array{location: Location, type: ResourceType, room: resource}  $setup
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $setup, array $overrides = []): array
    {
        return array_merge([
            'guest_title' => 'Ms.',
            'guest_name' => 'Test Guest',
            'guest_phone' => '+94 77 000 0000',
            'employee_ids' => ['010456'],
            'check_in_date' => now()->addDay()->toDateString(),
            'check_in_time' => '11:00',
            'check_out_date' => now()->addDays(3)->toDateString(),
            'check_out_time' => '10:00',
            'location_id' => $setup['location']->id,
            'number_of_guests' => 1,
            'special_requirements' => null,
        ], $overrides);
    }
}
