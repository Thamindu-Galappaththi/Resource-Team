<?php

namespace App\Notifications;

use App\Models\CanteenReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CanteenReservationCreated extends Notification
{
    use Queueable;

    public function __construct(public CanteenReservation $reservation)
    {
        //
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'reservation_ref' => $this->reservation->reservation_ref,
            'status' => $this->reservation->status,
            'message' => 'Your canteen reservation '.$this->reservation->reservation_ref.' has been submitted.',
        ];
    }
}
