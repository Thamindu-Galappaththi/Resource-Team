<?php

namespace App\Providers;

use App\Models\CanteenReservation;
use App\Models\Reservation;
use App\Policies\CanteenReservationPolicy;
use App\Policies\ReservationPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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

        // Views, controllers and tests use both "canteen.X" and
        // "canteen.reservations.X". Only the short names are real routes,
        // so resolve the long names to the short ones.
        URL::resolveMissingNamedRoutesUsing(function ($name, $parameters, $absolute) {
            $prefix = 'canteen.reservations.';

            if (str_starts_with($name, $prefix)) {
                return route('canteen.' . substr($name, strlen($prefix)), $parameters, $absolute);
            }

            return null;
        });
    }
}
