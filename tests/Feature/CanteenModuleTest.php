<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\CanteenReservation;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanteenModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_open_create_canteen_page()
    {
        $role = Role::factory()->create(['slug' => 'developer']);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $this->actingAs($user)->get(route('canteens.create'))->assertStatus(200);
    }

    public function test_create_canteen_validation_and_store()
    {
        $role = Role::factory()->create(['slug' => 'developer']);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $location = Location::factory()->create();

        $response = $this->actingAs($user)->post(route('canteens.store'), [
            'name' => 'Main Canteen',
            'location_id' => $location->id,
            'opening_time' => '08:00',
            'closing_time' => '17:00',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('canteens', ['name' => 'Main Canteen']);
    }

    public function test_create_canteen_closing_before_opening_fails()
    {
        $role = Role::factory()->create(['slug' => 'developer']);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $location = Location::factory()->create();

        $response = $this->actingAs($user)->post(route('canteens.store'), [
            'name' => 'Bad Canteen',
            'location_id' => $location->id,
            'opening_time' => '17:00',
            'closing_time' => '08:00',
        ]);

        $response->assertSessionHasErrors('closing_time');
    }

    public function test_create_canteen_reservation_and_code_generation()
    {
        $role = Role::factory()->create(['slug' => 'developer']);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $location = Location::factory()->create();
        $canteen = Canteen::create([
            'name' => 'Test Canteen',
            'location_id' => $location->id,
            'opening_time' => '08:00',
            'closing_time' => '18:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('canteen.reservations.store'), [
            'reservation_name' => 'Team Lunch',
            'canteen_id' => $canteen->id,
            'reservation_date' => now()->addDay()->format('Y-m-d'),
            'reservation_time' => '12:00',
            'meal_type' => 'Lunch',
            'number_of_orders' => 10,
            'order_details' => '10 meals',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('canteen_reservations', 1);
        $reservation = CanteenReservation::first();
        $this->assertStringStartsWith('CR-', $reservation->reservation_code);
    }
}
