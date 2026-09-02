<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_via_form_submission(): void
    {
        $response = $this->actingAs(User::factory()->create())
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
        ]);
    }

    public function test_slt_employee_details_can_be_looked_up_by_employee_id(): void
    {
        $administrator = User::factory()->create();
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
}
