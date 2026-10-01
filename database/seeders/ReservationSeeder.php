<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\ReservationBookingService;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->first();
        if (! $actor) {
            return;
        }

        $location = Location::query()->first()
            ?? Location::query()->create(['name' => 'Welisara']);

        $category = ResourceCategory::query()->firstOrCreate(['name' => 'Physical Space']);
        $type = ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $category->id, 'name' => 'Lecture Hall'],
            ['description' => 'Standard teaching hall']
        );

        $resource = Resource::query()->firstOrCreate(
            ['serial_number' => 'LH-WEL-A-001'],
            [
                'resource_type_id' => $type->id,
                'location_id' => $location->id,
                'name_model' => 'Hall A',
                'status' => 'active',
            ]
        );

        $service = app(ReservationBookingService::class);
        $date = now($service->timezone())->addDays(5)->toDateString();

        try {
            $service->create([
                'resource_ids' => [$resource->id],
                'requester_id' => $actor->id,
                'location_id' => $location->id,
                'reservation_date' => $date,
                'start_time' => '09:00',
                'end_time' => '12:00',
                'purpose' => 'CCNA Batch 12 practical lab',
                'title' => 'CCNA Batch 12 Practical',
                'attendee_count' => 24,
            ], $actor);
        } catch (\Throwable) {
            // Safe to re-run: overlapping sample rows are ignored.
        }
    }
}
