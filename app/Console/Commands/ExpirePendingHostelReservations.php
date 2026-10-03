<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\Reservation;
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
        $reservationIds = Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->where('status', ReservationStatus::PENDING_APPROVAL->value)
            ->whereHas('hostelStay', fn ($query) => $query->where('check_in_at', '<=', now('UTC')))
            ->pluck('id');

        $expiredCount = $reservationIds
            ->filter(fn (int $reservationId) => $this->hostel->expirePendingAtCheckIn($reservationId))
            ->count();

        $this->info('Expired '.$expiredCount.' pending hostel reservation(s).');

        return self::SUCCESS;
    }
}
