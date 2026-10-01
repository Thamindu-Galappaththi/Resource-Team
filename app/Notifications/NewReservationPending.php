<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReservationPending extends Notification
{
    use Queueable;

    public function __construct(public Reservation $reservation)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference,
            'message' => 'A new reservation requires approval: '.$this->reservation->reference,
        ];
    }
}
