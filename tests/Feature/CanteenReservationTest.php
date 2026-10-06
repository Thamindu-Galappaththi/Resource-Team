<?php

namespace Tests\Feature;

use App\Models\CanteenReservation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CanteenReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_create_valid_reservation_succeeds_and_defaults_to_confirmed_status(): void
    {
        $user = User::factory()->role('slt_employee')->create();

        $response = $this->actingAs($user)->post(route('canteen.store'), [
            'reservation_name' => 'Team lunch',
            'requested_by_user_id' => $user->id,
            'meal_type' => 'lunch',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:30',
            'number_of_orders' => 12,
            'order_details' => 'Rice and curry',
            'special_remarks' => 'No onions',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('canteen_reservations', [
            'requested_by_user_id' => $user->id,
            'reservation_name' => 'Team lunch',
            'status' => 'confirmed',
        ]);
    }

    public function test_reservation_uses_authenticated_user_as_requester(): void
    {
        $user = User::factory()->role('slt_employee')->create();
        $otherUser = User::factory()->role('slt_employee')->create();

        $this->actingAs($user)->post(route('canteen.store'), [
            'reservation_name' => 'Team lunch',
            'requested_by_user_id' => $otherUser->id,
            'meal_type' => 'lunch',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:30',
            'number_of_orders' => 12,
        ])->assertRedirect(route('canteen.show', CanteenReservation::query()->first()));

        $this->assertDatabaseHas('canteen_reservations', [
            'reservation_name' => 'Team lunch',
            'requested_by_user_id' => $user->id,
        ]);
    }

    public function test_past_date_is_rejected_with_validation_error(): void
    {
        $user = User::factory()->role('slt_employee')->create();

        $this->actingAs($user)
            ->from(route('canteen.create'))
            ->post(route('canteen.store'), [
                'reservation_name' => 'Past booking',
                'requested_by_user_id' => $user->id,
                'meal_type' => 'breakfast',
                'reservation_date' => now()->subDay()->toDateString(),
                'reservation_time' => '08:00',
                'number_of_orders' => 2,
            ])
            ->assertSessionHasErrors('reservation_date');
    }

    public function test_number_of_orders_is_rejected_when_zero_or_missing(): void
    {
        $user = User::factory()->role('slt_employee')->create();

        $this->actingAs($user)
            ->post(route('canteen.store'), [
                'reservation_name' => 'Bad order',
                'requested_by_user_id' => $user->id,
                'meal_type' => 'snacks',
                'reservation_date' => now()->addDay()->toDateString(),
                'reservation_time' => '15:30',
            ])
            ->assertSessionHasErrors('number_of_orders');

        $this->actingAs($user)
            ->post(route('canteen.store'), [
                'reservation_name' => 'Bad order',
                'requested_by_user_id' => $user->id,
                'meal_type' => 'snacks',
                'reservation_date' => now()->addDay()->toDateString(),
                'reservation_time' => '15:30',
                'number_of_orders' => 0,
            ])
            ->assertSessionHasErrors('number_of_orders');
    }

    public function test_large_group_order_is_flagged_pending(): void
    {
        $user = User::factory()->role('slt_employee')->create();

        $this->actingAs($user)->post(route('canteen.store'), [
            'reservation_name' => 'Large event lunch',
            'requested_by_user_id' => $user->id,
            'meal_type' => 'event_catering',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'number_of_orders' => config('canteen.large_group_threshold') + 5,
        ]);

        $this->assertDatabaseHas('canteen_reservations', [
            'requested_by_user_id' => $user->id,
            'reservation_name' => 'Large event lunch',
            'status' => 'pending',
        ]);
    }

    public function test_approve_transition_and_notification_are_sent_to_requester(): void
    {
        Notification::fake();

        $requester = User::factory()->role('slt_employee')->create();
        $approver = User::factory()->role('admin')->create();
        $reservation = CanteenReservation::factory()->create([
            'requested_by_user_id' => $requester->id,
            'status' => 'pending',
        ]);

        $this->actingAs($approver)
            ->patch(route('canteen.status', $reservation), [
                'status' => 'confirmed',
                'approval_comments' => 'Approved',
            ])
            ->assertRedirect();

        $reservation->refresh();
        $this->assertSame('confirmed', $reservation->status);
        Notification::assertSentTo($requester, \App\Notifications\CanteenReservationStatusUpdated::class);
    }

    public function test_reject_without_comment_is_rejected_by_validation(): void
    {
        $requester = User::factory()->role('slt_employee')->create();
        $approver = User::factory()->role('admin')->create();
        $reservation = CanteenReservation::factory()->create([
            'requested_by_user_id' => $requester->id,
            'status' => 'pending',
        ]);

        $this->actingAs($approver)
            ->patch(route('canteen.status', $reservation), [
                'status' => 'rejected',
            ])
            ->assertSessionHasErrors('approval_comments');
    }

    public function test_cancel_before_reservation_date_succeeds_and_after_completion_is_blocked(): void
    {
        $requester = User::factory()->role('slt_employee')->create();
        $reservation = CanteenReservation::factory()->create([
            'requested_by_user_id' => $requester->id,
            'status' => 'confirmed',
            'reservation_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($requester)
            ->delete(route('canteen.destroy', $reservation))
            ->assertRedirect();

        $this->assertDatabaseHas('canteen_reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
        ]);

        $completed = CanteenReservation::factory()->create([
            'requested_by_user_id' => $requester->id,
            'status' => 'completed',
            'reservation_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($requester)
            ->delete(route('canteen.destroy', $completed))
            ->assertForbidden();
    }

    public function test_status_filter_returns_matching_records(): void
    {
        $admin = User::factory()->role('admin')->create();
        $requester = User::factory()->role('slt_employee')->create();

        CanteenReservation::factory()->create([
            'requested_by_user_id' => $requester->id,
            'status' => 'pending',
        ]);
        CanteenReservation::factory()->create([
            'requested_by_user_id' => $requester->id,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->get(route('canteen.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('pending');
    }

    public function test_canteen_role_cannot_access_create_route(): void
    {
        $user = User::factory()->role('canteen')->create();

        $this->actingAs($user)
            ->get(route('canteen.create'))
            ->assertForbidden();
    }

    public function test_canteen_pages_render_for_authorized_users(): void
    {
        $user = User::factory()->role('canteen')->create();

        $this->actingAs($user)
            ->get(route('canteen.dashboard'))
            ->assertOk()
            ->assertSee('Expected today')
            ->assertDontSee('Kitchen Readiness');

        $this->get(route('canteen.index'))
            ->assertOk()
            ->assertSee('Reservation register');

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('canteen.maintenance'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('canteen.reservations.index'));
    }

    public function test_developer_can_see_other_users_canteen_reservations(): void
    {
        $developer = User::factory()->role('developer')->create();
        $owner = User::factory()->role('slt_employee')->create(['name' => 'Owner Employee']);
        CanteenReservation::factory()->create([
            'requested_by_user_id' => $owner->id,
            'reservation_name' => 'Developer visibility check',
            'status' => 'confirmed',
        ]);

        $this->actingAs($developer)
            ->get(route('canteen.index'))
            ->assertOk()
            ->assertSee('Developer visibility check')
            ->assertSee('Owner Employee');
    }

    public function test_create_page_lists_locations_in_campus_order(): void
    {
        $user = User::factory()->role('slt_employee')->create();
        \App\Models\Location::factory()->create(['name' => 'Peradeniya']);
        \App\Models\Location::factory()->create(['name' => 'Welisara']);
        \App\Models\Location::factory()->create(['name' => 'Moratuwa']);

        $html = $this->actingAs($user)->get(route('canteen.create'))->assertOk()->getContent();
        $welisara = strpos($html, 'Welisara');
        $moratuwa = strpos($html, 'Moratuwa');
        $peradeniya = strpos($html, 'Peradeniya');

        $this->assertNotFalse($welisara);
        $this->assertTrue($welisara < $moratuwa && $moratuwa < $peradeniya);
    }

    public function test_reservation_form_renders_for_requesters(): void
    {
        $user = User::factory()->role('slt_employee')->create();

        $this->actingAs($user)
            ->get(route('canteen.create'))
            ->assertOk()
            ->assertSee('Create Canteen Reservation')
            ->assertSee('Order requirements')
            ->assertSee('location_id');
    }

    public function test_requester_cannot_view_or_edit_another_users_reservation(): void
    {
        $owner = User::factory()->role('slt_employee')->create();
        $other = User::factory()->role('slt_employee')->create();
        $reservation = CanteenReservation::factory()->create([
            'requested_by_user_id' => $owner->id,
            'status' => 'pending',
        ]);

        $this->actingAs($other)
            ->get(route('canteen.show', $reservation))
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('canteen.edit', $reservation))
            ->assertForbidden();
    }

    public function test_canteen_staff_can_approve_pending_reservation_from_the_list(): void
    {
        $staff = User::factory()->role('canteen')->create();
        $requester = User::factory()->role('slt_employee')->create();
        $reservation = CanteenReservation::factory()->create([
            'requested_by_user_id' => $requester->id,
            'reservation_name' => 'Exam day lunches',
            'status' => 'pending',
            'number_of_orders' => 80,
        ]);

        $this->actingAs($staff)
            ->get(route('canteen.index'))
            ->assertOk()
            ->assertSee('Approve')
            ->assertSee('Reject');

        $this->actingAs($staff)
            ->get(route('canteen.show', $reservation))
            ->assertOk()
            ->assertSee('Review request')
            ->assertSee('Approve reservation');

        $this->actingAs($staff)
            ->patch(route('canteen.status', $reservation), ['status' => 'confirmed'])
            ->assertRedirect(route('canteen.show', $reservation));

        $this->assertDatabaseHas('canteen_reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
            'approved_by_user_id' => $staff->id,
        ]);
    }

    public function test_dashboard_counts_only_pending_and_confirmed_orders(): void
    {
        $staff = User::factory()->role('canteen')->create();
        $requester = User::factory()->role('slt_employee')->create();

        CanteenReservation::factory()->create([
            'requested_by_user_id' => $requester->id,
            'reservation_name' => 'Confirmed kitchen lunch',
            'reservation_date' => now()->toDateString(),
            'reservation_time' => '12:00:00',
            'number_of_orders' => 17,
            'status' => 'confirmed',
        ]);
        CanteenReservation::factory()->create([
            'requested_by_user_id' => $requester->id,
            'reservation_name' => 'Cancelled banquet',
            'reservation_date' => now()->toDateString(),
            'reservation_time' => '13:00:00',
            'number_of_orders' => 91,
            'status' => 'cancelled',
        ]);

        $this->actingAs($staff)
            ->get(route('canteen.dashboard'))
            ->assertOk()
            ->assertSee('>17</h3>', false)
            ->assertDontSee('>108</h3>', false);
    }

    public function test_store_accepts_browser_time_with_seconds(): void
    {
        $user = User::factory()->role('slt_employee')->create();

        $this->actingAs($user)->post(route('canteen.store'), [
            'reservation_name' => 'Timed lunch',
            'meal_type' => 'lunch',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:30:00',
            'number_of_orders' => 8,
        ])->assertRedirect();

        $this->assertDatabaseHas('canteen_reservations', [
            'reservation_name' => 'Timed lunch',
            'requested_by_user_id' => $user->id,
        ]);
    }

    public function test_edit_form_uses_time_without_seconds(): void
    {
        $user = User::factory()->role('slt_employee')->create();
        $reservation = CanteenReservation::factory()->create([
            'requested_by_user_id' => $user->id,
            'status' => 'pending',
            'reservation_time' => '12:30:00',
        ]);

        $this->actingAs($user)
            ->get(route('canteen.edit', $reservation))
            ->assertOk()
            ->assertSee('value="12:30"', false);
    }
}
