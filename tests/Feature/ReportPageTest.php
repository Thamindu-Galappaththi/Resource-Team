<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Resource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_reports_page_and_feed_load_without_full_page_form_action(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Resource Utilization')
            ->assertSee('rpt-filter-form');

        $this->actingAs($admin)
            ->getJson(route('reports.feed'))
            ->assertOk()
            ->assertJsonPath('has_filters', false)
            ->assertJsonStructure([
                'filters' => ['from_date', 'to_date'],
                'html',
                'charts' => ['trend', 'category'],
                'export' => ['utilization', 'reservations', 'lecturers'],
                'data' => ['summary', 'utilization_pager', 'reservation_pager'],
            ]);
    }

    public function test_reports_feed_filters_without_leaving_json_endpoint(): void
    {
        $admin = User::factory()->role('admin')->create();
        $resource = Resource::factory()->create(['status' => 'active', 'name_model' => 'Hall A']);
        $date = now('Asia/Colombo')->toDateString();

        $reservation = Reservation::factory()->create([
            'requester_id' => $admin->id,
            'created_by_user_id' => $admin->id,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'purpose' => 'Unique filter purpose 88',
            'reservation_date' => $date,
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id,
            'resource_id' => $resource->id,
            'resource_name_snapshot' => 'Hall A',
        ]);

        $payload = $this->actingAs($admin)
            ->getJson(route('reports.feed', [
                'from_date' => $date,
                'to_date' => $date,
                'search' => 'Unique filter purpose 88',
            ]))
            ->assertOk()
            ->assertJsonPath('has_filters', true)
            ->json();

        $this->assertStringContainsString('Unique filter purpose 88', $payload['html']);
        $this->assertSame(1, $payload['data']['summary']['total_reservations']);
        $this->assertStringContainsString('search=Unique', $payload['export']['reservations']);
    }

    public function test_resource_matrix_paginates(): void
    {
        $admin = User::factory()->role('admin')->create();
        Resource::factory()->count(12)->create(['status' => 'active']);

        $first = $this->actingAs($admin)
            ->getJson(route('reports.feed'))
            ->assertOk()
            ->json();

        $this->assertCount(10, $first['data']['resource_utilization']);
        $this->assertSame(12, $first['data']['utilization_pager']['total']);
        $this->assertSame(2, $first['data']['utilization_pager']['last']);

        $second = $this->actingAs($admin)
            ->getJson(route('reports.feed', ['util_page' => 2]))
            ->assertOk()
            ->json();

        $this->assertCount(2, $second['data']['resource_utilization']);
        $this->assertSame(11, $second['data']['utilization_pager']['from']);
    }

    public function test_export_reservation_log_includes_matching_rows(): void
    {
        $admin = User::factory()->role('admin')->create();
        $date = now('Asia/Colombo')->toDateString();
        Reservation::factory()->create([
            'requester_id' => $admin->id,
            'created_by_user_id' => $admin->id,
            'status' => ReservationStatus::APPROVED->value,
            'purpose' => 'Export me 42',
            'reference' => 'RRS-2026-EXP001',
            'reservation_date' => $date,
        ]);

        $csv = $this->actingAs($admin)
            ->get(route('reports.export', [
                'export_type' => 'reservations',
                'from_date' => $date,
                'to_date' => $date,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('RRS-2026-EXP001', $csv);
        $this->assertStringContainsString('Export me 42', $csv);
    }
}
