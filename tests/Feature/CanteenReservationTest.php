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

        $response = $this->actingAs($user)->post(route('canteen.reservations.store'), [
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

    public function test_past_date_is_rejected_with_validation_error(): void
    {
        $user = User::factory()->role('slt_employee')->create();

        $this->actingAs($user)
            ->from(route('canteen.reservations.create'))
            ->post(route('canteen.reservations.store'), [
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
            ->post(route('canteen.reservations.store'), [
                'reservation_name' => 'Bad order',
                'requested_by_user_id' => $user->id,
                'meal_type' => 'snacks',
                'reservation_date' => now()->addDay()->toDateString(),
                'reservation_time' => '15:30',
            ])
            ->assertSessionHasErrors('number_of_orders');

        $this->actingAs($user)
            ->post(route('canteen.reservations.store'), [
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

        $this->actingAs($user)->post(route('canteen.reservations.store'), [
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
            ->patch(route('canteen.reservations.status', $reservation), [
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
            ->patch(route('canteen.reservations.status', $reservation), [
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
            ->delete(route('canteen.reservations.destroy', $reservation))
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
            ->delete(route('canteen.reservations.destroy', $completed))
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
            ->get(route('canteen.reservations.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('pending');
    }

    public function test_canteen_role_cannot_access_create_route(): void
    {
        $user = User::factory()->role('canteen')->create();

        $this->actingAs($user)
            ->get(route('canteen.reservations.create'))
            ->assertForbidden();
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
            ->get(route('canteen.reservations.show', $reservation))
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('canteen.reservations.edit', $reservation))
            ->assertForbidden();
    }
}
