<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\User;
use App\Services\ReservationBookingService;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->where('email', 'admin@nebula.local')->first()
            ?? User::query()->first();
        if (! $actor) {
            return;
        }

        $service = app(ReservationBookingService::class);
        $today = now($service->timezone());

        $bookings = [
            ['serial' => 'WEL-AUD-MAIN', 'days' => 0, 'start' => '10:00', 'end' => '12:00', 'title' => 'Orientation in Main Auditorium', 'purpose' => 'New intake briefing'],
            ['serial' => 'WEL-LR-01', 'days' => 0, 'start' => '14:00', 'end' => '16:00', 'title' => 'CCNA Batch 12 lecture', 'purpose' => 'Classroom session'],
            ['serial' => 'WEL-CLAB-01', 'days' => 1, 'start' => '09:00', 'end' => '12:00', 'title' => 'Programming workshop', 'purpose' => 'Hands-on training'],
            ['serial' => 'WEL-LAB-EEC', 'days' => 2, 'start' => '13:00', 'end' => '16:00', 'title' => 'Control lab practical', 'purpose' => 'Lab session'],
            ['serial' => 'MOR-AUD', 'days' => 3, 'start' => '09:00', 'end' => '12:00', 'title' => 'Moratuwa guest lecture', 'purpose' => 'Industry talk'],
            ['serial' => 'PER-AUD', 'days' => 5, 'start' => '09:00', 'end' => '12:00', 'title' => 'Peradeniya staff briefing', 'purpose' => 'Campus meeting'],
        ];

        foreach ($bookings as $booking) {
            $resource = Resource::query()->where('serial_number', $booking['serial'])->first();
            if (! $resource || $resource->status !== 'active') {
                continue;
            }

            try {
                $service->create([
                    'resource_ids' => [$resource->id],
                    'requester_id' => $actor->id,
                    'location_id' => $resource->location_id,
                    'reservation_date' => $today->copy()->addDays($booking['days'])->toDateString(),
                    'start_time' => $booking['start'],
                    'end_time' => $booking['end'],
                    'purpose' => $booking['purpose'],
                    'title' => $booking['title'],
                    'attendee_count' => 12,
                ], $actor);
            } catch (\Throwable) {
                // Safe to re-run: overlapping sample rows are ignored.
            }
        }
    }
}
