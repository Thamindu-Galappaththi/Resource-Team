<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            ->assertSee('Choose how you sign in')
            ->assertSee('Login with Microsoft')
            ->assertSee('Nebula Administration')
            ->assertSee('Nebula Institute of Technology')
            ->assertSee('All rights reserved.')
            ->assertSee('id="starfield"', false)
            ->assertSee('NEBULA_STARS', false)
            ->assertSee('js/login.js', false);
    }

    public function test_admin_login_page_shows_the_credentials_form(): void
    {
        $this->get(route('login.admin'))
            ->assertOk()
            ->assertSee('Nebula Administration')
            ->assertSee('Username')
            ->assertSee('Sign In')
            ->assertSee('Forgot Password?')
            ->assertSee('All rights reserved.')
            ->assertSee('id="starfield"', false);
    }

    public function test_empty_login_shows_required_field_messages(): void
    {
        $this->from(route('login.admin'))
            ->followingRedirects()
            ->post(route('login.attempt'), [
                'username' => '',
                'password' => '',
            ])
            ->assertOk()
            ->assertSee('Username is required.')
            ->assertSee('Password is required.')
            ->assertSee('field is-invalid', false);
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
        $this->get(route('dashboard'))->assertOk();
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
        $this->from(route('login.admin'))
            ->post(route('login.attempt'), [
                'username' => '199012345678',
                'password' => 'wrong',
            ])
            ->assertRedirect(route('login.admin'))
            ->assertSessionHasErrors('username');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->role('admin')->inactive()->create([
            'nic' => '199012345678',
            'password' => 'secret-pass',
        ]);

        $this->from(route('login.admin'))
            ->post(route('login.attempt'), [
                'username' => $user->nic,
                'password' => 'secret-pass',
            ])
            ->assertRedirect(route('login.admin'))
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

    public function test_microsoft_login_redirects_to_entra(): void
    {
        $this->configureMicrosoft();

        $response = $this->get(route('login.microsoft'));

        $response->assertRedirect();
        $this->assertStringContainsString('login.microsoftonline.com/organizations/oauth2/v2.0/authorize', $response->headers->get('Location'));
        $this->assertNotEmpty(session('microsoft_oauth_state'));
    }

    public function test_microsoft_login_requires_configuration(): void
    {
        config([
            'services.microsoft.client_id' => '',
            'services.microsoft.client_secret' => '',
        ]);

        $this->get(route('login.microsoft'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('microsoft');
    }

    public function test_slt_employee_can_login_with_microsoft(): void
    {
        $this->configureMicrosoft();
        $this->fakeMicrosoftGraph('staff@slt.lk', 'SLT Staff');

        $user = User::factory()->role('slt_employee')->create([
            'email' => 'staff@slt.lk',
            'slt_employee' => true,
        ]);

        $this->withSession(['microsoft_oauth_state' => 'oauth-state'])
            ->get(route('login.microsoft.callback', [
                'code' => 'auth-code',
                'state' => 'oauth-state',
            ]))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_microsoft_login_creates_slt_employee_when_unknown(): void
    {
        $this->configureMicrosoft();
        $this->fakeMicrosoftGraph('new.staff@slt.com.lk', 'New Staff', 'ms-user-new');

        $this->assertDatabaseMissing('users', ['email' => 'new.staff@slt.com.lk']);

        $this->withSession(['microsoft_oauth_state' => 'oauth-state'])
            ->get(route('login.microsoft.callback', [
                'code' => 'auth-code',
                'state' => 'oauth-state',
            ]))
            ->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'new.staff@slt.com.lk')->first();

        $this->assertNotNull($user);
        $this->assertSame('New Staff', $user->name);
        $this->assertSame('ms-user-new', $user->microsoft_id);
        $this->assertTrue($user->slt_employee);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('slt_employee'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_microsoft_login_updates_existing_slt_employee(): void
    {
        $this->configureMicrosoft();
        $this->fakeMicrosoftGraph('staff@slt.lk', 'Updated Staff', 'ms-user-updated');

        $user = User::factory()->role('slt_employee')->create([
            'email' => 'staff@slt.lk',
            'name' => 'Old Name',
            'slt_employee' => true,
            'microsoft_id' => null,
        ]);

        $this->withSession(['microsoft_oauth_state' => 'oauth-state'])
            ->get(route('login.microsoft.callback', [
                'code' => 'auth-code',
                'state' => 'oauth-state',
            ]))
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertSame('Updated Staff', $user->name);
        $this->assertSame('staff@slt.lk', $user->email);
        $this->assertSame('ms-user-updated', $user->microsoft_id);
        $this->assertTrue($user->slt_employee);
        $this->assertAuthenticatedAs($user);
    }

    public function test_non_slt_user_cannot_login_with_microsoft(): void
    {
        $this->configureMicrosoft();
        $this->fakeMicrosoftGraph('admin@slt.lk', 'Admin User');

        User::factory()->role('admin')->create([
            'email' => 'admin@slt.lk',
            'slt_employee' => false,
        ]);

        $this->withSession(['microsoft_oauth_state' => 'oauth-state'])
            ->get(route('login.microsoft.callback', [
                'code' => 'auth-code',
                'state' => 'oauth-state',
            ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('microsoft');

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'admin@slt.lk',
            'user_role' => 'admin',
        ]);
    }

    public function test_non_slt_domain_cannot_login_with_microsoft(): void
    {
        $this->configureMicrosoft();
        $this->fakeMicrosoftGraph('person@gmail.com', 'Outside User');

        $this->withSession(['microsoft_oauth_state' => 'oauth-state'])
            ->get(route('login.microsoft.callback', [
                'code' => 'auth-code',
                'state' => 'oauth-state',
            ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('microsoft');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'person@gmail.com']);
    }

    public function test_inactive_slt_employee_cannot_login_with_microsoft(): void
    {
        $this->configureMicrosoft();
        $this->fakeMicrosoftGraph('staff@slt.lk', 'SLT Staff');

        User::factory()->role('slt_employee')->inactive()->create([
            'email' => 'staff@slt.lk',
            'slt_employee' => true,
        ]);

        $this->withSession(['microsoft_oauth_state' => 'oauth-state'])
            ->get(route('login.microsoft.callback', [
                'code' => 'auth-code',
                'state' => 'oauth-state',
            ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('microsoft');

        $this->assertGuest();
    }

    private function configureMicrosoft(): void
    {
        config([
            'services.microsoft.client_id' => 'test-client',
            'services.microsoft.client_secret' => 'test-secret',
            'services.microsoft.tenant' => 'organizations',
            'services.microsoft.redirect' => 'http://localhost/login/microsoft/callback',
            'services.microsoft.allowed_domains' => ['slt.lk', 'slt.com.lk'],
        ]);
    }

    private function fakeMicrosoftGraph(string $email, string $name, string $microsoftId = 'ms-user-1'): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response([
                'access_token' => 'graph-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            'https://graph.microsoft.com/v1.0/me' => Http::response([
                'id' => $microsoftId,
                'displayName' => $name,
                'mail' => $email,
                'userPrincipalName' => $email,
            ]),
        ]);
    }
}
