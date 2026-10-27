<?php

namespace App\Notifications;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservationDecision extends Notification
{
    use Queueable;

    public function __construct(public Reservation $reservation, public User $approver, public string $comment) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $status = $this->reservation->statusEnum();

        return [
            'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference,
            'status' => $this->reservation->status,
            'reservation_type' => $this->reservation->type,
            'approver' => $this->approver->name,
            'comment' => $this->comment,
            'icon' => $status === ReservationStatus::REJECTED ? 'ti-calendar-x' : 'ti-calendar-check',
            'title' => 'Reservation '.$status->label(),
            'url' => $this->reservation->destinationUrl(),
            'message' => 'Reservation '.$this->reservation->reference.' is '.$status->label().'.',
        ];
    }
}
