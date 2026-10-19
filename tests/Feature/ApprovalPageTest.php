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

class ApprovalPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_approvals_page_uses_static_cards_tables_and_shared_filters(): void
    {
        $admin = User::factory()->role('admin')->create();
        $requester = User::factory()->role('slt_employee')->create();
        $resource = Resource::factory()->create(['status' => 'active', 'name_model' => 'Board Room']);
        $this->pendingReservation($requester, $resource, 'Lab briefing');

        $this->actingAs($admin)->get(route('approvals.index'))
            ->assertOk()
            ->assertSee('Waiting for review', false)
            ->assertSee('Approval history', false)
            ->assertSee('id="ap-filters"', false)
            ->assertSee('id="ap-clear"', false)
            ->assertSee('Pending requests', false)
            ->assertSee('Lab briefing', false)
            ->assertSee('Board Room', false)
            ->assertSee('data-ap-view', false)
            ->assertDontSee('href="#pending-heading"', false)
            ->assertDontSee('href="#history-heading"', false)
            ->assertDontSee('data-bs-target="#approval-view-', false)
            ->assertSee('ap-stats', false);
    }

    public function test_approvals_search_filters_pending_requests(): void
    {
        $admin = User::factory()->role('admin')->create();
        $requester = User::factory()->role('slt_employee')->create();
        $resource = Resource::factory()->create(['status' => 'active', 'name_model' => 'Hall A']);
        $keep = $this->pendingReservation($requester, $resource, 'Keep this request');
        $this->pendingReservation($requester, $resource, 'Hide this request');

        $this->actingAs($admin)
            ->get(route('approvals.index', ['search' => $keep->reference]))
            ->assertOk()
            ->assertSee($keep->reference, false)
            ->assertDontSee('Hide this request', false);

        $json = $this->actingAs($admin)
            ->getJson(route('approvals.index', ['search' => $keep->reference]))
            ->assertOk()
            ->json();
        $this->assertTrue(collect($json['pending'])->contains(fn ($row) => $row['reference'] === $keep->reference));
        $this->assertFalse(collect($json['pending'])->contains(fn ($row) => $row['title'] === 'Hide this request'));
    }

    public function test_pending_pagination_uses_named_page_query(): void
    {
        $admin = User::factory()->role('admin')->create();
        $requester = User::factory()->role('slt_employee')->create();
        $resource = Resource::factory()->create(['status' => 'active', 'name_model' => 'Hall A']);
        foreach (range(1, 11) as $index) {
            $this->pendingReservation($requester, $resource, 'Queued request '.$index);
        }

        $this->actingAs($admin)->get(route('approvals.index'))
            ->assertOk()
            ->assertSee('pending_page=2', false)
            ->assertSee('Queued request 1', false);

        $this->actingAs($admin)->get(route('approvals.index', ['pending_page' => 2]))
            ->assertOk()
            ->assertSee('Queued request 11', false);
    }

    public function test_archive_requires_a_reason_and_hides_the_row(): void
    {
        $admin = User::factory()->role('admin')->create();
        $requester = User::factory()->role('slt_employee')->create();
        $resource = Resource::factory()->create(['status' => 'active', 'name_model' => 'Hall A']);
        $pending = $this->pendingReservation($requester, $resource, 'Needs removal');

        $this->actingAs($admin)
            ->delete(route('approvals.archive', $pending), ['archive_reason' => 'Duplicate request'])
            ->assertRedirect();

        $this->assertNotNull($pending->fresh()->approval_archived_at);
        $this->actingAs($admin)->get(route('approvals.index'))
            ->assertOk()
            ->assertDontSee('Needs removal', false);
    }

    private function pendingReservation(User $requester, Resource $resource, string $title): Reservation
    {
        $start = now('UTC')->addDay()->setTime(4, 30);
        $reservation = Reservation::factory()->create([
            'requester_id' => $requester->id,
            'created_by_user_id' => $requester->id,
            'location_id' => $resource->location_id,
            'title' => $title,
            'purpose' => $title,
            'status' => ReservationStatus::PENDING_APPROVAL->value,
            'reservation_date' => now('Asia/Colombo')->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);
        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id,
            'resource_id' => $resource->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'resource_name_snapshot' => $resource->name_model,
        ]);

        return $reservation;
    }
}
