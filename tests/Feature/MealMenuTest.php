<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\MealMenu;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MealMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_canteen_staff_can_publish_a_meal_menu(): void
    {
        $staff = User::factory()->role('canteen')->create();

        $this->actingAs($staff)->postJson(route('canteen.menus.store'), [
            'name' => 'Rice and curry',
            'image' => UploadedFile::fake()->image('lunch.jpg', 1200, 900),
        ])->assertOk()
            ->assertJsonPath('message', 'Meal menu published.')
            ->assertSee('data-cp-confirm', false);

        $this->assertDatabaseHas('meal_menus', ['name' => 'Rice and curry']);
        $this->actingAs($staff)->get(route('canteen.menus.index'))
            ->assertOk()
            ->assertSee('Meal Plans')
            ->assertSee('data-cp-confirm', false)
            ->assertDontSee('confirm(', false)
            ->assertDontSee('Service date');
    }

    public function test_slt_employee_cannot_open_meal_plans(): void
    {
        $employee = User::factory()->role('slt_employee')->create();

        $this->actingAs($employee)->get(route('canteen.menus.index'))->assertForbidden();
    }

    public function test_create_reservation_requires_a_meal_menu_and_shows_its_image(): void
    {
        $menu = MealMenu::query()->create([
            'name' => 'Friday lunch',
            'image_path' => 'meal-menus/friday.jpg',
        ]);
        $location = Location::factory()->create();
        $user = User::factory()->role('slt_employee')->create();

        $this->actingAs($user)->get(route('canteen.create'))
            ->assertOk()
            ->assertSee('Meal menu')
            ->assertSee('Location')
            ->assertDontSee('Meal type')
            ->assertSee('Friday lunch');

        $this->actingAs($user)->post(route('canteen.store'), [
            'reservation_name' => 'Friday group lunch',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:30',
            'number_of_orders' => 8,
        ])->assertSessionHasErrors('meal_menu_id');

        $this->actingAs($user)->post(route('canteen.store'), [
            'reservation_name' => 'Friday group lunch',
            'meal_menu_id' => $menu->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:30',
            'number_of_orders' => 8,
        ])->assertSessionHasErrors('location_id');

        $this->actingAs($user)->post(route('canteen.store'), [
            'reservation_name' => 'Friday group lunch',
            'meal_menu_id' => $menu->id,
            'location_id' => $location->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '12:30',
            'number_of_orders' => 8,
        ])->assertRedirect();

        $this->assertDatabaseHas('canteen_reservations', [
            'reservation_name' => 'Friday group lunch',
            'meal_menu_id' => $menu->id,
        ]);
    }
}
