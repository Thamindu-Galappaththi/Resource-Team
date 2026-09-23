<?php

namespace App\Notifications;

use App\Models\CanteenReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCanteenReservationPending extends Notification
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
            'message' => 'A new pending canteen reservation requires review: '.$this->reservation->reservation_ref,
        ];
    }
}
