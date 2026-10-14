<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationDecision extends Notification
{
    use Queueable;

    public function __construct(public Reservation $reservation, public User $approver, public string $comment) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reservation '.$this->reservation->reference.' '.$this->reservation->statusEnum()->label())
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your reservation '.$this->reservation->reference.' is '.$this->reservation->statusEnum()->label().'.')
            ->line('Comment from '.$this->approver->name.': '.$this->comment);
    }

    public function toArray($notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference,
            'status' => $this->reservation->status,
            'approver' => $this->approver->name,
            'comment' => $this->comment,
            'message' => 'Reservation '.$this->reservation->reference.' is '.$this->reservation->statusEnum()->label().'. Comment: '.$this->comment,
        ];
    }
}
