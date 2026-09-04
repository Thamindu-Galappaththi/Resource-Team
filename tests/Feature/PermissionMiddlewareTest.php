<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_nebula_sms_user_cannot_open_create_user(): void
    {
        $user = User::factory()->role('nebula_sms_user')->create();

        $this->actingAs($user)
            ->get(route('create.user'))
            ->assertForbidden();
    }

    public function test_admin_can_open_create_user_and_user_management(): void
    {
        $user = User::factory()->role('admin')->create();

        $this->actingAs($user)->get(route('create.user'))->assertOk();
        $this->actingAs($user)->get(route('user.management'))->assertOk();
    }

    public function test_canteen_user_cannot_open_resource_pages(): void
    {
        $user = User::factory()->role('canteen')->create();

        $this->actingAs($user)->get(route('resources.create'))->assertForbidden();
        $this->actingAs($user)->get(route('resources.index'))->assertForbidden();
    }

    public function test_coordinator_can_open_reservation_pages(): void
    {
        $user = User::factory()->role('coordinator')->create();

        $this->actingAs($user)->get(route('reservations.create'))->assertOk();
        $this->actingAs($user)->get(route('reservations.index'))->assertOk();
    }
}
