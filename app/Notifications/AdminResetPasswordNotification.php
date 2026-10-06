<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminResetPasswordNotification extends Notification
{
    public function __construct(public string $plainPassword)
    {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Resource Reservation password was reset')
            ->view('emails.admin-reset-password', [
                'user' => $notifiable,
                'password' => $this->plainPassword,
                'loginUrl' => route('login'),
            ]);
    }
}
