<?php

namespace App\Providers;

use App\Models\CanteenReservation;
use App\Models\DatabaseNotification;
use App\Models\Reservation;
use App\Policies\CanteenReservationPolicy;
use App\Policies\ReservationPolicy;
use App\Services\NotificationPresenter;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
                ? $user->notifications()->latest()->limit(40)->get()->map(
                    fn (DatabaseNotification $notification) => NotificationPresenter::toArray($notification)
                )
                : collect();

            $view->with([
                'headerNotifications' => $notifications,
                'unreadNotificationCount' => $user ? $user->unreadNotifications()->count() : 0,
            ]);
        });
    }
}
