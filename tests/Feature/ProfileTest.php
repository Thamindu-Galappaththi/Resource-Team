<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('user.profile'))->assertRedirect(route('login'));
    }

    public function test_user_can_view_their_profile(): void
    {
        $user = User::factory()->role('slt_employee')->create([
            'name' => 'Nimal Perera',
            'nic' => '199012345678',
        ]);

        $this->actingAs($user)
            ->get(route('user.profile'))
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee('Location', false)
            ->assertSee('id="rs-location"', false)
            ->assertSee('Full name', false)
            ->assertSee('text-danger', false)
            ->assertDontSee('Campus', false)
            ->assertSee('Nimal Perera')
            ->assertSee('199012345678')
            ->assertSee('Update the details used on reservations.')
            ->assertDontSee('Security')
            ->assertDontSee('data-rs-tab-btn="security"', false)
            ->assertDontSee('data-rs-panel="security"', false)
            ->assertDontSee('Change password')
            ->assertDontSee('Profile page setup is pending.');
    }

    public function test_slt_employee_security_url_returns_to_profile(): void
    {
        $user = User::factory()->role('slt_employee')->create();

        $this->actingAs($user)
            ->get(route('user.profile', ['tab' => 'security']))
            ->assertRedirect(route('user.profile'));
    }

    public function test_slt_employee_cannot_change_password(): void
    {
        $user = User::factory()->role('slt_employee')->create([
            'password' => 'secret-pass',
        ]);

        $this->actingAs($user)
            ->putJson(route('user.profile.password'), [
                'current_password' => 'secret-pass',
                'password' => 'new-secret1',
                'password_confirmation' => 'new-secret1',
            ])
            ->assertForbidden();

        $this->assertTrue(Hash::check('secret-pass', $user->fresh()->password));
    }

    public function test_microsoft_sign_in_hides_security_for_staff_who_also_have_a_password(): void
    {
        $user = User::factory()->role('admin')->create([
            'slt_employee' => true,
            'password' => 'secret-pass',
        ]);

        $this->actingAs($user)
            ->withSession(['auth_portal' => 'microsoft'])
            ->get(route('user.profile'))
            ->assertOk()
            ->assertDontSee('data-rs-tab-btn="security"', false);

        $this->actingAs($user)
            ->withSession(['auth_portal' => 'microsoft'])
            ->putJson(route('user.profile.password'), [
                'current_password' => 'secret-pass',
                'password' => 'new-secret1',
                'password_confirmation' => 'new-secret1',
            ])
            ->assertForbidden();

        $this->assertTrue(Hash::check('secret-pass', $user->fresh()->password));
    }

    public function test_staff_flagged_as_slt_employee_can_still_open_security_on_the_password_portal(): void
    {
        $user = User::factory()->role('admin')->create([
            'slt_employee' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['auth_portal' => 'password'])
            ->get(route('user.profile'))
            ->assertOk()
            ->assertSee('data-rs-tab-btn="security"', false)
            ->assertSee('Password changes are on the Security tab.');
    }

    public function test_security_tab_shows_password_form(): void
    {
        $user = User::factory()->role('admin')->create();

        $this->actingAs($user)
            ->get(route('user.profile', ['tab' => 'security']))
            ->assertOk()
            ->assertSee('Change password')
            ->assertSee('Current password')
            ->assertSee('Other signed-in devices will be signed out');
    }

    public function test_user_can_update_contact_details_but_not_role(): void
    {
        $user = User::factory()->role('slt_employee')->create([
            'name' => 'Old Name',
            'email' => 'old@nebula.local',
            'phone' => '0770000000',
            'location' => 'Welisara',
        ]);

        $this->actingAs($user)
            ->putJson(route('user.profile.update'), [
                'name' => 'New Name',
                'email' => 'new@nebula.local',
                'phone' => '0771111111',
                'location' => 'Nebula Institute of Technology - Moratuwa',
                'designation' => 'Lecturer',
                'user_role' => 'admin',
                'role_id' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Your profile was updated.')
            ->assertJsonPath('user.name', 'New Name');

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@nebula.local', $user->email);
        $this->assertSame('0771111111', $user->phone);
        $this->assertSame('Lecturer', $user->designation);
        $this->assertSame('slt_employee', $user->user_role);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->role('admin')->create([
            'password' => 'secret-pass',
        ]);

        $this->actingAs($user)
            ->from(route('user.profile'))
            ->putJson(route('user.profile.password'), [
                'current_password' => 'wrong-pass',
                'password' => 'new-secret1',
                'password_confirmation' => 'new-secret1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('secret-pass', $user->fresh()->password));
    }

    public function test_user_can_change_their_password(): void
    {
        $user = User::factory()->role('admin')->create([
            'password' => 'secret-pass',
        ]);

        $this->actingAs($user)
            ->putJson(route('user.profile.password'), [
                'current_password' => 'secret-pass',
                'password' => 'new-secret1',
                'password_confirmation' => 'new-secret1',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Your password was changed.');

        $this->assertTrue(Hash::check('new-secret1', $user->fresh()->password));
    }

    public function test_user_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->role('coordinator')->create();

        $this->actingAs($user)
            ->putJson(route('user.profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '0771234567',
                'location' => 'Nebula Institute of Technology - Welisara',
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
            ])
            ->assertOk()
            ->assertJsonPath('user.has_avatar', true);

        $user->refresh();
        $this->assertNotNull($user->user_profile);
        Storage::disk('public')->assertExists($user->user_profile);
    }
}
