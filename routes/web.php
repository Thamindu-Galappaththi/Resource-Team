<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
 
/**
 * These map 1:1 onto the fetch() calls already in create.blade.php:
 *   POST /resource-categories  -> categoryForm submit handler
 *   POST /resource-types       -> typeForm submit handler
 *   POST /resources            -> resourceForm submit handler
 * The two GET routes aren't called by the current JS (which keeps
 * state in memory client-side) but are useful for re-fetching data,
 * building an "edit"/"list" page later, or feeding
 * window.__initialCategories / __initialTypes / __initialResources
 * from the controller that renders create.blade.php.
 */

use App\Http\Controllers\ResourceCategoryController;
use App\Http\Controllers\ResourceTypeController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\LocationController;
 
Route::get('/resource-categories', [ResourceCategoryController::class, 'index'])
    ->name('resource-categories.index');
 
Route::post('/resource-categories', [ResourceCategoryController::class, 'store'])
    ->name('resource-categories.store');
 
Route::get('/resource-types', [ResourceTypeController::class, 'index'])
    ->name('resource-types.index');
 
Route::post('/resource-types', [ResourceTypeController::class, 'store'])
    ->name('resource-types.store');
 
Route::get('/resources', [ResourceController::class, 'index'])
    ->name('resources.index');
 
Route::post('/resources', [ResourceController::class, 'store'])
    ->name('resources.store');

Route::get('/resource-lookups', [ResourceController::class, 'lookups'])
    ->name('resources.lookups');

    Route::get('/locations', [LocationController::class, 'index'])
    ->name('locations.index');

    Route::get('/resource-list', [ResourceController::class, 'index'])
    ->name('resources.index');


Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboards.dashboard');
    })->name('dashboard');

    Route::get('/user-management/create-user', function () {
        return view('user-management.create-user');
    })->name('create.user');

    Route::get('/user-management', function () {
        return view('user-management.index');
    })->name('user.management');

    Route::get('/reservations/calendar', function () {
        return view('reservations.calendar');
    })->name('reservations.calendar');

    Route::get('/reservations/create', function () {
        return view('reservations.create');
    })->name('reservations.create');

    Route::get('/reservations', function () {
        return view('reservations.index');
    })->name('reservations.index');

    Route::get('/resources/create', function () {
        return view('resources.create');
    })->name('resources.create');

    Route::get('/resources', function () {
        return view('resources.index');
    })->name('resources.index');

    Route::get('/approvals/special', function () {
        return view('approvals.special');
    })->name('approvals.special');

    Route::get('/profile', function () {
        return response('Profile page setup is pending.', 200);
    })->name('user.profile');

    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');
});
