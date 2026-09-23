<?php

namespace App\Notifications;

use App\Models\CanteenReservation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CanteenReservationStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public CanteenReservation $reservation, public User $approver)
    {
        //
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $message = 'Your canteen reservation '.$this->reservation->reservation_ref.' is now '.$this->reservation->status.'.';

        if ($this->reservation->status === 'rejected' && filled($this->reservation->approval_comments)) {
            $message .= ' Comment: '.$this->reservation->approval_comments;
        }

        return [
            'reservation_id' => $this->reservation->id,
            'reservation_ref' => $this->reservation->reservation_ref,
            'status' => $this->reservation->status,
            'message' => $message,
        ];
    }
}
