<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_be_created_via_form_submission(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $response = $this->actingAs(User::factory()->role('admin')->create())
            ->post('/user-management/create-user', [
                'slt_employee' => 'no',
                'name' => 'Jane Doe',
                'nic' => '200012345678',
                'email' => 'jane@example.com',
                'phone' => '0771234567',
                'user_roles' => ['admin', 'coordinator'],
                'location' => 'Nebula Institute of Technology - Welisara',
            ]);

        $response->assertRedirect(route('create.user'));
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'user_role' => 'admin',
            'slt_employee' => false,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('role_user', [
            'user_id' => User::query()->where('email', 'jane@example.com')->value('id'),
            'role_id' => \App\Models\Role::query()->where('slug', 'coordinator')->value('id'),
        ]);
    }

    public function test_create_user_page_lists_roles_and_grouped_permissions(): void
    {
        $administrator = User::factory()->role('admin')->create();

        $html = $this->actingAs($administrator)
            ->get(route('create.user'))
            ->assertOk()
            ->assertSee('SLT employee')
            ->assertSee('Find employee')
            ->assertSee('Select roles')
            ->assertSee('Select extra permissions')
            ->assertSee('Coordinator')
            ->assertSee('User Management')
            ->assertSee('Reservation Management')
            ->assertSee('Hostel')
            ->assertSee('Canteen')
            ->assertSee('Payments')
            ->assertSee('Reports')
            ->assertDontSee('Hold Ctrl')
            ->getContent();

        $this->assertMatchesRegularExpression('/id="service_id"[^>]*\bdisabled\b/', $html);
    }

    public function test_slt_employee_details_are_mocked_from_erp_sample_in_development(): void
    {
        $administrator = User::factory()->role('admin')->create();

        $this->actingAs($administrator)
            ->getJson(route('slt.employee.lookup', ['employee_id' => '010375']))
            ->assertOk()
            ->assertJson([
                'name' => 'M G D Karunananda',
                'email' => 'dkaru@slt.com.lk',
                'phone' => '+94714238497',
                'designation' => 'Senior Engineer',
                'service_id' => '010375',
                'mock' => true,
            ]);
    }

    public function test_unknown_employee_id_echoes_development_directory_data(): void
    {
        $administrator = User::factory()->role('admin')->create();

        $this->actingAs($administrator)
            ->getJson(route('slt.employee.lookup', ['employee_id' => '123']))
            ->assertOk()
            ->assertJson([
                'service_id' => '000123',
                'email' => '000123@slt.com.lk',
                'mock' => true,
            ]);
    }

    public function test_live_erp_lookup_uses_the_intranet_api_when_mock_is_disabled(): void
    {
        config([
            'services.slt_erp.mock' => false,
            'services.slt_erp.url' => 'https://oneidentitytest.slt.com.lk/ERPAPIs/api/ERPData/GetAllEmployeeDetailsForServiceNo',
            'services.slt_erp.username' => 'dpuser3',
            'services.slt_erp.password' => 'secret',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'oneidentitytest.slt.com.lk/*' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'message' => 'Operation completed successfully',
                'data' => [[
                    'employeeNumber' => '010375',
                    'employeeName' => 'M G D Karunananda',
                    'designation' => 'Senior Engineer',
                    'email' => 'dkaru@slt.com.lk',
                    'mobileNo' => '+94714238497',
                    'orgName' => 'Provincial Network_WPSW',
                ]],
            ]),
        ]);

        $administrator = User::factory()->role('admin')->create();

        $this->actingAs($administrator)
            ->getJson(route('slt.employee.lookup', ['employee_id' => '010375']))
            ->assertOk()
            ->assertJson([
                'name' => 'M G D Karunananda',
                'mock' => false,
            ]);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return $request->url() === 'https://oneidentitytest.slt.com.lk/ERPAPIs/api/ERPData/GetAllEmployeeDetailsForServiceNo'
                && $request['employeeNo'] === '010375'
                && $request->hasHeader('UserName', 'dpuser3');
        });
    }

    public function test_extra_permissions_can_be_granted_beyond_the_selected_role(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->actingAs(User::factory()->role('admin')->create())
            ->post(route('create.user.store'), [
                'slt_employee' => 'no',
                'name' => 'View Only Plus',
                'nic' => '199912345678',
                'email' => 'viewplus@example.com',
                'phone' => '0770000000',
                'user_roles' => ['nebula_sms_user'],
                'extra_permissions' => ['canteen.view'],
                'location' => 'Nebula Institute of Technology - Welisara',
            ])
            ->assertRedirect(route('create.user'));

        $user = User::query()->where('email', 'viewplus@example.com')->firstOrFail();
        $this->assertTrue($user->hasPermission('reservations.calendar'));
        $this->assertTrue($user->hasPermission('canteen.view'));
        $this->assertFalse($user->hasPermission('canteen.create'));
    }

    public function test_user_can_be_deleted_after_confirmation_submission(): void
    {
        $administrator = User::factory()->role('admin')->create();
        $user = User::factory()->create();

        $this->actingAs($administrator)
            ->delete(route('users.destroy', $user))
            ->assertRedirect(route('user.management'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_cannot_delete_their_own_account(): void
    {
        $administrator = User::factory()->role('admin')->create();

        $this->actingAs($administrator)
            ->delete(route('users.destroy', $administrator))
            ->assertRedirect(route('user.management'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('users', ['id' => $administrator->id]);
    }

    public function test_user_list_shows_existing_user_permissions(): void
    {
        $administrator = User::factory()->role('admin')->create(['name' => 'Access Admin']);

        $this->actingAs($administrator)
            ->get(route('user.management'))
            ->assertOk()
            ->assertSee('Access Admin')
            ->assertSee('Create, Manage')
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertSee('Nebula Institute of Technology - Moratuwa')
            ->assertSee('Nebula Institute of Technology - Peradeniya')
            ->assertSee('Role access is locked')
            ->assertSee('js-delete-user', false)
            ->assertSee('js-view-user', false)
            ->assertSee('Select roles')
            ->assertSee('SLT employee')
            ->assertSee('Find employee')
            ->assertSee('sweetalert2', false)
            ->assertSee('um-table-wrap', false)
            ->assertSee('>Save</button>', false)
            ->assertDontSee('deleteUserModal')
            ->assertDontSee('Confirm user deletion')
            ->assertDontSee('Save and email password');
    }

    public function test_user_management_pagination_uses_bootstrap_links(): void
    {
        $administrator = User::factory()->role('admin')->create();
        User::factory()->count(11)->create();

        $this->actingAs($administrator)
            ->get(route('user.management'))
            ->assertOk()
            ->assertSee('page-link', false)
            ->assertSee('Showing 1 to 10 of 12 users')
            ->assertDontSee('Showing 1 to 15 of 16 results');
    }

    public function test_user_list_can_be_filtered_with_ajax(): void
    {
        $administrator = User::factory()->role('admin')->create(['name' => 'Access Admin']);
        User::factory()->create(['name' => 'Other Person']);

        $this->actingAs($administrator)
            ->get(route('user.management', ['search' => 'Access Admin']), [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertSee('Access Admin')
            ->assertDontSee('Other Person')
            ->assertDontSee('Control access, roles, and profiles');
    }

    public function test_user_list_keeps_long_names_inside_the_table_cell(): void
    {
        $administrator = User::factory()->role('admin')->create();
        User::factory()->create([
            'name' => 'Management User sdfgfgfgdfhghfghghf',
            'email' => 'longname@nebula.local',
        ]);

        $this->actingAs($administrator)
            ->get(route('user.management'))
            ->assertOk()
            ->assertSee('Management User sdfgfgfgdfhghf...')
            ->assertSee('title="Management User sdfgfgfgdfhghfghghf"', false)
            ->assertSee('um-table-wrap', false);
    }

    public function test_user_can_be_soft_deleted_via_ajax_without_a_page_redirect(): void
    {
        $administrator = User::factory()->role('admin')->create();
        $user = User::factory()->create();

        $this->actingAs($administrator)
            ->deleteJson(route('users.destroy', $user))
            ->assertOk()
            ->assertJsonStructure(['status', 'statistics']);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_status_can_be_toggled_via_ajax_without_a_page_redirect(): void
    {
        $administrator = User::factory()->role('admin')->create();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($administrator)
            ->postJson(route('users.toggle-active', $user))
            ->assertOk()
            ->assertJsonPath('is_active', false)
            ->assertJsonStructure(['status', 'statistics']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_soft_deleted_user_remains_visible(): void
    {
        $administrator = User::factory()->role('admin')->create();
        $deletedUser = User::factory()->create([
            'name' => 'Deleted User',
            'deleted_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->get(route('user.management'))
            ->assertOk()
            ->assertSee('Deleted User')
            ->assertSee('Deleted');
    }
}
