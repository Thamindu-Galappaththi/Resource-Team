<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ResourceCalendarController;
use App\Http\Controllers\ResourceCategoryController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ResourceTypeController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Entry
Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

// ===========================================================================
// VIEW PAGES — HTML screens only
// ===========================================================================

Route::middleware('guest')->prefix('login')->controller(AuthController::class)->group(function () {
    Route::get('/', 'showLogin')->name('login'); // login page
});

Route::middleware('guest')->get('/forgot-password', function () {
    return redirect()->route('login')->with('status', 'Please contact an administrator to reset your password.');
})->name('password.request');

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard')->name('dashboard');

    Route::prefix('user-management')->controller(UserManagementController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:user.management')->name('user.management');
        Route::get('/create-user', 'create')->middleware('permission:user.create')->name('create.user');
    });

    Route::prefix('reservations')->name('reservations.')->controller(ReservationController::class)->group(function () {
        Route::get('/', fn () => view('reservations.index'))->middleware('permission:reservations.index')->name('index');
        Route::get('/create', fn () => view('reservations.create'))->middleware('permission:reservations.create')->name('create');
        Route::get('/calendar', 'calendar')->middleware('permission:reservations.calendar')->name('calendar');
    });

    Route::prefix('resources')->name('resources.')->group(function () {
        Route::get('/', fn () => view('resources.index'))->middleware('permission:resources.index')->name('index');
        Route::get('/create', fn () => view('resources.create'))->middleware('permission:resources.create')->name('create');
    });

    Route::prefix('resource-calendar')->name('resources.')->controller(ResourceCalendarController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:resources.calendar')->name('calendar');
    });

    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/special', fn () => view('approvals.special'))->middleware('permission:approvals.special')->name('special');
    });

    Route::get('/profile', fn () => response('Profile page setup is pending.', 200))->name('user.profile');
});

// ===========================================================================
// FUNCTIONING ROUTES — JSON, form submit, lookups
// ===========================================================================

Route::middleware('guest')->prefix('login')->controller(AuthController::class)->group(function () {
    Route::post('/', 'login')->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {

    Route::prefix('resource-categories')->name('resource-categories.')->middleware('permission:resources.create')->controller(ResourceCategoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
    });

    Route::prefix('resource-types')->name('resource-types.')->middleware('permission:resources.create')->controller(ResourceTypeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
    });

    Route::prefix('resource-list')->name('resources.')->middleware('permission:resources.index,resources.create')->controller(ResourceController::class)->group(function () {
        Route::get('/', 'index')->name('list');
    });

    Route::prefix('resource-lookups')->name('resources.')->middleware('permission:resources.create')->controller(ResourceController::class)->group(function () {
        Route::get('/', 'lookups')->name('lookups');
    });

    Route::prefix('resources')->name('resources.')->controller(ResourceController::class)->group(function () {
        Route::post('/', 'store')->middleware('permission:resources.create')->name('store');
        Route::post('/{resource}/request-delete', 'requestDelete')->middleware('permission:resources.index')->name('request-delete');
        Route::post('/{resource}/approve-delete', 'approveDelete')->middleware('permission:resources.index')->name('approve-delete');
        Route::post('/{resource}/reject-delete', 'rejectDelete')->middleware('permission:resources.index')->name('reject-delete');
    });

    Route::prefix('locations')->name('locations.')->middleware('permission:resources.create')->controller(LocationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });

    Route::prefix('user-management')->controller(UserManagementController::class)->group(function () {
        Route::post('/create-user', 'store')->middleware('permission:user.create')->name('create.user.store');
        Route::get('/slt-employee', 'lookupSltEmployee')->middleware('permission:user.create')->name('slt.employee.lookup');
        Route::post('/{user}/toggle-active', 'toggleActive')->middleware('permission:user.management')->name('users.toggle-active');
        Route::post('/{user}/reset-password', 'resetPassword')->middleware('permission:user.management')->name('users.reset-password');
    });

    Route::prefix('logout')->controller(AuthController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'logout')->name('logout');
    });
});
