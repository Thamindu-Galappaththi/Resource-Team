<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationRecipients
{
    /**
     * @param  list<int|string|null>  $except
     * @return Collection<int, User>
     */
    public static function withPermission(string $permission, array $except = []): Collection
    {
        $except = array_values(array_filter($except, fn ($id) => $id !== null && $id !== ''));

        return User::query()
            ->where('is_active', true)
            ->when($except !== [], fn ($query) => $query->whereKeyNot($except))
            ->get()
            ->filter(fn (User $user) => $user->hasPermission($permission))
            ->values();
    }

    /**
     * @param  list<int|string|null>  $except
     */
    public static function notify(string $permission, Notification $notification, array $except = []): void
    {
        $recipients = self::withPermission($permission, $except);

        if ($recipients->isEmpty()) {
            return;
        }

        NotificationFacade::send($recipients, $notification);
    }
}
