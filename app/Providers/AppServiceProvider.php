<?php

namespace App\Providers;

use App\Models\CanteenReservation;
use App\Models\Reservation;
use App\Policies\CanteenReservationPolicy;
use App\Policies\ReservationPolicy;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const NOTIFICATION_ICONS = [
        'NewUserCreated' => 'ti-user-plus',
        'ReservationCreated' => 'ti-calendar-plus',
        'NewReservationPending' => 'ti-calendar-time',
        'ReservationCancelled' => 'ti-calendar-off',
        'CanteenReservationCreated' => 'ti-tools-kitchen-2',
        'NewCanteenReservationPending' => 'ti-tools-kitchen-2',
        'CanteenReservationStatusUpdated' => 'ti-tools-kitchen-2',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Paginator::useBootstrapFive();

        Gate::policy(CanteenReservation::class, CanteenReservationPolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);

        View::composer('components.sidebar', function () {
            auth()->user()?->loadMissing('role.permissions', 'roles.permissions');
        });

        View::composer('components.notification-bell', function ($view) {
            $user = auth()->user();

            $notifications = $user
                ? $user->notifications()->latest()->take(8)->get()->map(fn (DatabaseNotification $notification) => [
                    'message' => $notification->data['message'] ?? 'You have a new notification.',
                    'icon' => $notification->data['icon'] ?? self::NOTIFICATION_ICONS[class_basename($notification->type)] ?? 'ti-bell',
                    'url' => $notification->data['url'] ?? null,
                    'time' => $notification->created_at?->diffForHumans(),
                    'unread' => $notification->read_at === null,
                ])
                : collect();

            $view->with([
                'headerNotifications' => $notifications,
                'unreadNotificationCount' => $user ? $user->unreadNotifications()->count() : 0,
            ]);
        });
    }
}
