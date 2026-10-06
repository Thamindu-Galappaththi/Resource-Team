<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_open_brd_navigation_pages(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('approvals.index'))->assertOk()->assertSee('Approvals');
        $this->actingAs($admin)->get(route('approvals.special'))->assertOk();
        $this->actingAs($admin)->get(route('hostel.index'))->assertOk()->assertSee('Hostel Reservations');
        $this->actingAs($admin)->get(route('hostel.create'))->assertOk();
        $this->actingAs($admin)->get(route('canteen.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('canteen.create'))->assertOk();
        $this->actingAs($admin)->get(route('canteen.index'))->assertOk();
        $this->actingAs($admin)->get(route('payments.lecture-fees'))->assertOk();
        $this->actingAs($admin)->get(route('payments.resources'))->assertOk();
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
    }

    public function test_canteen_role_can_view_canteen_pages_but_cannot_create(): void
    {
        $user = User::factory()->role('canteen')->create();

        $this->actingAs($user)->get(route('canteen.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('canteen.index'))->assertOk();
        $this->actingAs($user)->get(route('canteen.create'))->assertForbidden();
        $this->actingAs($user)->get(route('resources.create'))->assertForbidden();
        $this->actingAs($user)->get(route('hostel.create'))->assertForbidden();
    }

    public function test_nebula_sms_user_only_reaches_view_only_reservation_pages(): void
    {
        $user = User::factory()->role('nebula_sms_user')->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('reservations.calendar'))->assertOk();
        $this->actingAs($user)->get(route('reservations.index'))->assertOk();
        $this->actingAs($user)->get(route('reservations.create'))->assertForbidden();
        $this->actingAs($user)->get(route('create.user'))->assertForbidden();
        $this->actingAs($user)->get(route('canteen.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('payments.lecture-fees'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($user)->get(route('hostel.index'))->assertForbidden();
    }

    public function test_resource_owner_can_open_approvals_but_not_special_overrides(): void
    {
        $user = User::factory()->role('resource_owner')->create();

        $this->actingAs($user)->get(route('approvals.index'))->assertOk();
        $this->actingAs($user)->get(route('approvals.special'))->assertForbidden();
        $this->actingAs($user)->get(route('resources.create'))->assertOk();
    }

    public function test_hostel_manager_can_view_hostel_list_but_cannot_create(): void
    {
        $user = User::factory()->role('hostel_manager')->create();

        $this->actingAs($user)->get(route('hostel.index'))->assertOk();
        $this->actingAs($user)->get(route('hostel.create'))->assertForbidden();
        $this->actingAs($user)->get(route('reservations.calendar'))->assertOk();
    }

    public function test_management_can_open_reports_and_payments_only(): void
    {
        $user = User::factory()->role('management')->create();

        $this->actingAs($user)->get(route('reports.index'))->assertOk();
        $this->actingAs($user)->get(route('payments.resources'))->assertOk();
        $this->actingAs($user)->get(route('user.management'))->assertForbidden();
        $this->actingAs($user)->get(route('canteen.create'))->assertForbidden();
    }

    public function test_admin_dashboard_sidebar_lists_brd_modules(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Create User')
            ->assertSee('User Management')
            ->assertSee('Reservation Calendar')
            ->assertSee('Create Reservations')
            ->assertSee('Existing Reservations')
            ->assertSee('Create Resources')
            ->assertSee('Existing Resources')
            ->assertSee('Resource Calendar')
            ->assertSee('Approvals')
            ->assertSee('Special Approvals')
            ->assertSee('Hostel Reservations')
            ->assertSee('Create Hostel Reservation')
            ->assertSee('Canteen Dashboard')
            ->assertSee('Create Canteen Reservation')
            ->assertSee('Existing Canteen Reservations')
            ->assertSee('Lecture Fees')
            ->assertSee('Resource Payments')
            ->assertSee('Reports & Analytics', false)
            ->assertDontSee('hide-menu">Maintenance', false);
    }

    public function test_canteen_sidebar_does_not_show_create_canteen_reservation(): void
    {
        $user = User::factory()->role('canteen')->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Canteen Dashboard')
            ->assertSee('Existing Canteen Reservations')
            ->assertDontSee('Create Canteen Reservation')
            ->assertDontSee('Create Resources');
    }
}
