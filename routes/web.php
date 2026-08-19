<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/user-management/create-user', [UserManagementController::class, 'create'])->name('create.user');
    Route::post('/user-management/create-user', [UserManagementController::class, 'store'])->name('create.user.store');
    Route::get('/user-management/slt-employee', [UserManagementController::class, 'lookupSltEmployee'])->name('slt.employee.lookup');

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
