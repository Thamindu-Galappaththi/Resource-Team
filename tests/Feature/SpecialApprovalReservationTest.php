<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Permission;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use App\Models\User;
use App\Notifications\NewReservationPending;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpecialApprovalReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_reservation_stores_name_lecturer_and_goes_to_special_approvals_with_documents(): void
    {
        Notification::fake();
        $creator = User::factory()->role('coordinator')->create();
        $approver = User::factory()->role('resource_owner')->create();
        $special = User::factory()->role('admin')->create();
        $outsider = User::factory()->role('slt_employee')->create();
        $resource = Resource::factory()->create(['status' => 'active']);

        $this->actingAs($creator)->post(route('reservations.store'), $this->payload($resource, [
            'title' => 'Networking training',
            'lecturer_name' => 'Mr. Kasun Perera',
            'special_approval' => '1',
            'special_documents' => [
                UploadedFile::fake()->create('approval-letter.pdf', 200, 'application/pdf'),
                UploadedFile::fake()->image('email.png'),
            ],
        ]))->assertRedirect()
            ->assertSessionHas('success', 'Reservation submitted successfully and sent for special approval.');

        $reservation = Reservation::query()->with('documents')->firstOrFail();
        $this->assertSame('Networking training', $reservation->title);
        $this->assertSame('Mr. Kasun Perera', $reservation->lecturer_name);
        $this->assertTrue($reservation->special_approval);
        $this->assertSame(['approval-letter.pdf', 'email.png'], $reservation->documents->pluck('original_name')->all());
        Notification::assertSentTo($special, NewReservationPending::class);
        Notification::assertNotSentTo($approver, NewReservationPending::class);

        $this->actingAs($approver)->getJson(route('approvals.feed'))
            ->assertOk()
            ->assertJsonPath('summary.pending', 0);
        $this->post(route('approvals.decision', $reservation), ['decision' => 'approve', 'comment' => 'ok'])->assertForbidden();

        $this->actingAs($special)->getJson(route('approvals.special.feed'))
            ->assertOk()
            ->assertJsonPath('summary.special', 1)
            ->assertJsonPath('pending.0.lecturer', 'Mr. Kasun Perera')
            ->assertJsonPath('pending.0.documents.1.name', 'email.png');

        $document = $reservation->documents->first();
        $this->get($document->downloadUrl())->assertOk();
        $this->actingAs($outsider)->get($document->downloadUrl())->assertForbidden();

        $hostelManager = User::factory()->role('hostel_manager')->create();
        $hostelManager->extraPermissions()->attach(Permission::query()->where('slug', 'approvals.special')->value('id'));
        $this->actingAs($hostelManager->refresh())->getJson(route('approvals.special.feed'))
            ->assertOk()
            ->assertJsonPath('summary.special', 0)
            ->assertJsonPath('summary.pending', 0);
        $this->post(route('approvals.special-decision', $reservation), ['decision' => 'approve', 'comment' => 'Hostel only'])
            ->assertForbidden();

        $this->actingAs($special)
            ->post(route('approvals.special-decision', $reservation), ['decision' => 'approve', 'comment' => 'Exception approved'])
            ->assertRedirect(route('approvals.special'));
        $this->assertSame(ReservationStatus::APPROVED->value, $reservation->fresh()->status);
    }

    public function test_documents_are_ignored_without_special_approval_and_validated_when_ticked(): void
    {
        $creator = User::factory()->role('coordinator')->create();
        $resource = Resource::factory()->create(['status' => 'active']);

        $this->actingAs($creator)->post(route('reservations.store'), $this->payload($resource, [
            'special_documents' => [UploadedFile::fake()->create('note.pdf', 10, 'application/pdf')],
        ]))->assertRedirect();
        $this->assertFalse(Reservation::query()->firstOrFail()->special_approval);
        $this->assertDatabaseCount('reservation_documents', 0);

        $this->post(route('reservations.store'), $this->payload($resource, [
            'reservation_date' => now()->addDays(2)->toDateString(),
            'special_approval' => '1',
            'special_documents' => [UploadedFile::fake()->create('virus.exe', 10)],
        ]))->assertSessionHasErrors(['special_documents.0' => 'Supporting documents must be PDF, Word, JPG, or PNG files.']);

        $this->post(route('reservations.store'), $this->payload($resource, ['title' => '']))
            ->assertSessionHasErrors(['title' => 'Enter a reservation name.']);
    }

    public function test_create_form_hides_hostel_rooms_and_shows_links_from_both_sides(): void
    {
        $creator = User::factory()->role('coordinator')->create();
        $hostelCategory = ResourceCategory::query()->create(['name' => config('hostel.category_name')]);
        $bed = ResourceType::query()->create(['resource_category_id' => $hostelCategory->id, 'name' => 'Bed']);
        $hostel = Resource::factory()->create([
            'resource_type_id' => $bed->id,
            'name_model' => 'Welisara Hostel',
            'status' => 'active',
            'bed_counts' => ['permanent' => 1, 'female' => 1, 'male' => 1],
            'bed_count' => 3,
        ]);
        $hall = Resource::factory()->create(['name_model' => 'Hall A', 'status' => 'active']);
        $projector = Resource::factory()->create(['name_model' => 'Projector X', 'status' => 'active']);
        $projector->linkedResources()->attach($hall->id);

        $lookups = $this->actingAs($creator)->getJson(route('reservations.lookups'))->assertOk();
        $this->assertNotContains($hostelCategory->id, collect($lookups->json('categories'))->pluck('id'));
        $this->assertNotContains($hostel->id, collect($lookups->json('resources'))->pluck('id'));
        $hallRow = collect($lookups->json('resources'))->firstWhere('id', $hall->id);
        $this->assertSame(['Projector X'], collect($hallRow['linked_resources'])->pluck('name_model')->all());

        $this->post(route('reservations.store'), $this->payload($hostel, ['category_id' => $hostelCategory->id]))
            ->assertSessionHasErrors(['category_id' => 'Hostel rooms are booked from Hostel Reservations.']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Resource $resource, array $overrides = []): array
    {
        return array_merge([
            'resource_id' => $resource->id,
            'resource_ids' => [$resource->id],
            'location_id' => $resource->location_id,
            'category_id' => $resource->type->resource_category_id,
            'reservation_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'title' => 'Networking training',
            'purpose' => 'CCNA practical',
            'attendee_count' => 12,
        ], $overrides);
    }
}
