<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewUserCreated;
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

    public function test_creating_a_user_notifies_other_active_admins_only(): void
    {
        Notification::fake();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $creator = User::factory()->role('admin')->create();
        $otherAdmin = User::factory()->role('admin')->create();
        $superAdmin = User::factory()->role('super_admin')->create();
        $inactiveAdmin = User::factory()->role('admin')->inactive()->create();
        $coordinator = User::factory()->role('coordinator')->create();

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

        Notification::assertSentTo([$otherAdmin, $superAdmin], NewUserCreated::class, function (NewUserCreated $notification) use ($newUser, $creator) {
            return $notification->user->is($newUser) && $notification->creator->is($creator);
        });
        Notification::assertNotSentTo([$creator, $inactiveAdmin, $coordinator, $newUser], NewUserCreated::class);
    }
}
