<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\CanteenReservation;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Resource;
use App\Models\User;
use App\Notifications\CanteenReservationCreated;
use App\Notifications\NewCanteenReservationPending;
use App\Notifications\NewReservationPending;
use App\Notifications\NewUserCreated;
use App\Notifications\ReservationCancelled;
use App\Notifications\ReservationCreated;
use App\Notifications\ReservationDecision;
use App\Services\NotificationPresenter;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_notification_bell_is_shown_to_every_signed_in_user(): void
    {
        $this->actingAs(User::factory()->role('coordinator')->create())
            ->get(route('user.profile'))
            ->assertOk()
            ->assertSee('id="notificationsDropdown"', false)
            ->assertSee("You're all caught up.", false);
    }

    public function test_notification_bell_lists_notifications_with_unread_count(): void
    {
        $viewer = User::factory()->role('coordinator')->create();
        $creator = User::factory()->role('admin')->create(['name' => 'Asha Admin']);
        $newUser = User::factory()->role('coordinator')->create(['name' => 'Nimal Perera']);

        $viewer->notify(new NewUserCreated($newUser, $creator));

        $this->actingAs($viewer)
            ->get(route('user.profile'))
            ->assertOk()
            ->assertSee('Asha Admin created a new user account for Nimal Perera.')
            ->assertSee('class="nb-badge"', false)
            ->assertSee('1 new')
            ->assertSee('ti-user-plus', false);
    }

    public function test_marking_notifications_read_only_affects_the_current_user(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $creator = User::factory()->role('admin')->create();
        $newUser = User::factory()->role('coordinator')->create();
        $viewer = User::factory()->role('coordinator')->create();
        $other = User::factory()->role('coordinator')->create();

        $viewer->notify(new NewUserCreated($newUser, $creator));
        $other->notify(new NewUserCreated($newUser, $creator));

        $this->actingAs($viewer)
            ->postJson(route('notifications.mark-read'))
            ->assertOk()
            ->assertJson(['success' => true, 'unread_count' => 0]);

        $this->assertSame(0, $viewer->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }

    public function test_guests_cannot_mark_notifications_read(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->post(route('notifications.mark-read'))->assertRedirect(route('login'));
    }

    public function test_creating_a_user_notifies_users_who_can_manage_users(): void
    {
        Notification::fake();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $creator = User::factory()->role('admin')->create();
        $otherAdmin = User::factory()->role('admin')->create();
        $superAdmin = User::factory()->role('super_admin')->create();
        $developer = User::factory()->role('developer')->create();
        $inactiveAdmin = User::factory()->role('admin')->inactive()->create();
        $coordinator = User::factory()->role('coordinator')->create();
        $management = User::factory()->role('management')->create();

        $this->actingAs($creator)
            ->post(route('create.user.store'), [
                'slt_employee' => 'no',
                'name' => 'New Admin',
                'nic' => '200012345670',
                'email' => 'new.admin@example.com',
                'phone' => '0771234567',
                'user_roles' => ['admin'],
                'location' => 'Nebula Institute of Technology - Welisara',
            ])
            ->assertRedirect(route('create.user'));

        $newUser = User::query()->where('email', 'new.admin@example.com')->firstOrFail();

        Notification::assertSentTo([$otherAdmin, $superAdmin, $developer], NewUserCreated::class, function (NewUserCreated $notification) use ($newUser, $creator) {
            return $notification->user->is($newUser) && $notification->creator->is($creator);
        });
        Notification::assertNotSentTo([$creator, $inactiveAdmin, $coordinator, $management, $newUser], NewUserCreated::class);
    }

    public function test_new_resource_reservation_notifies_approval_permission_holders(): void
    {
        Notification::fake();

        $requester = User::factory()->role('coordinator')->create();
        $approver = User::factory()->role('resource_owner')->create();
        $admin = User::factory()->role('admin')->create();
        $management = User::factory()->role('management')->create();
        $resource = Resource::factory()->create();

        $this->actingAs($requester)->post(route('reservations.store'), [
            'resource_id' => $resource->id,
            'resource_ids' => [$resource->id],
            'requester_id' => $requester->id,
            'location_id' => $resource->location_id,
            'category_id' => $resource->type->resource_category_id,
            'reservation_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'purpose' => 'Training session',
            'attendee_count' => 10,
        ])->assertRedirect();

        Notification::assertNotSentTo($requester, ReservationCreated::class);
        Notification::assertSentTo([$approver, $admin], NewReservationPending::class);
        Notification::assertNotSentTo([$requester, $management], NewReservationPending::class);
    }

    public function test_booking_for_another_user_notifies_that_requester_not_the_creator(): void
    {
        Notification::fake();

        $admin = User::factory()->role('admin')->create();
        $requester = User::factory()->role('slt_employee')->create();
        $resource = Resource::factory()->create();

        $this->actingAs($admin)->post(route('reservations.store'), [
            'resource_id' => $resource->id,
            'resource_ids' => [$resource->id],
            'requester_id' => $requester->id,
            'location_id' => $resource->location_id,
            'category_id' => $resource->type->resource_category_id,
            'reservation_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'purpose' => 'Booked for colleague',
            'attendee_count' => 8,
        ])->assertRedirect();

        Notification::assertSentTo($requester, ReservationCreated::class);
        Notification::assertNotSentTo($admin, ReservationCreated::class);
        Notification::assertNotSentTo($admin, NewReservationPending::class);
    }

    public function test_self_cancel_does_not_notify_the_actor_but_admin_cancel_notifies_requester(): void
    {
        Notification::fake();

        $requester = User::factory()->role('slt_employee')->create();
        $admin = User::factory()->role('admin')->create();
        $resource = Resource::factory()->create(['status' => 'active']);

        $own = Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'location_id' => $resource->location_id,
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $own->id,
            'resource_id' => $resource->id,
        ]);

        $this->actingAs($requester)->post(route('reservations.cancel', $own), [
            'cancellation_reason' => 'Changed my mind',
        ])->assertRedirect();
        Notification::assertNotSentTo($requester, ReservationCancelled::class);

        $theirs = Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'location_id' => $resource->location_id,
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $theirs->id,
            'resource_id' => $resource->id,
        ]);

        $this->actingAs($admin)->post(route('reservations.cancel', $theirs), [
            'cancellation_reason' => 'Room needed elsewhere',
        ])->assertRedirect();
        Notification::assertSentTo($requester, ReservationCancelled::class);
        Notification::assertNotSentTo($admin, ReservationCancelled::class);
    }

    public function test_approval_decision_notifies_the_requester_not_the_approver(): void
    {
        Notification::fake();

        $requester = User::factory()->role('slt_employee')->create();
        $approver = User::factory()->role('admin')->create();
        $resource = Resource::factory()->create(['status' => 'active']);
        $reservation = Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'location_id' => $resource->location_id,
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id,
            'resource_id' => $resource->id,
        ]);

        $this->actingAs($approver)->post(route('approvals.decision', $reservation), [
            'decision' => 'approve',
            'comment' => 'Looks good',
        ])->assertRedirect();

        Notification::assertSentTo($requester, ReservationDecision::class);
        Notification::assertNotSentTo($approver, ReservationDecision::class);
    }

    public function test_canteen_create_notifies_kitchen_not_the_requester(): void
    {
        Notification::fake();

        $requester = User::factory()->role('slt_employee')->create();
        $kitchen = User::factory()->role('canteen')->create();

        $this->actingAs($requester)->post(route('canteen.store'), [
            'reservation_name' => 'Team lunch',
            'meal_type' => 'lunch',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:30',
            'number_of_orders' => 12,
            'order_details' => 'Rice and curry',
        ])->assertRedirect();

        Notification::assertNotSentTo($requester, CanteenReservationCreated::class);
        Notification::assertSentTo($kitchen, CanteenReservationCreated::class);
        Notification::assertNotSentTo($requester, NewCanteenReservationPending::class);

        $this->actingAs($requester)->post(route('canteen.store'), [
            'reservation_name' => 'Large event lunch',
            'meal_type' => 'event_catering',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:00',
            'number_of_orders' => (int) config('canteen.large_group_threshold') + 5,
        ])->assertRedirect();

        Notification::assertSentTo($kitchen, NewCanteenReservationPending::class);
        Notification::assertNotSentTo($requester, NewCanteenReservationPending::class);
    }

    public function test_notification_messages_are_limited_in_the_bell(): void
    {
        $viewer = User::factory()->role('admin')->create();
        $creator = User::factory()->role('admin')->create(['name' => str_repeat('Asha ', 12)]);
        $newUser = User::factory()->role('coordinator')->create(['name' => str_repeat('Nimal ', 12)]);
        $viewer->notify(new NewUserCreated($newUser, $creator));

        $payload = NotificationPresenter::toArray($viewer->notifications()->firstOrFail());
        $this->assertLessThanOrEqual(NotificationPresenter::MESSAGE_LIMIT + 3, mb_strlen($payload['message']));
        $this->assertStringEndsWith('...', $payload['message']);
        $this->assertStringContainsString($newUser->name, $payload['full_message']);

        $this->actingAs($viewer)
            ->get(route('user.profile'))
            ->assertOk()
            ->assertSee($payload['message'], false)
            ->assertSee('title="'.$payload['full_message'].'"', false);
    }

    public function test_notifications_link_to_the_relevant_page(): void
    {
        $viewer = User::factory()->role('admin')->create();
        $reservation = Reservation::factory()->create(['requester_id' => $viewer->id]);
        $hostel = Reservation::factory()->create([
            'requester_id' => $viewer->id,
            'type' => ReservationType::HOSTEL->value,
        ]);
        $canteen = CanteenReservation::factory()->create(['requested_by_user_id' => $viewer->id]);

        $viewer->notify(new ReservationCreated($reservation));
        $viewer->notify(new NewReservationPending($reservation));
        $viewer->notify(new NewReservationPending($hostel));
        $viewer->notify(new CanteenReservationCreated($canteen));

        $this->actingAs($viewer)
            ->get(route('user.profile'))
            ->assertOk()
            ->assertSee('href="'.route('reservations.show', $reservation).'"', false)
            ->assertSee('href="'.route('approvals.index', ['search' => $reservation->reference]).'"', false)
            ->assertSee('href="'.route('hostel.show', $hostel).'"', false)
            ->assertSee('href="'.route('canteen.show', $canteen).'"', false);
    }

    public function test_resource_owner_can_open_a_pending_notification_for_any_resource(): void
    {
        $owner = User::factory()->role('resource_owner')->create();
        $admin = User::factory()->role('admin')->create();
        $resource = Resource::factory()->create([
            'status' => 'active',
            'resource_owner' => 'Someone Else',
            'name_model' => 'Unowned Hall',
        ]);
        $reservation = Reservation::factory()->create([
            'requester_id' => $admin->id,
            'created_by_user_id' => $admin->id,
            'location_id' => $resource->location_id,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'purpose' => 'Admin created booking',
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id,
            'resource_id' => $resource->id,
            'resource_name_snapshot' => $resource->name_model,
        ]);

        $owner->notify(new NewReservationPending($reservation));
        $url = NotificationPresenter::toArray($owner->notifications()->firstOrFail())['url'];

        $this->actingAs($owner)
            ->get($url)
            ->assertOk()
            ->assertSee($reservation->reference, false)
            ->assertSee('Unowned Hall')
            ->assertSee('Admin created booking');
    }

    public function test_deleting_a_notification_sets_deleted_at_and_hides_it(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $viewer = User::factory()->role('coordinator')->create();
        $creator = User::factory()->role('admin')->create();
        $newUser = User::factory()->role('coordinator')->create();
        $viewer->notify(new NewUserCreated($newUser, $creator));

        $notification = $viewer->notifications()->firstOrFail();

        $this->actingAs($viewer)
            ->deleteJson(route('notifications.destroy', $notification->id))
            ->assertOk()
            ->assertJson(['success' => true, 'unread_count' => 0]);

        $this->assertSoftDeleted('notifications', ['id' => $notification->id]);
        $this->assertSame(0, $viewer->fresh()->notifications()->count());

        $this->get(route('user.profile'))
            ->assertOk()
            ->assertDontSee($newUser->name)
            ->assertSee("You're all caught up.", false);
    }

    public function test_notification_feed_returns_current_items_and_skips_deleted(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $viewer = User::factory()->role('admin')->create();
        $reservation = Reservation::factory()->create(['requester_id' => $viewer->id]);
        $viewer->notify(new ReservationCreated($reservation));
        $keep = $viewer->notifications()->firstOrFail();

        $creator = User::factory()->role('admin')->create();
        $newUser = User::factory()->role('coordinator')->create();
        $viewer->notify(new NewUserCreated($newUser, $creator));
        $viewer->notifications()->where('id', '!=', $keep->id)->firstOrFail()->delete();

        $this->actingAs($viewer)
            ->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.id', $keep->id)
            ->assertJsonPath('notifications.0.url', route('reservations.show', $reservation));
    }
}
