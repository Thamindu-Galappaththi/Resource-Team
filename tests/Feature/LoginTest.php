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
            ->assertSee('Login with Google')
            ->assertSee('Login with Facebook')
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

    public function test_non_slt_user_cannot_login_with_microsoft(): void
    {
        $this->configureMicrosoft();
        $this->fakeMicrosoftGraph('admin@nebula.local', 'Admin User');

        User::factory()->role('admin')->create([
            'email' => 'admin@nebula.local',
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
    }

    public function test_unknown_microsoft_account_cannot_login(): void
    {
        $this->configureMicrosoft();
        $this->fakeMicrosoftGraph('unknown@slt.lk', 'Unknown Staff');

        $this->withSession(['microsoft_oauth_state' => 'oauth-state'])
            ->get(route('login.microsoft.callback', [
                'code' => 'auth-code',
                'state' => 'oauth-state',
            ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('microsoft');

        $this->assertGuest();
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

    public function test_unconfigured_google_redirects_back_with_error(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('login.google'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_configured_google_redirects_to_authorization_url(): void
    {
        $this->configureGoogle();

        $response = $this->get(route('login.google'));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth', (string) $response->headers->get('Location'));
        $this->assertNotNull(session('google_oauth_state'));
    }

    public function test_unconfigured_facebook_redirects_back_with_error(): void
    {
        config([
            'services.facebook.client_id' => null,
            'services.facebook.client_secret' => null,
        ]);

        $this->get(route('login.facebook'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('facebook');
    }

    public function test_configured_facebook_redirects_to_authorization_url(): void
    {
        $this->configureFacebook();

        $response = $this->get(route('login.facebook'));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://www.facebook.com/v19.0/dialog/oauth', (string) $response->headers->get('Location'));
        $this->assertNotNull(session('facebook_oauth_state'));
    }

    private function configureMicrosoft(): void
    {
        config([
            'services.microsoft.client_id' => 'test-client',
            'services.microsoft.client_secret' => 'test-secret',
            'services.microsoft.tenant' => 'organizations',
            'services.microsoft.redirect' => 'http://localhost/login/microsoft/callback',
        ]);
    }

    private function configureGoogle(): void
    {
        config([
            'services.google.client_id' => 'test-google-client',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => 'http://localhost/login/google/callback',
        ]);
    }

    private function configureFacebook(): void
    {
        config([
            'services.facebook.client_id' => 'test-fb-client',
            'services.facebook.client_secret' => 'test-fb-secret',
            'services.facebook.redirect' => 'http://localhost/login/facebook/callback',
        ]);
    }

    private function fakeMicrosoftGraph(string $email, string $name): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response([
                'access_token' => 'graph-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            'https://graph.microsoft.com/v1.0/me' => Http::response([
                'id' => 'ms-user-1',
                'displayName' => $name,
                'mail' => $email,
                'userPrincipalName' => $email,
            ]),
        ]);
    }
}
