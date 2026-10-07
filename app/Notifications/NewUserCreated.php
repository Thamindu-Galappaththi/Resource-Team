<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewUserCreated extends Notification
{
    use Queueable;

    public function __construct(public User $user, public User $creator)
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
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'created_by_id' => $this->creator->id,
            'created_by' => $this->creator->name,
            'icon' => 'ti-user-plus',
            'url' => route('user.management'),
            'message' => $this->creator->name.' created a new user account for '.$this->user->name.'.',
        ];
    }
}
