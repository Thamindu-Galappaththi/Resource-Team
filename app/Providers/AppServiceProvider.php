<?php

namespace App\Providers;

use App\Models\CanteenReservation;
use App\Policies\CanteenReservationPolicy;
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

        Gate::policy(CanteenReservation::class, CanteenReservationPolicy::class);

        View::composer('components.sidebar', function () {
            auth()->user()?->loadMissing('role.permissions');
        });
    }
}
