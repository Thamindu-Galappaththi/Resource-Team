<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response = $this->actingAs(User::factory()->role('admin')->create())
            ->post('/user-management/create-user', [
                'slt_employee' => 'no',
                'name' => 'Jane Doe',
                'nic' => '200012345678',
                'email' => 'jane@example.com',
                'phone' => '0771234567',
                'user_role' => 'admin',
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
    }

    public function test_slt_employee_details_can_be_looked_up_by_employee_id(): void
    {
        $administrator = User::factory()->role('admin')->create();
        User::factory()->create([
            'service_id' => 'SLT-1001',
            'name' => 'Sam Perera',
            'nic' => '199012345678',
            'email' => 'sam.perera@slt.lk',
            'phone' => '0712345678',
        ]);

        $this->actingAs($administrator)
            ->getJson(route('slt.employee.lookup', ['employee_id' => 'SLT-1001']))
            ->assertOk()
            ->assertExactJson([
                'name' => 'Sam Perera',
                'nic' => '199012345678',
                'email' => 'sam.perera@slt.lk',
                'phone' => '0712345678',
            ]);
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

    public function test_soft_deleted_user_remains_visible_with_deleted_timestamp(): void
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
            ->assertSee('Deleted')
            ->assertSee($deletedUser->deleted_at->format('M d, Y'));
    }
}
