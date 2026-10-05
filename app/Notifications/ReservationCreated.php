<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservationCreated extends Notification
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
            'status' => $this->reservation->status,
            'message' => 'Reservation '.$this->reservation->reference.' submitted successfully and sent for approval.',
        ];
    }
}
