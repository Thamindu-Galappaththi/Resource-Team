<?php

namespace Tests\Feature;

use App\Enums\ReservationType;
use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use App\Models\User;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Database\Seeders\HostelRoomSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\ReservationSeeder;
use Database\Seeders\ResourceSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_resource_page_has_example_placeholders_and_optional_serial_field(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();

        $this->actingAs($user)->get('/resources/create')
            ->assertOk()
            ->assertSee('e.g., Computer Lab 03')
            ->assertSee('e.g., SN-2024-00123')
            ->assertSee('(Optional)');
    }

    public function test_existing_resources_page_renders_the_in_page_edit_modal_for_managers(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();

        $this->actingAs($user)->get('/resources')
            ->assertOk()
            ->assertSee('editResourceModal')
            ->assertSee('data-cu-select', false)
            ->assertSee('rm-search-group', false)
            ->assertSee('id="rmPagination"', false)
            ->assertSee('editResourceForm')
            ->assertSee('data-bs-dismiss="modal"', false)
            ->assertSee('Edit Resource')
            ->assertSee('lookups', false);
    }

    public function test_resource_serial_number_is_optional_trimmed_and_shown_in_details(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        [$category, $type, $location] = $this->resourceLookups();

        $response = $this->actingAs($user)->postJson('/resources', [
            'category_id' => $category->id,
            'resource_type_id' => $type->id,
            'location_id' => $location->id,
            'name_model' => 'Computer Lab 03',
            'serial_number' => '   ',
            'status' => 'active',
        ]);

        $response->assertCreated()->assertJsonPath('serial_number', null);
        $resource = Resource::query()->firstOrFail();
        $this->assertNull($resource->serial_number);
        $this->actingAs($user)->getJson("/resources/{$resource->id}")
            ->assertOk()->assertJsonPath('serial_number', null);

        $this->actingAs($user)->postJson('/resources', [
            'category_id' => $category->id,
            'resource_type_id' => $type->id,
            'location_id' => $location->id,
            'name_model' => 'Computer Lab 04',
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('serial_number', null);
    }

    public function test_resource_serial_number_is_trimmed_and_rejects_invalid_values(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        [$category, $type, $location] = $this->resourceLookups();
        $payload = [
            'category_id' => $category->id,
            'resource_type_id' => $type->id,
            'location_id' => $location->id,
            'name_model' => 'Projector 03',
            'serial_number' => '  SN-2024-00123  ',
            'status' => 'active',
        ];

        $this->actingAs($user)->postJson('/resources', $payload)
            ->assertCreated()->assertJsonPath('serial_number', 'SN-2024-00123');
        $this->actingAs($user)->postJson('/resources', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('serial_number');
        $payload['serial_number'] = 'SN_2024!';
        $this->actingAs($user)->postJson('/resources', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('serial_number');
    }

    public function test_resource_can_be_viewed_and_updated_using_the_existing_form_endpoint(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        [$category, $type, $location] = $this->resourceLookups();
        $resource = Resource::query()->create([
            'resource_type_id' => $type->id,
            'location_id' => $location->id,
            'name_model' => 'Projector 01',
            'serial_number' => 'PR-001',
            'status' => 'active',
        ]);

        $linked = Resource::query()->create([
            'resource_type_id' => $type->id,
            'location_id' => $location->id,
            'name_model' => 'HDMI Cable 01',
            'status' => 'active',
        ]);
        $resource->linkedResources()->syncWithoutDetaching([$linked->id]);

        $this->actingAs($user)->getJson("/resources/{$resource->id}")
            ->assertOk()
            ->assertJsonPath('name_model', 'Projector 01')
            ->assertJsonPath('serial_number', 'PR-001')
            ->assertJsonPath('location.name', $location->name)
            ->assertJsonPath('type.category.name', $category->name)
            ->assertJsonPath('linked_resources.0.name_model', 'HDMI Cable 01');

        $this->actingAs($user)->putJson("/resources/{$resource->id}", [
            'category_id' => $category->id,
            'resource_type_id' => $type->id,
            'location_id' => $location->id,
            'resource_owner' => 'Nadun',
            'name_model' => 'Projector 02',
            'serial_number' => '',
            'status' => 'under_maintenance',
            'linked_resource_ids' => [$linked->id],
        ])->assertOk()
            ->assertJsonPath('name_model', 'Projector 02')
            ->assertJsonPath('serial_number', null)
            ->assertJsonPath('resource_owner', 'Nadun')
            ->assertJsonPath('linked_resources.0.id', $linked->id);

        $this->assertDatabaseHas('resources', ['id' => $resource->id, 'name_model' => 'Projector 02', 'serial_number' => null]);
    }

    public function test_view_only_users_see_the_inventory_without_edit_controls(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('management')->create();

        $this->actingAs($user)->get('/resources')
            ->assertOk()
            ->assertSee('Existing resources')
            ->assertDontSee('editResourceModal')
            ->assertDontSee('Create resource');
    }

    public function test_view_only_users_cannot_delete_a_resource(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('management')->create();
        [, $type, $location] = $this->resourceLookups();
        $resource = Resource::query()->create([
            'resource_type_id' => $type->id,
            'location_id' => $location->id,
            'name_model' => 'Protected Projector',
            'status' => 'active',
        ]);

        $this->actingAs($user)->deleteJson("/resources/{$resource->id}")->assertForbidden();
        $this->assertDatabaseHas('resources', ['id' => $resource->id, 'is_deleted' => false]);
    }

    public function test_create_resource_page_uses_stepped_workflow_with_owner_input(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();

        $this->actingAs($user)->get('/resources/create')
            ->assertOk()
            ->assertSee('Create a resource category')
            ->assertSee('Link resources')
            ->assertSee('data-cu-select', false)
            ->assertSee('Resource Owner')
            ->assertSee('e.g., Nadun')
            ->assertSee('Update the category name and features.')
            ->assertSee('editAddFeatureBtn', false)
            ->assertSee('id="existingCategoriesPagination"', false)
            ->assertSee('id="existingCategoriesCount"', false);
    }

    public function test_resource_calendar_page_renders_filters_and_calendar_root(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();

        $this->actingAs($user)->get(route('resources.calendar'))
            ->assertOk()
            ->assertSee('Resource calendar')
            ->assertSee('id="resourceCalendar"', false)
            ->assertSee('id="refreshCalendarButton"', false)
            ->assertSee('id="resource-calendar-filters"', false)
            ->assertSeeInOrder([
                'id="locationFilter"',
                'id="categoryFilter"',
                'id="resourceFilter"',
                'id="statusFilter"',
            ], false)
            ->assertSee('id="upcomingPagination"', false)
            ->assertSee('id="upcomingResultCount"', false)
            ->assertSee('Active resources')
            ->assertDontSee('My Resources')
            ->assertSee('Independence Day')
            ->assertSee('Vap Full Moon Poya Day')
            ->assertSee('Christmas Day')
            ->assertSee('Poya')
            ->assertSee('Mercantile')
            ->assertSee('2027-02-04')
            ->assertSee('2028-02-04')
            ->assertSee('2028-01-12')
            ->assertSee('poya.svg', false);
    }

    public function test_resource_calendar_embeds_iso8601_times_from_reservation_items(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        $resource = Resource::factory()->create(['name_model' => 'Hall A', 'status' => 'active']);
        $reservation = Reservation::factory()->create([
            'requester_id' => $user->id,
            'created_by_user_id' => $user->id,
            'title' => 'Lab session',
        ]);
        $start = now('UTC')->addDay()->setTime(3, 30, 0);
        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id,
            'resource_id' => $resource->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(3),
            'resource_name_snapshot' => 'Hall A',
        ]);

        $this->actingAs($user)
            ->getJson(route('resources.calendar.events', [
                'start' => $start->copy()->subDay()->toIso8601String(),
                'end' => $start->copy()->addDays(2)->toIso8601String(),
            ]))
            ->assertOk()
            ->assertSee('Hall A', false)
            ->assertSee($start->copy()->timezone('Asia/Colombo')->format('Y-m-d\TH:i:s'), false)
            ->assertDontSee('"start":"T', false);
    }

    public function test_resource_calendar_uses_nights_and_stay_bounds_for_hostel_bookings(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        $resource = Resource::factory()->create(['name_model' => 'Moratuwa Single 101', 'status' => 'active']);
        $start = now('Asia/Colombo')->addDays(5)->setTime(11, 0, 0);
        $end = $start->copy()->addHours(61);
        $reservation = Reservation::factory()->create([
            'requester_id' => $user->id,
            'created_by_user_id' => $user->id,
            'type' => ReservationType::HOSTEL->value,
            'title' => 'Hostel stay',
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id,
            'resource_id' => $resource->id,
            'starts_at' => $start->copy()->utc(),
            'ends_at' => $end->copy()->utc(),
            'resource_name_snapshot' => 'Moratuwa Single 101',
        ]);

        $this->actingAs($user)
            ->getJson(route('resources.calendar.events', [
                'start' => $start->copy()->subDay()->toIso8601String(),
                'end' => $end->copy()->addDay()->toIso8601String(),
            ]))
            ->assertOk()
            ->assertSee('Moratuwa Single 101', false)
            ->assertSee('2 nights', false)
            ->assertSee('"reservationType":"hostel"', false)
            ->assertDontSee('61 hr', false);
    }

    public function test_resource_calendar_shows_other_users_bookings_to_developer(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $developer = User::factory()->role('developer')->create();
        $owner = User::factory()->role('admin')->create();
        $resource = Resource::factory()->create(['name_model' => 'Hall A', 'status' => 'active']);
        $reservation = Reservation::factory()->create([
            'requester_id' => $owner->id,
            'created_by_user_id' => $owner->id,
            'title' => 'Shared lab booking',
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id,
            'resource_id' => $resource->id,
            'resource_name_snapshot' => 'Hall A',
        ]);

        $this->actingAs($developer)
            ->getJson(route('resources.calendar.events', [
                'start' => now()->subDay()->toIso8601String(),
                'end' => now()->addMonths(2)->toIso8601String(),
            ]))
            ->assertOk()
            ->assertSee('Hall A', false)
            ->assertSee('Shared lab booking', false);
    }

    public function test_resource_calendar_events_honor_resource_filter(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        $keep = Resource::factory()->create(['name_model' => 'Keep Hall', 'status' => 'active']);
        $hide = Resource::factory()->create(['name_model' => 'Hide Hall', 'status' => 'active']);
        $start = now('UTC')->addDay()->setTime(3, 30, 0);
        foreach ([$keep, $hide] as $resource) {
            $reservation = Reservation::factory()->create([
                'requester_id' => $user->id,
                'created_by_user_id' => $user->id,
                'location_id' => $resource->location_id,
                'title' => $resource->name_model.' booking',
            ]);
            ReservationItem::factory()->create([
                'reservation_id' => $reservation->id,
                'resource_id' => $resource->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addHours(2),
                'resource_name_snapshot' => $resource->name_model,
            ]);
        }

        $range = [
            'start' => $start->copy()->subDay()->toIso8601String(),
            'end' => $start->copy()->addDays(2)->toIso8601String(),
        ];

        $this->actingAs($user)
            ->getJson(route('resources.calendar.events', $range + ['resource_id' => $keep->id]))
            ->assertOk()
            ->assertSee('Keep Hall', false)
            ->assertDontSee('Hide Hall', false);
    }

    public function test_empty_category_features_are_not_stored(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();

        $this->actingAs($user)->postJson('/resource-categories', [
            'category_name' => 'IT Equipment',
            'features' => [
                ['name' => '  ', 'enabled' => true, 'options' => 'Dell'],
                ['name' => 'Brand', 'enabled' => true, 'options' => 'Dell, Lenovo'],
            ],
        ])->assertCreated();

        $category = ResourceCategory::query()->where('name', 'IT Equipment')->firstOrFail();
        $this->assertSame(['Brand'], $category->features()->pluck('name')->all());
    }

    public function test_category_update_can_add_and_edit_features(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        $category = ResourceCategory::query()->create(['name' => 'Meeting Rooms']);

        $this->actingAs($user)->putJson("/resource-categories/{$category->id}", [
            'category_name' => 'Meeting Rooms',
            'features' => [
                ['name' => 'Capacity', 'enabled' => true, 'options' => '8, 12, 20'],
            ],
        ])->assertOk()->assertJsonPath('features.0.name', 'Capacity');

        $this->assertSame(['Capacity'], $category->features()->pluck('name')->all());

        $featureId = $category->features()->value('id');
        $this->actingAs($user)->putJson("/resource-categories/{$category->id}", [
            'category_name' => 'Conference Rooms',
            'features' => [
                ['id' => $featureId, 'name' => 'Seating', 'enabled' => true, 'options' => '8, 12'],
                ['name' => 'Projector', 'enabled' => false, 'options' => null],
            ],
        ])->assertOk();

        $this->assertSame(['Seating', 'Projector'], $category->fresh()->features()->pluck('name')->all());
        $this->assertDatabaseHas('resource_categories', ['id' => $category->id, 'name' => 'Conference Rooms']);
    }

    public function test_resource_delete_sets_soft_delete_fields_and_hides_it_from_list_and_details(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        [, $type, $location] = $this->resourceLookups();
        $resource = Resource::query()->create([
            'resource_type_id' => $type->id,
            'location_id' => $location->id,
            'name_model' => 'Projector To Delete',
            'serial_number' => null,
            'status' => 'active',
        ]);

        $this->actingAs($user)->getJson('/resource-list')->assertOk()->assertJsonFragment(['id' => $resource->id]);
        $this->actingAs($user)->deleteJson("/resources/{$resource->id}")->assertOk();

        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'is_deleted' => true,
        ]);
        $this->assertNotNull(Resource::withoutGlobalScope('not_deleted')->findOrFail($resource->id)->deleted_at);
        $this->actingAs($user)->getJson('/resource-list')->assertOk()->assertJsonMissing(['id' => $resource->id]);
        $this->actingAs($user)->getJson("/resources/{$resource->id}")->assertNotFound();
    }

    public function test_resource_seeder_creates_enough_rows_for_pagination(): void
    {
        $this->seed([LocationSeeder::class, HostelRoomSeeder::class, ResourceSeeder::class]);

        $this->assertGreaterThanOrEqual(11, Resource::query()->count());
    }

    public function test_reservation_seeder_creates_calendar_bookings(): void
    {
        $this->seed([
            LocationSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            HostelRoomSeeder::class,
            ResourceSeeder::class,
            ReservationSeeder::class,
        ]);

        $this->assertGreaterThan(0, Reservation::query()->count());
    }

    private function resourceLookups(): array
    {
        $category = ResourceCategory::query()->create(['name' => 'Equipment']);
        $type = ResourceType::query()->create(['resource_category_id' => $category->id, 'name' => 'Projector']);
        $location = Location::query()->create(['name' => 'Block A, Floor 2']);

        return [$category, $type, $location];
    }
}
