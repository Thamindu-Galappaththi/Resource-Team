<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SetupPasswordNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_creating_a_user_sends_a_password_setup_email(): void
    {
        Notification::fake();

        $this->actingAs(User::factory()->role('admin')->create())
            ->post('/user-management/create-user', [
                'slt_employee' => 'no',
                'name' => 'Invited User',
                'nic' => '199512345678',
                'email' => 'invited@example.com',
                'phone' => '0771234567',
                'user_roles' => ['admin'],
                'location' => 'Nebula Institute of Technology - Welisara',
            ])
            ->assertRedirect(route('create.user'));

        $user = User::query()->where('email', 'invited@example.com')->firstOrFail();

        Notification::assertSentTo($user, SetupPasswordNotification::class, function (SetupPasswordNotification $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Set up your Resource Reservation password'
                && str_contains($mail->view ?? '', 'emails.setup-password')
                && ($mail->viewData['expiresMinutes'] ?? null) === 60
                && str_contains((string) ($mail->viewData['url'] ?? ''), '/setup-password/');
        });
    }

    public function test_password_setup_page_is_public_and_shows_email_as_read_only(): void
    {
        $user = User::factory()->role('admin')->create([
            'email' => 'setup@nebula.local',
        ]);
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]))
            ->assertOk()
            ->assertSee('Set up your password')
            ->assertSee('setup@nebula.local')
            ->assertSee('readonly', false)
            ->assertSee('New password')
            ->assertSee('Re-enter password')
            ->assertSee('60 minutes')
            ->assertDontSee('page-wrapper', false)
            ->assertDontSee('left-sidebar', false)
            ->assertDontSee('Enter your e-mail address');
    }

    public function test_invalid_setup_link_shows_an_expired_public_page(): void
    {
        $user = User::factory()->role('admin')->create();

        $this->get(route('password.reset', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
        ]))
            ->assertOk()
            ->assertSee('Link expired')
            ->assertSee('60 minutes')
            ->assertDontSee('Set password')
            ->assertDontSee('left-sidebar', false);
    }

    public function test_setting_a_password_redirects_to_login(): void
    {
        $user = User::factory()->role('admin')->create([
            'email' => 'ready@nebula.local',
            'password' => 'old-password',
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->post(route('login.attempt'), [
            'username' => $user->email,
            'password' => 'new-password1',
        ])->assertRedirect(route('dashboard'));
    }
}
