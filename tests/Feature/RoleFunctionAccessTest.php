<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Resource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleFunctionAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    private const PAGES = [
        'dashboard' => 'dashboard',
        'create.user' => 'user.create',
        'user.management' => 'user.management',
        'reservations.calendar' => 'reservations.calendar',
        'reservations.create' => 'reservations.create',
        'reservations.index' => 'reservations.index',
        'resources.create' => 'resources.create',
        'resources.index' => 'resources.index',
        'resources.calendar' => 'resources.calendar',
        'approvals.index' => 'approvals.index',
        'approvals.special' => 'approvals.special',
        'hostel.index' => 'hostel.index',
        'hostel.create' => 'hostel.create',
        'canteen.dashboard' => 'canteen.view',
        'canteen.index' => 'canteen.index',
        'canteen.create' => 'canteen.create',
        'payments.lecture-fees' => 'payments.view',
        'reports.index' => 'reports.view',
    ];

    /**
     * @var array<string, string>
     */
    private const SIDEBAR = [
        'reservations.calendar' => 'hide-menu">Reservation Calendar',
        'reservations.create' => 'hide-menu">Create Reservations',
        'reservations.index' => 'hide-menu">Existing Reservations',
        'resources.create' => 'hide-menu">Create Resources',
        'resources.index' => 'hide-menu">Existing Resources',
        'resources.calendar' => 'hide-menu">Resource Calendar',
        'approvals.index' => 'hide-menu">Approvals</span>',
        'approvals.special' => 'hide-menu">Special Approvals',
        'hostel.create' => 'hide-menu">Create Hostel Reservation',
        'canteen.create' => 'hide-menu">Create Canteen Reservation',
        'reports.view' => 'hide-menu">Reports',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_every_role_reaches_only_the_pages_in_the_rbac_matrix(): void
    {
        foreach (array_keys(config('rbac.roles')) as $role) {
            $user = User::factory()->role($role)->create();

            foreach (self::PAGES as $route => $permission) {
                $response = $this->actingAs($user)->get(route($route));
                if ($this->roleHas($role, $permission)) {
                    $this->assertEquals(200, $response->status(), "{$role} should open {$route}");
                } else {
                    $this->assertEquals(403, $response->status(), "{$role} should be blocked from {$route}");
                }
            }
        }
    }

    public function test_every_role_sidebar_matches_create_edit_and_approval_permissions(): void
    {
        foreach (array_keys(config('rbac.roles')) as $role) {
            $html = $this->actingAs(User::factory()->role($role)->create())
                ->get(route('dashboard'))
                ->assertOk()
                ->getContent();

            foreach (self::SIDEBAR as $permission => $marker) {
                if ($this->roleHas($role, $permission)) {
                    $this->assertStringContainsString($marker, $html, "{$role} sidebar missing {$permission}");
                } else {
                    $this->assertStringNotContainsString($marker, $html, "{$role} sidebar should hide {$permission}");
                }
            }
        }
    }

    public function test_approve_and_reject_are_limited_to_approver_roles(): void
    {
        $requester = User::factory()->role('slt_employee')->create();
        $resource = Resource::factory()->create(['status' => 'active', 'name_model' => 'Hall A']);
        $pending = $this->pendingReservation($requester, $resource);

        $this->actingAs($requester)->post(route('approvals.decision', $pending), [
            'decision' => 'approve',
            'comment' => 'Self approve',
        ])->assertForbidden();

        $this->actingAs(User::factory()->role('coordinator')->create())
            ->post(route('approvals.decision', $pending), [
                'decision' => 'approve',
                'comment' => 'Coordinator approve',
            ])->assertForbidden();

        $admin = User::factory()->role('admin')->create();
        $this->actingAs($admin)->post(route('approvals.decision', $pending), [
            'decision' => 'approve',
            'comment' => 'Approved for the lab session',
        ])->assertRedirect(route('approvals.index'));
        $this->assertDatabaseHas('reservations', [
            'id' => $pending->id,
            'status' => ReservationStatus::APPROVED->value,
        ]);

        $second = $this->pendingReservation($requester, $resource);
        $this->actingAs($admin)->post(route('approvals.decision', $second), [
            'decision' => 'reject',
            'comment' => 'Hall is already booked',
        ])->assertRedirect(route('approvals.index'));
        $this->assertDatabaseHas('reservations', [
            'id' => $second->id,
            'status' => ReservationStatus::REJECTED->value,
        ]);
    }

    public function test_resource_owner_can_decide_only_owned_resources(): void
    {
        $owner = User::factory()->role('resource_owner')->create(['name' => 'Owned Lab Manager']);
        $requester = User::factory()->role('slt_employee')->create();
        $owned = Resource::factory()->create([
            'status' => 'active',
            'resource_owner' => $owner->name,
            'name_model' => 'Owned Hall',
        ]);
        $other = Resource::factory()->create([
            'status' => 'active',
            'resource_owner' => 'Someone Else',
            'name_model' => 'Other Hall',
        ]);

        $ownedBooking = $this->pendingReservation($requester, $owned);
        $otherBooking = $this->pendingReservation($requester, $other);

        $this->actingAs($owner)->get(route('approvals.index'))
            ->assertOk()
            ->assertSee('Owned Hall')
            ->assertDontSee('Other Hall');

        $this->actingAs($owner)->post(route('approvals.decision', $ownedBooking), [
            'decision' => 'approve',
            'comment' => 'Resource is free',
        ])->assertRedirect(route('approvals.index'));

        $this->actingAs($owner)->post(route('approvals.decision', $otherBooking), [
            'decision' => 'approve',
            'comment' => 'Not my resource',
        ])->assertForbidden();
    }

    public function test_employees_cancel_own_bookings_but_not_other_people(): void
    {
        $employee = User::factory()->role('slt_employee')->create();
        $other = User::factory()->role('slt_employee')->create();
        $resource = Resource::factory()->create(['status' => 'active']);
        $own = $this->pendingReservation($employee, $resource);
        $theirs = $this->pendingReservation($other, $resource);

        $this->actingAs($employee)->post(route('reservations.cancel', $own), [
            'cancellation_reason' => 'Session moved',
        ])->assertRedirect(route('reservations.index'));

        $this->actingAs($employee)->post(route('reservations.cancel', $theirs), [
            'cancellation_reason' => 'Not mine',
        ])->assertForbidden();
    }

    public function test_only_resource_managers_can_edit_and_delete_inventory(): void
    {
        $resource = Resource::factory()->create(['status' => 'active', 'name_model' => 'Hall A']);
        $resource->load('type');
        $payload = [
            'category_id' => $resource->type->resource_category_id,
            'resource_type_id' => $resource->resource_type_id,
            'location_id' => $resource->location_id,
            'name_model' => 'Hall A updated',
            'status' => 'active',
        ];

        $this->actingAs(User::factory()->role('slt_employee')->create())
            ->putJson(route('resources.update', $resource), $payload)
            ->assertForbidden();
        $this->actingAs(User::factory()->role('management')->create())
            ->putJson(route('resources.update', $resource), $payload)
            ->assertForbidden();
        $this->actingAs(User::factory()->role('management')->create())
            ->get(route('resources.index'))
            ->assertOk();

        $this->actingAs(User::factory()->role('admin')->create())
            ->putJson(route('resources.update', $resource), $payload)
            ->assertOk();
        $this->assertDatabaseHas('resources', ['id' => $resource->id, 'name_model' => 'Hall A updated']);
    }

    public function test_calendar_data_is_scoped_for_employees_and_open_for_staff(): void
    {
        $employee = User::factory()->role('slt_employee')->create();
        $admin = User::factory()->role('admin')->create();
        $resource = Resource::factory()->create(['status' => 'active', 'name_model' => 'Shared Hall']);
        $mine = $this->pendingReservation($employee, $resource);
        $staff = $this->pendingReservation($admin, $resource);

        $range = [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addMonths(2)->toIso8601String(),
        ];

        $employeeEvents = $this->actingAs($employee)
            ->getJson(route('reservations.calendar.events', $range))
            ->assertOk()
            ->json();
        $this->assertTrue(collect($employeeEvents)->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $mine->id));
        $this->assertFalse(collect($employeeEvents)->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $staff->id));

        $adminEvents = $this->actingAs($admin)
            ->getJson(route('reservations.calendar.events', $range))
            ->assertOk()
            ->json();
        $this->assertTrue(collect($adminEvents)->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $mine->id));
        $this->assertTrue(collect($adminEvents)->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $staff->id));

        $this->actingAs($employee)->get(route('resources.calendar'))->assertForbidden();
        $this->actingAs($admin)->get(route('resources.calendar'))
            ->assertOk()
            ->assertSee('Shared Hall', false);

        $managementEvents = collect($this->actingAs(User::factory()->role('management')->create())
            ->getJson(route('reservations.calendar.events', $range))
            ->assertOk()
            ->json());
        $this->assertTrue($managementEvents->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $mine->id));
        $this->assertTrue($managementEvents->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $staff->id));
        $this->actingAs(User::factory()->role('management')->create())
            ->get(route('resources.calendar'))
            ->assertForbidden();
    }

    public function test_resource_owner_calendars_only_include_owned_resources(): void
    {
        $owner = User::factory()->role('resource_owner')->create(['name' => 'Owned Lab Manager']);
        $requester = User::factory()->role('slt_employee')->create();
        $owned = Resource::factory()->create([
            'status' => 'active',
            'resource_owner' => $owner->name,
            'name_model' => 'Owned Hall',
        ]);
        $other = Resource::factory()->create([
            'status' => 'active',
            'resource_owner' => 'Someone Else',
            'name_model' => 'Other Hall',
        ]);
        $ownedBooking = $this->pendingReservation($requester, $owned);
        $otherBooking = $this->pendingReservation($requester, $other);
        $range = [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addMonths(2)->toIso8601String(),
        ];

        $events = collect($this->actingAs($owner)
            ->getJson(route('reservations.calendar.events', $range))
            ->assertOk()
            ->json());
        $this->assertTrue($events->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $ownedBooking->id));
        $this->assertFalse($events->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $otherBooking->id));

        $html = $this->actingAs($owner)->get(route('resources.calendar'))
            ->assertOk()
            ->assertSee('Owned Hall', false)
            ->getContent();
        $this->assertStringNotContainsString('"title":"Other Hall"', $html);
    }

    public function test_hostel_manager_sees_hostel_bookings_on_the_reservation_calendar(): void
    {
        $coordinator = User::factory()->role('coordinator')->create();
        $manager = User::factory()->role('hostel_manager')->create();
        $employee = User::factory()->role('slt_employee')->create();
        $room = Resource::factory()->create(['status' => 'active', 'name_model' => 'Welisara Single 101']);
        $hall = Resource::factory()->create(['status' => 'active', 'name_model' => 'Shared Hall']);

        $hostel = $this->pendingReservation($coordinator, $room);
        $hostel->update(['type' => ReservationType::HOSTEL->value]);
        $stay = $hostel->items()->first();
        $stay->update(['ends_at' => $stay->starts_at->copy()->addHours(61)]);
        $lab = $this->pendingReservation($employee, $hall);

        $range = [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addMonths(2)->toIso8601String(),
        ];

        $events = collect($this->actingAs($manager)
            ->getJson(route('reservations.calendar.events', $range))
            ->assertOk()
            ->json());

        $this->assertTrue($events->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $hostel->id));
        $this->assertFalse($events->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $lab->id));
        $this->assertTrue($events->contains(fn ($event) => ($event['extendedProps']['url'] ?? null) === route('hostel.show', $hostel)));
        $this->assertTrue($events->contains(fn ($event) => ($event['extendedProps']['reservationId'] ?? null) === $hostel->id
            && ($event['extendedProps']['reservationType'] ?? null) === ReservationType::HOSTEL->value
            && str_contains((string) ($event['extendedProps']['duration'] ?? ''), 'night')));
    }

    public function test_sms_users_cannot_create_and_canteen_cannot_open_create_form(): void
    {
        $this->actingAs(User::factory()->role('nebula_sms_user')->create())
            ->get(route('reservations.create'))
            ->assertForbidden();
        $this->actingAs(User::factory()->role('canteen')->create())
            ->get(route('canteen.create'))
            ->assertForbidden();
        $this->actingAs(User::factory()->role('hostel_manager')->create())
            ->get(route('hostel.create'))
            ->assertForbidden();
        $this->actingAs(User::factory()->role('coordinator')->create())
            ->get(route('hostel.create'))
            ->assertOk();
    }

    private function roleHas(string $role, string $permission): bool
    {
        $allowed = config('rbac.role_permissions.'.$role, []);

        return in_array('*', $allowed, true) || in_array($permission, $allowed, true);
    }

    private function pendingReservation(User $requester, Resource $resource): Reservation
    {
        $start = now('UTC')->addDay()->setTime(4, 30);

        $reservation = Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'location_id' => $resource->location_id,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'reservation_date' => now('Asia/Colombo')->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id,
            'resource_id' => $resource->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'resource_name_snapshot' => $resource->name_model,
        ]);

        return $reservation;
    }
}
