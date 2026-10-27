<?php

namespace Tests\Feature;

use App\Enums\CanteenReservationStatus;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\CanteenReservation;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SltEmployeeDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_resource_my_requests_only_lists_own_bookings(): void
    {
        $employee = User::factory()->role('slt_employee')->create();
        $other = User::factory()->role('slt_employee')->create();

        Reservation::factory()->create([
            'requester_id' => $employee->id,
            'title' => 'My projector booking',
            'status' => ReservationStatus::PENDING_APPROVAL->value,
        ]);
        Reservation::factory()->create([
            'requester_id' => $other->id,
            'title' => 'Secret other booking',
        ]);
        Reservation::factory()->create([
            'requester_id' => $employee->id,
            'type' => ReservationType::HOSTEL->value,
            'title' => 'Should stay on hostel list',
        ]);

        $this->actingAs($employee)
            ->get(route('reservations.mine'))
            ->assertOk()
            ->assertSee('My resource requests')
            ->assertSee('Reference')
            ->assertSee('Request')
            ->assertSee('Details')
            ->assertSee('My projector booking')
            ->assertDontSee('Secret other booking')
            ->assertDontSee('Should stay on hostel list');
    }

    public function test_hostel_my_requests_only_lists_own_hostel_bookings(): void
    {
        $employee = User::factory()->role('slt_employee')->create();
        $other = User::factory()->role('slt_employee')->create();

        Reservation::factory()->create([
            'requester_id' => $employee->id,
            'type' => ReservationType::HOSTEL->value,
            'title' => 'My hostel stay',
        ]);
        Reservation::factory()->create([
            'requester_id' => $other->id,
            'type' => ReservationType::HOSTEL->value,
            'title' => 'Other hostel stay',
        ]);
        Reservation::factory()->create([
            'requester_id' => $employee->id,
            'title' => 'Lab booking',
        ]);

        $this->actingAs($employee)
            ->get(route('hostel.mine'))
            ->assertOk()
            ->assertSee('My hostel requests')
            ->assertSee('Guest')
            ->assertSee('Stay dates')
            ->assertSee('My hostel stay')
            ->assertDontSee('Other hostel stay')
            ->assertDontSee('Lab booking');
    }

    public function test_canteen_my_requests_only_lists_own_meal_orders(): void
    {
        $employee = User::factory()->role('slt_employee')->create();
        $other = User::factory()->role('slt_employee')->create();

        CanteenReservation::factory()->create([
            'requested_by_user_id' => $employee->id,
            'reservation_name' => 'Team lunch order',
            'status' => CanteenReservationStatus::PENDING->value,
        ]);
        CanteenReservation::factory()->create([
            'requested_by_user_id' => $other->id,
            'reservation_name' => 'Secret canteen order',
        ]);

        $this->actingAs($employee)
            ->get(route('canteen.mine'))
            ->assertOk()
            ->assertSee('My canteen requests')
            ->assertSee('Reference')
            ->assertSee('Details')
            ->assertSee('Team lunch order')
            ->assertDontSee('Secret canteen order');
    }

    public function test_my_requests_ajax_pagination_returns_table_fragment(): void
    {
        $employee = User::factory()->role('slt_employee')->create();

        foreach (range(1, 12) as $index) {
            Reservation::factory()->create([
                'requester_id' => $employee->id,
                'title' => 'Resource booking '.$index,
                'reservation_date' => now()->subDays($index)->toDateString(),
            ]);
        }

        $this->actingAs($employee)
            ->get(route('reservations.mine'))
            ->assertOk()
            ->assertSee('Showing 1–10 of 12')
            ->assertSee('id="slt-results"', false)
            ->assertDontSee('slt-action--inline');

        $this->actingAs($employee)
            ->get(route('reservations.mine', ['page' => 2]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Showing 11–12 of 12')
            ->assertSee('id="slt-results"', false)
            ->assertDontSee('My resource requests')
            ->assertSee('Resource booking 11')
            ->assertSee('Resource booking 12')
            ->assertDontSee('>Resource booking 1<', false);
    }

    public function test_employees_cannot_open_staff_lists_or_see_list_buttons(): void
    {
        $employee = User::factory()->role('slt_employee')->create();

        $this->actingAs($employee)->get(route('reservations.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('reservations.feed'))->assertForbidden();
        $this->actingAs($employee)->get(route('hostel.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('canteen.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('canteen.dashboard'))->assertForbidden();
        $this->actingAs($employee)->get(route('canteen.forecast', ['date' => now()->toDateString()]))->assertForbidden();

        $this->actingAs($employee)
            ->get(route('reservations.create'))
            ->assertOk()
            ->assertDontSee('Existing Reservations');
        $this->actingAs($employee)
            ->get(route('hostel.create'))
            ->assertOk()
            ->assertDontSee('Hostel Reservations');
        $this->actingAs($employee)
            ->get(route('canteen.create'))
            ->assertOk()
            ->assertDontSee('All reservations');
    }

    public function test_guests_cannot_open_my_requests(): void
    {
        $this->get(route('reservations.mine'))->assertRedirect(route('login'));
        $this->get(route('hostel.mine'))->assertRedirect(route('login'));
        $this->get(route('canteen.mine'))->assertRedirect(route('login'));
    }

    public function test_module_dashboards_show_only_that_sections_actions(): void
    {
        $employee = User::factory()->role('slt_employee')->create();

        $this->actingAs($employee)
            ->get(route('hostel.home'))
            ->assertOk()
            ->assertSee('Hostel reservation')
            ->assertSee('Reservation Calendar')
            ->assertSee('Create Hostel Reservation')
            ->assertSee('My requests')
            ->assertSee(route('hostel.calendar', absolute: false))
            ->assertDontSee('Create Reservation</span>', false);

        $this->actingAs($employee)
            ->get(route('canteen.home'))
            ->assertOk()
            ->assertSee('Canteen reservation')
            ->assertSee(route('canteen.calendar', absolute: false))
            ->assertSee(route('canteen.create', absolute: false));
    }

    public function test_hostel_and_canteen_calendars_render_for_employees(): void
    {
        $employee = User::factory()->role('slt_employee')->create();

        Reservation::factory()->create([
            'requester_id' => $employee->id,
            'type' => ReservationType::HOSTEL->value,
            'title' => 'My hostel stay',
            'reservation_date' => now()->toDateString(),
        ]);

        CanteenReservation::factory()->create([
            'requested_by_user_id' => $employee->id,
            'reservation_name' => 'Team lunch order',
            'reservation_date' => now()->toDateString(),
        ]);

        $other = User::factory()->role('slt_employee')->create();
        Reservation::factory()->create([
            'requester_id' => $other->id,
            'type' => ReservationType::HOSTEL->value,
            'title' => 'Other hostel stay',
            'reservation_date' => now()->toDateString(),
        ]);
        CanteenReservation::factory()->create([
            'requested_by_user_id' => $other->id,
            'reservation_name' => 'Secret canteen title',
            'reservation_date' => now()->toDateString(),
        ]);

        $this->actingAs($employee)
            ->get(route('hostel.calendar'))
            ->assertOk()
            ->assertSee('Hostel reservation calendar')
            ->assertSee('Create Hostel Reservation')
            ->assertDontSee('All locations')
            ->assertSee(route('hostel.create', absolute: false));

        $hostelMine = Reservation::query()->where('title', 'My hostel stay')->first();
        $hostelOther = Reservation::query()->where('title', 'Other hostel stay')->first();
        $range = [
            'start' => now()->subDay()->toDateString(),
            'end' => now()->addMonths(2)->toDateString(),
        ];
        $hostelEvents = collect($this->actingAs($employee)
            ->getJson(route('hostel.calendar.events', $range))
            ->assertOk()
            ->json());
        $mineHostel = $hostelEvents->first(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $hostelMine->id);
        $otherHostel = $hostelEvents->first(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $hostelOther->id);
        $this->assertNotNull($mineHostel);
        $this->assertNotNull($otherHostel);
        $this->assertSame('My hostel stay', $mineHostel['title']);
        $this->assertNotEmpty($mineHostel['extendedProps']['url'] ?? null);
        $this->assertEmpty($otherHostel['extendedProps']['url'] ?? null);
        $this->assertNotSame('Other hostel stay', $otherHostel['title']);

        $this->actingAs($employee)
            ->get(route('canteen.calendar'))
            ->assertOk()
            ->assertSee('Canteen reservation calendar')
            ->assertSee('Create reservation')
            ->assertDontSee('All locations')
            ->assertSee(route('canteen.create', absolute: false));

        $canteenMine = CanteenReservation::query()->where('reservation_name', 'Team lunch order')->first();
        $canteenOther = CanteenReservation::query()->where('reservation_name', 'Secret canteen title')->first();
        $canteenEvents = collect($this->actingAs($employee)
            ->getJson(route('canteen.calendar.events', $range))
            ->assertOk()
            ->json());
        $mineCanteen = $canteenEvents->first(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $canteenMine->id);
        $otherCanteen = $canteenEvents->first(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $canteenOther->id);
        $this->assertNotNull($mineCanteen);
        $this->assertNotNull($otherCanteen);
        $this->assertSame('Team lunch order', $mineCanteen['title']);
        $this->assertNotEmpty($mineCanteen['extendedProps']['url'] ?? null);
        $this->assertEmpty($otherCanteen['extendedProps']['url'] ?? null);
        $this->assertNotSame('Secret canteen title', $otherCanteen['title']);
    }

    public function test_normal_calendar_hides_hostel_stays_for_slt_employees(): void
    {
        $employee = User::factory()->role('slt_employee')->create();

        Reservation::factory()->create([
            'requester_id' => $employee->id,
            'title' => 'Lab session',
            'reservation_date' => now()->toDateString(),
        ]);
        Reservation::factory()->create([
            'requester_id' => $employee->id,
            'type' => ReservationType::HOSTEL->value,
            'title' => 'Guest wing stay',
            'reservation_date' => now()->toDateString(),
        ]);

        $stats = $this->actingAs($employee)
            ->getJson(route('reservations.calendar.stats'))
            ->assertOk()
            ->json();
        $encoded = json_encode($stats);

        $this->assertStringContainsString('Lab session', $encoded);
        $this->assertStringNotContainsString('Guest wing stay', $encoded);

        $hostelEvents = json_encode($this->actingAs($employee)
            ->getJson(route('hostel.calendar.events', [
                'start' => now()->subDay()->toDateString(),
                'end' => now()->addMonths(2)->toDateString(),
            ]))
            ->assertOk()
            ->json());
        $this->assertStringContainsString('Guest wing stay', $hostelEvents);
        $this->assertStringNotContainsString('Lab session', $hostelEvents);
    }
}
