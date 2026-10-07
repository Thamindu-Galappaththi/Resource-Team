<?php

namespace App\Console\Commands;

use App\Services\HostelReservationService;
use Illuminate\Console\Command;

class CompleteHostelReservations extends Command
{
    protected $signature = 'hostel:complete-reservations';

    protected $description = 'Complete approved hostel reservations at their scheduled check-out time.';

    public function handle(HostelReservationService $hostel): int
    {
        $count = $hostel->completePastApprovedReservations();
        $this->info('Completed '.$count.' hostel reservation(s).');

        return self::SUCCESS;
    }
}
