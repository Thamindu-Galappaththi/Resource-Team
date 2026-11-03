<?php

namespace Tests\Feature;

use App\Enums\ReservationType;
use App\Models\HostelStayDetail;
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

    public function test_overlapping_hostel_stays_share_beds_without_calendar_conflicts(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        $hostel = Resource::factory()->create(['name_model' => 'Welisara Hostel', 'status' => 'active']);
        $start = now('Asia/Colombo')->addDays(5)->setTime(11, 0, 0);

        foreach (['female' => 2, 'male' => 1] as $group => $beds) {
            $reservation = Reservation::factory()->create([
                'requester_id' => $user->id,
                'created_by_user_id' => $user->id,
                'type' => ReservationType::HOSTEL->value,
                'status' => 'confirmed',
                'title' => 'Hostel stay '.$group,
            ]);
            HostelStayDetail::factory()->create(['reservation_id' => $reservation->id, 'bed_group' => $group, 'number_of_guests' => $beds]);
            ReservationItem::factory()->create([
                'reservation_id' => $reservation->id,
                'resource_id' => $hostel->id,
                'status' => 'confirmed',
                'quantity' => $beds,
                'starts_at' => $start->copy()->utc(),
                'ends_at' => $start->copy()->addDays(2)->utc(),
                'resource_name_snapshot' => 'Welisara Hostel',
            ]);
        }

        $range = [
            'start' => $start->copy()->subDay()->toIso8601String(),
            'end' => $start->copy()->addDays(3)->toIso8601String(),
        ];

        foreach (['resources.calendar.events', 'reservations.calendar.events'] as $route) {
            $events = collect($this->actingAs($user)->getJson(route($route, $range))->assertOk()->json());

            $this->assertNotEmpty($events);
            $this->assertFalse($events->contains(fn ($event) => $event['extendedProps']['conflict'] ?? false), $route);
            $this->assertContains('Welisara Hostel · Ladies · 2 beds', $events->pluck('extendedProps.resource'));
            $this->assertContains('Welisara Hostel · Male · 1 bed', $events->pluck('extendedProps.resource'));
        }
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
        $location = Location::query()->firstOrCreate(['name' => 'Welisara']);

        return [$category, $type, $location];
    }

    /**
     * @return array{0: ResourceCategory, 1: ResourceType, 2: Location, 3: Location}
     */
    private function hostelLookups(): array
    {
        $category = ResourceCategory::query()->firstOrCreate(['name' => config('hostel.category_name')]);
        $bed = ResourceType::query()->firstOrCreate(['resource_category_id' => $category->id, 'name' => 'Bed']);

        return [
            $category,
            $bed,
            Location::query()->firstOrCreate(['name' => 'Welisara']),
            Location::query()->firstOrCreate(['name' => 'Moratuwa']),
        ];
    }

    public function test_resources_can_only_be_added_at_welisara_or_moratuwa(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        [$category, $type] = $this->resourceLookups();
        $peradeniya = Location::query()->create(['name' => 'Peradeniya']);

        $this->actingAs($user)->getJson(route('locations.index'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Welisara'])
            ->assertJsonMissing(['name' => 'Peradeniya']);

        $this->postJson('/resources', [
            'category_id' => $category->id,
            'resource_type_id' => $type->id,
            'location_id' => $peradeniya->id,
            'name_model' => 'Projector 09',
            'status' => 'active',
        ])->assertUnprocessable()->assertJsonValidationErrors(['location_id' => 'Resources can only be added at Welisara or Moratuwa.']);
    }

    public function test_hostel_room_category_is_locked_and_only_has_the_bed_type(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        [$category] = $this->hostelLookups();

        $this->actingAs($user)->getJson(route('resource-categories.index'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Hostel Room', 'is_locked' => true]);

        $this->putJson(route('resource-categories.update', $category), ['category_name' => 'Rooms'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Hostel Room is a fixed category used for hostel reservations and cannot be changed or deleted.');
        $this->deleteJson(route('resource-categories.destroy', $category))->assertUnprocessable();
        $this->assertSame('Hostel Room', $category->fresh()->name);

        $this->postJson(route('resource-types.store'), ['category_id' => $category->id, 'resource_type' => 'Double'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resource_type' => 'Hostel Room has one fixed type (Bed). No other types can be added.']);
        $this->assertSame(['Bed'], $category->types()->pluck('name')->all());
    }

    public function test_one_hostel_record_per_location_stores_every_bed_group(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        [$category, $bed, $welisara, $moratuwa] = $this->hostelLookups();
        $base = [
            'category_id' => $category->id,
            'resource_type_id' => $bed->id,
            'location_id' => $welisara->id,
            'status' => 'active',
        ];

        $this->actingAs($user)->postJson('/resources', $base)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bed_counts.permanent', 'bed_counts.female', 'bed_counts.male'])
            ->assertJsonMissingValidationErrors('name_model');

        $this->postJson('/resources', $base + ['bed_counts' => ['permanent' => 1001, 'female' => 40, 'male' => 40]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bed_counts.permanent');

        $this->postJson('/resources', $base + ['bed_counts' => ['permanent' => 10, 'female' => 40, 'male' => 40]])
            ->assertCreated()
            ->assertJsonPath('name_model', 'Welisara Hostel')
            ->assertJsonPath('bed_count', 90)
            ->assertJsonPath('bed_counts.female', 40);

        $this->postJson('/resources', $base + ['bed_counts' => ['permanent' => 1, 'female' => 1, 'male' => 1]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['location_id' => 'Welisara already has a hostel record. Edit that record to change its beds.']);

        $hostel = Resource::query()->where('location_id', $welisara->id)->firstOrFail();
        $other = Resource::factory()->create(['location_id' => $welisara->id, 'status' => 'active']);
        $this->putJson("/resources/{$hostel->id}", $base + ['bed_counts' => ['permanent' => 10, 'female' => 38, 'male' => 40], 'linked_resource_ids' => [$other->id]])
            ->assertOk()->assertJsonPath('bed_count', 88)->assertJsonPath('linked_resources', []);

        $this->postJson('/resources', ['location_id' => $moratuwa->id, 'name_model' => 'Moratuwa Boarding', 'bed_counts' => ['permanent' => 10, 'female' => 40, 'male' => 40]] + $base)
            ->assertCreated()->assertJsonPath('name_model', 'Moratuwa Boarding');

        $this->assertSame(2, Resource::query()->hostelRooms()->count());
    }

    public function test_hostel_records_are_never_linked_to_other_resources(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        [$category, $bed, $welisara] = $this->hostelLookups();
        $hall = Resource::factory()->create(['name_model' => 'Hall A', 'status' => 'active']);
        $projector = Resource::factory()->create(['name_model' => 'Projector', 'status' => 'active']);
        $hostel = Resource::factory()->create([
            'resource_type_id' => $bed->id,
            'location_id' => $welisara->id,
            'name_model' => 'Welisara Hostel',
            'status' => 'active',
            'bed_counts' => ['permanent' => 10, 'female' => 40, 'male' => 40],
            'bed_count' => 90,
        ]);
        $hostel->linkedResources()->attach($hall->id);

        $this->actingAs($user)->postJson(route('resource-links.store'), ['resource_id' => $hall->id, 'linked_resource_id' => $hostel->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['linked_resource_id' => 'Hostel records hold beds only and cannot be linked to other resources.']);

        $this->putJson("/resources/{$hostel->id}", [
            'category_id' => $category->id,
            'resource_type_id' => $bed->id,
            'location_id' => $welisara->id,
            'status' => 'active',
            'bed_counts' => ['permanent' => 10, 'female' => 40, 'male' => 40],
            'linked_resource_ids' => [$hall->id],
        ])->assertOk()->assertJsonCount(0, 'linked_resources');

        $this->putJson("/resources/{$hall->id}", [
            'category_id' => $hall->type->resource_category_id,
            'resource_type_id' => $hall->resource_type_id,
            'location_id' => $welisara->id,
            'name_model' => 'Hall A',
            'status' => 'active',
            'linked_resource_ids' => [$hostel->id, $projector->id],
        ])->assertOk()->assertJsonPath('linked_resources.0.id', $projector->id)->assertJsonCount(1, 'linked_resources');
    }

    public function test_hostel_beds_can_grow_but_not_drop_below_upcoming_bookings(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->role('admin')->create();
        [$category, $bed, $welisara] = $this->hostelLookups();
        $base = [
            'category_id' => $category->id,
            'resource_type_id' => $bed->id,
            'location_id' => $welisara->id,
            'status' => 'active',
        ];
        $hostel = Resource::factory()->create([
            'resource_type_id' => $bed->id,
            'location_id' => $welisara->id,
            'name_model' => 'Welisara Hostel',
            'status' => 'active',
            'bed_counts' => ['permanent' => 10, 'female' => 40, 'male' => 40],
            'bed_count' => 90,
        ]);

        $this->actingAs($user)->putJson("/resources/{$hostel->id}", $base + ['bed_counts' => ['permanent' => 12, 'female' => 60, 'male' => 45]])
            ->assertOk()
            ->assertJsonPath('bed_count', 117)
            ->assertJsonPath('bed_counts.female', 60);

        $start = now()->addDays(3);
        foreach ([3, 2] as $index => $beds) {
            $reservation = Reservation::factory()->create([
                'type' => ReservationType::HOSTEL->value,
                'status' => 'confirmed',
                'title' => 'Ladies stay '.$index,
            ]);
            HostelStayDetail::factory()->create(['reservation_id' => $reservation->id, 'bed_group' => 'female', 'number_of_guests' => $beds]);
            ReservationItem::factory()->create([
                'reservation_id' => $reservation->id,
                'resource_id' => $hostel->id,
                'status' => 'confirmed',
                'quantity' => $beds,
                'starts_at' => $start->copy()->addDays($index),
                'ends_at' => $start->copy()->addDays($index + 2),
            ]);
        }

        $this->putJson("/resources/{$hostel->id}", $base + ['bed_counts' => ['permanent' => 0, 'female' => 4, 'male' => 1]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bed_counts.female' => '5 Ladies beds are already booked on upcoming dates. Keep at least that many.'])
            ->assertJsonMissingValidationErrors(['bed_counts.permanent', 'bed_counts.male']);

        $this->putJson("/resources/{$hostel->id}", $base + ['bed_counts' => ['permanent' => 0, 'female' => 5, 'male' => 1]])
            ->assertOk()->assertJsonPath('bed_count', 6);
    }

    public function test_hostel_room_seeder_keeps_one_ninety_bed_record_per_location(): void
    {
        $this->seed([LocationSeeder::class, HostelRoomSeeder::class, HostelRoomSeeder::class]);

        $hostels = Resource::query()->hostelRooms()->with('location')->get();
        $this->assertSame(['Moratuwa Hostel', 'Welisara Hostel'], $hostels->pluck('name_model')->sort()->values()->all());
        foreach ($hostels as $hostel) {
            $this->assertSame(['permanent' => 10, 'female' => 40, 'male' => 40], $hostel->bed_counts);
            $this->assertSame(90, $hostel->bed_count);
        }

        $this->assertSame(['Bed'], ResourceType::query()->whereHas('category', fn ($query) => $query->where('name', 'Hostel Room'))->pluck('name')->all());
    }

    public function test_hostel_manager_dashboard_shows_bus_style_bed_map(): void
    {
        $this->seed([RolePermissionSeeder::class, LocationSeeder::class, HostelRoomSeeder::class]);
        $manager = User::factory()->role('hostel_manager')->create();

        $html = $this->actingAs($manager)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bed availability')
            ->assertSee('id="hbm-date"', false)
            ->assertSee('<strong>90</strong> of 90 beds free', false)
            ->getContent();
        $this->assertSame(180, substr_count($html, 'class="hbm-seat is-free"'));

        $this->get(route('hostel.bed-map', ['date' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertSee('id="hbm-body"', false)
            ->assertSee('Bed L40: Available');
        $this->get(route('hostel.bed-map', ['date' => 'bad']))->assertSessionHasErrors('date');
    }
}
