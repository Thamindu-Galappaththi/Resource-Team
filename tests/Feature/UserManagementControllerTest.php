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
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'user_role' => 'admin',
            ]);

        $response->assertRedirect(route('create.user'));
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'user_role' => 'admin',
        ]);
    }
}
