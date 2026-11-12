<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\RateRule;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LectureFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_lecture_fees_page_assigns_prices_with_active_status_and_create_edit_ui(): void
    {
        $user = User::factory()->role('admin')->create();
        $this->humanResource('Ishara Perera');

        $this->actingAs($user)->get(route('payments.lecture-fees'))
            ->assertOk()
            ->assertSee('Assign lecture rate', false)
            ->assertSee('Hourly rate (LKR)', false)
            ->assertSee('id="lf-status"', false)
            ->assertSee('Active', false)
            ->assertSee('data-lf-search', false)
            ->assertDontSee('fas fa-search', false)
            ->assertSee('id="lf-edit-modal"', false)
            ->assertSee('HR lecturers', false);
    }

    public function test_lecture_rate_can_be_created_updated_and_deactivated(): void
    {
        $user = User::factory()->role('admin')->create();

        $this->actingAs($user)->postJson(route('payments.lecture-fees.store'), [
            'user_type' => 'external_visiting',
            'department' => 'diploma',
            'qualification' => 'masters',
            'hourly_rate' => 2500,
            'status' => 'active',
        ])->assertOk()->assertJsonPath('message', 'Lecture rate added.');

        $rule = RateRule::query()->first();
        $this->assertNotNull($rule);
        $this->assertSame(250000, (int) $rule->hourly_rate_minor);
        $this->assertTrue($rule->is_active);
        $this->assertSame('external_visiting', $rule->user_type);

        $this->actingAs($user)->putJson(route('payments.lecture-fees.update', $rule), [
            'user_type' => 'external_visiting',
            'department' => 'diploma',
            'qualification' => 'phd',
            'hourly_rate' => 3500,
            'status' => 'active',
        ])->assertOk()->assertJsonPath('message', 'Lecture rate updated.');

        $rule->refresh();
        $this->assertSame('phd', $rule->qualification);
        $this->assertSame(350000, (int) $rule->hourly_rate_minor);

        $this->actingAs($user)->patchJson(route('payments.lecture-fees.status', $rule), [
            'status' => 'inactive',
        ])->assertOk()->assertJsonPath('message', 'Lecture rate deactivated.');
        $this->assertFalse($rule->fresh()->is_active);

        $this->actingAs($user)->get(route('payments.lecture-fees'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()
            ->assertSee('PHD / DBA or Fellow Member of IESL', false)
            ->assertSee('Inactive', false)
            ->assertSee('ti-trash', false)
            ->assertDontSee('Assign lecture rate', false);

        $this->actingAs($user)->deleteJson(route('payments.lecture-fees.destroy', $rule))
            ->assertOk()
            ->assertJsonPath('message', 'Lecture rate deleted.');
        $this->assertDatabaseMissing('rate_rules', ['id' => $rule->id]);
    }

    private function humanResource(string $name): Resource
    {
        $category = ResourceCategory::query()->firstOrCreate(['name' => 'Human Resource']);
        $type = ResourceType::query()->firstOrCreate([
            'resource_category_id' => $category->id,
            'name' => 'Lecturer',
        ]);

        return Resource::factory()->create([
            'resource_type_id' => $type->id,
            'location_id' => Location::factory(),
            'name_model' => $name,
            'serial_number' => 'LECT-'.fake()->unique()->numerify('###'),
            'status' => 'active',
        ]);
    }
}
