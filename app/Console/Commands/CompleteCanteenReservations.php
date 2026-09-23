<?php

namespace App\Console\Commands;

use App\Enums\CanteenReservationStatus;
use App\Models\CanteenReservation;
use Illuminate\Console\Command;

class CompleteCanteenReservations extends Command
{
    protected $signature = 'canteen:complete-reservations';

    protected $description = 'Mark confirmed canteen reservations as completed once their date and time have passed.';

    public function handle(): int
    {
        $updated = CanteenReservation::query()
            ->where('status', CanteenReservationStatus::CONFIRMED->value)
            ->where('reservation_date', '<=', now()->toDateString())
            ->get()
            ->filter(function (CanteenReservation $reservation) {
                $time = $reservation->reservation_time ?? '00:00:00';

                return $reservation->reservation_date->copy()->setTimeFromTimeString($time)->isPast();
            });

        foreach ($updated as $reservation) {
            $reservation->update(['status' => CanteenReservationStatus::COMPLETED->value]);
        }

        $this->info('Completed '.$updated->count().' canteen reservation(s).');

        return self::SUCCESS;
    }
}
