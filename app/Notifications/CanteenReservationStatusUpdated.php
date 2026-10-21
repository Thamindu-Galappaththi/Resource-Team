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
        return [
            'reservation_id' => $this->reservation->id,
            'reservation_ref' => $this->reservation->reservation_ref,
            'status' => $this->reservation->status,
            'comment' => $this->reservation->approval_comments,
            'icon' => 'ti-tools-kitchen-2',
            'title' => 'Canteen reservation update',
            'url' => route('canteen.show', $this->reservation),
            'message' => 'Canteen reservation '.$this->reservation->reservation_ref.' is now '.$this->reservation->status.'.',
        ];
    }
}
