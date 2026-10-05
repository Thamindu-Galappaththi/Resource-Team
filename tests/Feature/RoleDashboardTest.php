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

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_each_role_sees_its_own_dashboard_title_without_placeholders(): void
    {
        $titles = [
            'developer' => 'Developer Dashboard',
            'super_admin' => 'Super Admin Dashboard',
            'admin' => 'Admin Dashboard',
            'coordinator' => 'Coordinator Dashboard',
            'resource_owner' => 'Resource Owner Dashboard',
            'slt_employee' => 'SLT Employee Dashboard',
            'nebula_sms_user' => 'Nebula SMS User Dashboard',
            'management' => 'Management Dashboard',
            'canteen' => 'Canteen Dashboard',
            'hostel_manager' => 'Hostel Manager Dashboard',
        ];

        foreach ($titles as $slug => $title) {
            $user = User::factory()->role($slug)->create();

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee($title)
                ->assertDontSee('Placeholder. This widget will be connected in a later module.');
        }
    }

    public function test_developer_dashboard_shows_live_platform_metrics(): void
    {
        $developer = User::factory()->role('developer')->create();
        User::factory()->role('admin')->create();
        Reservation::factory()->create(['title' => 'Platform lab booking']);

        $this->actingAs($developer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Active users')
            ->assertSee('Exceptions')
            ->assertSee('Users by role')
            ->assertSee('Platform lab booking')
            ->assertSee('User management');
    }

    public function test_employee_dashboard_only_lists_own_reservations(): void
    {
        $employee = User::factory()->role('slt_employee')->create();
        $other = User::factory()->role('admin')->create();

        Reservation::factory()->create([
            'requester_id' => $employee->id,
            'created_by_user_id' => $employee->id,
            'title' => 'My projector booking',
            'status' => ReservationStatus::PENDING_APPROVAL->value,
        ]);

        Reservation::factory()->create([
            'requester_id' => $other->id,
            'created_by_user_id' => $other->id,
            'title' => 'Secret other booking',
        ]);

        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My projector booking')
            ->assertDontSee('Secret other booking')
            ->assertSee('New reservation');
    }

    public function test_sms_user_dashboard_is_view_only(): void
    {
        $user = User::factory()->role('nebula_sms_user')->create();

        Reservation::factory()->create([
            'requester_id' => $user->id,
            'created_by_user_id' => $user->id,
            'title' => 'Classroom observation',
            'status' => ReservationStatus::CONFIRMED->value,
            'reservation_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Classroom observation')
            ->assertSee('My schedule')
            ->assertDontSee('New reservation');
    }

    public function test_hostel_manager_dashboard_uses_hostel_metrics(): void
    {
        $manager = User::factory()->role('hostel_manager')->create();

        Reservation::factory()->create([
            'type' => ReservationType::HOSTEL->value,
            'title' => 'Guest wing stay',
            'status' => ReservationStatus::PENDING_APPROVAL->value,
        ]);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rooms ready')
            ->assertSee('Check-ins today')
            ->assertSee('Guest wing stay')
            ->assertSee('Hostel bookings')
            ->assertDontSee('New reservation');
    }

    public function test_canteen_dashboard_shows_order_volume(): void
    {
        $user = User::factory()->role('canteen')->create();

        CanteenReservation::factory()->create([
            'reservation_name' => 'Batch 12 lunch',
            'reservation_date' => now()->toDateString(),
            'number_of_orders' => 40,
            'status' => CanteenReservationStatus::PENDING->value,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Expected today')
            ->assertSee('Batch 12 lunch')
            ->assertSee('Existing orders')
            ->assertDontSee('New order');
    }

    public function test_admin_dashboard_links_to_daily_operations(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pending approvals')
            ->assertSee('Create reservation')
            ->assertSee('Available resources');
    }
}
