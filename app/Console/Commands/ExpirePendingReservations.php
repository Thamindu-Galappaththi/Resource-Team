<?php

namespace App\Console\Commands;

use App\Services\ReservationBookingService;
use Illuminate\Console\Command;

class ExpirePendingReservations extends Command
{
    public function __construct(private readonly ReservationBookingService $bookings)
    {
        parent::__construct();
    }

    protected $signature = 'reservations:expire-pending';

    protected $description = 'Expire pending resource reservations whose date has passed.';

    public function handle(): int
    {
        $expiredCount = $this->bookings->expirePastPendingReservations();

        $this->info('Expired '.$expiredCount.' pending resource reservation(s).');

        return self::SUCCESS;
    }
}
