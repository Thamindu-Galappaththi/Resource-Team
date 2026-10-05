<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
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
            ->assertSee('editResourceForm')
            ->assertSee('data-bs-dismiss="modal"', false)
            ->assertSee('Edit Resource');
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

        $this->actingAs($user)->getJson("/resources/{$resource->id}")
            ->assertOk()
            ->assertJsonPath('name_model', 'Projector 01')
            ->assertJsonPath('serial_number', 'PR-001')
            ->assertJsonPath('location.name', $location->name)
            ->assertJsonPath('type.category.name', $category->name);

        $this->actingAs($user)->putJson("/resources/{$resource->id}", [
            'category_id' => $category->id,
            'resource_type_id' => $type->id,
            'location_id' => $location->id,
            'name_model' => 'Projector 02',
            'serial_number' => '',
            'status' => 'under_maintenance',
        ])->assertOk()->assertJsonPath('name_model', 'Projector 02')->assertJsonPath('serial_number', null);

        $this->assertDatabaseHas('resources', ['id' => $resource->id, 'name_model' => 'Projector 02', 'serial_number' => null]);
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

    private function resourceLookups(): array
    {
        $category = ResourceCategory::query()->create(['name' => 'Equipment']);
        $type = ResourceType::query()->create(['resource_category_id' => $category->id, 'name' => 'Projector']);
        $location = Location::query()->create(['name' => 'Block A, Floor 2']);

        return [$category, $type, $location];
    }
}
