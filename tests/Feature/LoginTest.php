<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_page_is_shown_to_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Resource Reservation')
            ->assertSee('Sign in to manage your institutional assets')
            ->assertSee('Username')
            ->assertSee('Sign In')
            ->assertSee('Forgot Password?');
    }

    public function test_user_can_login_with_nic(): void
    {
        $user = User::factory()->role('admin')->create([
            'nic' => '199012345678',
            'password' => 'secret-pass',
        ]);

        $this->post(route('login.attempt'), [
            'username' => $user->nic,
            'password' => 'secret-pass',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->role('admin')->create([
            'email' => 'admin@nebula.local',
            'password' => 'secret-pass',
        ]);

        $this->post(route('login.attempt'), [
            'username' => 'admin@nebula.local',
            'password' => 'secret-pass',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_invalid_credentials_show_an_error(): void
    {
        $this->from(route('login'))
            ->post(route('login.attempt'), [
                'username' => '199012345678',
                'password' => 'wrong',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->role('admin')->inactive()->create([
            'nic' => '199012345678',
            'password' => 'secret-pass',
        ]);

        $this->from(route('login'))
            ->post(route('login.attempt'), [
                'username' => $user->nic,
                'password' => 'secret-pass',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_successful_login_opens_the_role_dashboard(): void
    {
        $user = User::factory()->role('coordinator')->create([
            'nic' => '199001010003',
            'password' => 'secret-pass',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Coordinator Dashboard');
    }
}
