<?php

namespace App\Console\Commands;

use App\Services\HostelReservationService;
use Illuminate\Console\Command;

class ExpirePendingHostelReservations extends Command
{
    public function __construct(private readonly HostelReservationService $hostel)
    {
        parent::__construct();
    }

    protected $signature = 'hostel:expire-pending-reservations';

    protected $description = 'Expire pending hostel requests when their scheduled check-in time passes.';

    public function handle(): int
    {
        $expiredCount = $this->hostel->expireOverduePendingReservations();

        $this->info('Expired '.$expiredCount.' pending hostel reservation(s).');

        return self::SUCCESS;
    }
}
