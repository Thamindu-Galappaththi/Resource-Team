<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\CanteenReservationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HostelReservationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordSetupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SltEmployeeDashboardController;
use App\Http\Controllers\SltEmployeeRequestController;
use App\Http\Controllers\ResourceCalendarController;
use App\Http\Controllers\ResourceCategoryController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ResourcePaymentController;
use App\Http\Controllers\ResourceRateController;
use App\Http\Controllers\ResourceTypeController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\LectureFeeController;
use App\Http\Controllers\ReportController;
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
    Route::get('/', 'showLogin')->name('login');
    Route::get('/admin', 'showAdminLogin')->name('login.admin');
    Route::get('/microsoft', 'redirectToMicrosoft')->middleware('throttle:10,1')->name('login.microsoft');
    Route::get('/microsoft/callback', 'handleMicrosoftCallback')->name('login.microsoft.callback');
});

Route::middleware('guest')->get('/forgot-password', function () {
    return redirect()->route('login.admin')->with('status', 'Please contact an administrator to reset your password.');
})->name('password.request');

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard')->name('dashboard');

    Route::prefix('user-management')->controller(UserManagementController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:user.management')->name('user.management');
        Route::get('/create-user', 'create')->middleware('permission:user.create')->name('create.user');
    });

    Route::prefix('reservations')->name('reservations.')->controller(ReservationController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:reservations.index')->name('index');
        Route::get('/feed', 'feed')->middleware('permission:reservations.index')->name('feed');
        Route::get('/home', [SltEmployeeDashboardController::class, 'resource'])->middleware('permission:reservations.create')->name('home');
        Route::get('/create', 'create')->middleware('permission:reservations.create')->name('create');
        Route::get('/calendar', 'calendar')->middleware('permission:reservations.calendar')->name('calendar');
        Route::get('/calendar/stats', 'calendarStats')->middleware('permission:reservations.calendar')->name('calendar.stats');
        Route::get('/calendar/events', 'calendarEvents')->middleware('permission:reservations.calendar')->name('calendar.events');
        Route::get('/lookups', 'lookups')->middleware('permission:reservations.create')->name('lookups');
        Route::get('/availability', 'availability')->middleware('permission:reservations.create')->name('availability');
        Route::get('/my-requests', [SltEmployeeRequestController::class, 'resources'])->middleware('permission:reservations.index')->name('mine');
        Route::get('/{reservation}', 'show')->middleware('permission:reservations.index')->name('show');
    });

    Route::prefix('resources')->name('resources.')->group(function () {
        Route::get('/', [ResourceController::class, 'inventoryPage'])->middleware('permission:resources.index')->name('index');
        Route::get('/create', fn () => view('resources.create'))->middleware('permission:resources.create')->name('create');
    });

    Route::prefix('resource-calendar')->name('resources.')->controller(ResourceCalendarController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:resources.calendar')->name('calendar');
        Route::get('/events', 'events')->middleware('permission:resources.calendar')->name('calendar.events');
    });

    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->middleware('permission:approvals.index')->name('index');
        Route::get('/feed', [ApprovalController::class, 'feed'])->middleware('permission:approvals.index')->name('feed');
        Route::post('/{reservation}/decision', [ApprovalController::class, 'decide'])->middleware('permission:approvals.index')->name('decision');
        Route::delete('/{reservation}', [ApprovalController::class, 'archive'])->middleware('permission:approvals.index')->name('archive');
        Route::get('/special', [ApprovalController::class, 'special'])->middleware('permission:approvals.special')->name('special');
        Route::get('/special/feed', [ApprovalController::class, 'specialFeed'])->middleware('permission:approvals.special')->name('special.feed');
        Route::post('/{reservation}/special-decision', [ApprovalController::class, 'specialDecision'])->middleware('permission:approvals.special')->name('special-decision');
    });

    Route::prefix('hostel')->name('hostel.')->controller(HostelReservationController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:hostel.index')->name('index');
        Route::get('/home', [SltEmployeeDashboardController::class, 'hostel'])->middleware('permission:hostel.create')->name('home');
        Route::get('/calendar', [SltEmployeeDashboardController::class, 'hostelCalendar'])->middleware('permission:hostel.index')->name('calendar');
        Route::get('/create', 'create')->middleware('permission:hostel.create')->name('create');
        Route::get('/my-requests', [SltEmployeeRequestController::class, 'hostel'])->middleware('permission:hostel.index')->name('mine');
        Route::get('/{reservation}', 'show')->middleware('permission:hostel.index')->name('show');
    });

    Route::prefix('canteen')->name('canteen.')->controller(CanteenReservationController::class)->group(function () {
        Route::get('/', 'dashboard')->middleware('permission:canteen.view')->name('dashboard');
        Route::get('/forecast/{date}', 'forecast')->middleware('permission:canteen.view')->name('forecast');
        Route::get('/home', [SltEmployeeDashboardController::class, 'canteen'])->middleware('permission:canteen.create')->name('home');
        Route::get('/calendar', [SltEmployeeDashboardController::class, 'canteenCalendar'])->middleware('permission:canteen.index')->name('calendar');
        Route::get('/reservations', 'index')->middleware('permission:canteen.index')->name('index');
        Route::get('/reservations/create', 'create')->middleware('permission:canteen.create')->name('create');
        Route::get('/reservations/my-requests', [SltEmployeeRequestController::class, 'canteen'])->middleware('permission:canteen.index')->name('mine');
        Route::get('/reservations/{reservation}', 'show')->middleware('permission:canteen.index')->name('show');
        Route::get('/reservations/{reservation}/edit', 'edit')->middleware('permission:canteen.create')->name('edit');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/lecture-fees', [LectureFeeController::class, 'index'])->middleware('permission:payments.view')->name('lecture-fees');
        Route::get('/resources', [ResourcePaymentController::class, 'index'])->middleware('permission:payments.view')->name('resources');
        Route::get('/resources/lookup', [ResourcePaymentController::class, 'lookup'])->middleware('permission:payments.view')->name('resources.lookup');
        Route::get('/resources/invoices/{reference}/print', [ResourcePaymentController::class, 'print'])->middleware('permission:payments.view')->name('resources.print');
        Route::post('/resources/usage/confirm', [ResourcePaymentController::class, 'confirmUsage'])->middleware('permission:payments.view')->name('resources.usage.confirm');
        Route::get('/resource-rates', [ResourceRateController::class, 'index'])->middleware('permission:payments.rates.manage')->name('rates.index');
        Route::post('/resource-rates', [ResourceRateController::class, 'store'])->middleware('permission:payments.rates.manage')->name('rates.store');
        Route::put('/resource-rates/{rate}', [ResourceRateController::class, 'update'])->middleware('permission:payments.rates.manage')->name('rates.update');
        Route::patch('/resource-rates/{rate}/deactivate', [ResourceRateController::class, 'deactivate'])->middleware('permission:payments.rates.manage')->name('rates.deactivate');
        Route::patch('/resource-rates/{rate}/activate', [ResourceRateController::class, 'activate'])->middleware('permission:payments.rates.manage')->name('rates.activate');
        Route::post('/resource-payment-discounts', [ResourceRateController::class, 'storeDiscount'])->middleware('permission:payments.rates.manage')->name('discounts.store');
        Route::put('/resource-payment-discounts/{rule}', [ResourceRateController::class, 'updateDiscount'])->middleware('permission:payments.rates.manage')->name('discounts.update');
        Route::patch('/resource-payment-discounts/{rule}/deactivate', [ResourceRateController::class, 'deactivateDiscount'])->middleware('permission:payments.rates.manage')->name('discounts.deactivate');
        Route::patch('/resource-payment-discounts/{rule}/activate', [ResourceRateController::class, 'activateDiscount'])->middleware('permission:payments.rates.manage')->name('discounts.activate');
    });

    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->controller(ReportController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/feed', 'feed')->name('feed');
        Route::get('/export', 'exportCsv')->name('export');
    });

    Route::get('/profile', [ProfileController::class, 'show'])->name('user.profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('user.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('user.profile.password');
});

// ===========================================================================
// FUNCTIONING ROUTES — JSON, form submit, lookups
// ===========================================================================

Route::middleware('guest')->prefix('login')->controller(AuthController::class)->group(function () {
    Route::post('/', 'login')->middleware('throttle:5,1')->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {

    Route::prefix('resource-categories')->name('resource-categories.')->middleware('permission:resources.create')->controller(ResourceCategoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{category}', 'update')->name('update');
        Route::get('/{category}/delete-check', 'deleteCheck')->name('delete-check');
        Route::delete('/{category}', 'destroy')->name('destroy');
    });

    Route::prefix('resource-types')->name('resource-types.')->middleware('permission:resources.create')->controller(ResourceTypeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
    });

    Route::prefix('resource-list')->name('resources.')->middleware('permission:resources.index,resources.create')->controller(ResourceController::class)->group(function () {
        Route::get('/', 'index')->name('list');
    });

    Route::post('/resource-links', [ResourceController::class, 'storeLink'])
        ->middleware('permission:resources.create')
        ->name('resource-links.store');

    Route::prefix('resource-lookups')->name('resources.')->middleware('permission:resources.create')->controller(ResourceController::class)->group(function () {
        Route::get('/', 'lookups')->name('lookups');
    });

    Route::prefix('resources')->name('resources.')->controller(ResourceController::class)->group(function () {
        Route::get('/{resource}', 'show')->middleware('permission:resources.index,resources.create')->name('show');
        Route::put('/{resource}', 'update')->middleware('permission:resources.create')->name('update');
        Route::delete('/{resource}', 'destroy')->middleware('permission:resources.create')->name('destroy');
        Route::post('/', 'store')->middleware('permission:resources.create')->name('store');
        Route::post('/{resource}/request-delete', 'requestDelete')->middleware('permission:resources.create')->name('request-delete');
        Route::post('/{resource}/approve-delete', 'approveDelete')->middleware('permission:resources.create')->name('approve-delete');
        Route::post('/{resource}/reject-delete', 'rejectDelete')->middleware('permission:resources.create')->name('reject-delete');
    });

    Route::prefix('locations')->name('locations.')->middleware('permission:resources.create')->controller(LocationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });

    Route::prefix('canteen')->name('canteen.')->controller(CanteenReservationController::class)->group(function () {
        Route::post('/reservations', 'store')->middleware('permission:canteen.create')->name('store');
        Route::put('/reservations/{reservation}', 'update')->middleware('permission:canteen.create')->name('update');
        Route::patch('/reservations/{reservation}/status', 'updateStatus')->middleware('permission:canteen.manage')->name('status');
        Route::delete('/reservations/{reservation}', 'destroy')->middleware('permission:canteen.create,canteen.manage')->name('destroy');

    });

    Route::prefix('user-management')->controller(UserManagementController::class)->group(function () {
        Route::post('/create-user', 'store')->middleware('permission:user.create')->name('create.user.store');
        Route::get('/slt-employee', 'lookupSltEmployee')->middleware('permission:user.create,user.management')->name('slt.employee.lookup');
        Route::delete('/{user}', 'destroy')->middleware('permission:user.management')->name('users.destroy');
        Route::put('/{user}', 'update')->middleware('permission:user.management')->name('users.update');
        Route::post('/{user}/toggle-active', 'toggleActive')->middleware('permission:user.management')->name('users.toggle-active');
        Route::post('/{user}/reset-password', 'resetPassword')->middleware('permission:user.management')->name('users.reset-password');
        Route::post('/{user}/resend-password-setup', 'resendPasswordSetup')->middleware('permission:user.management')->name('users.resend-password-setup');
    });

    Route::prefix('reservations')->name('reservations.')->controller(ReservationController::class)->group(function () {
        Route::post('/', 'store')->middleware('permission:reservations.create')->name('store');
        Route::post('/{reservation}/cancel', 'cancel')->middleware('permission:reservations.index,reservations.create')->name('cancel');
    });

    Route::prefix('hostel')->name('hostel.')->controller(HostelReservationController::class)->group(function () {
        Route::post('/', 'store')->middleware('permission:hostel.create')->name('store');
        Route::post('/{reservation}/cancel', 'cancel')->middleware('permission:hostel.index,hostel.create,hostel.manage')->name('cancel');
        Route::post('/{reservation}/approval', 'updateApproval')->middleware('permission:hostel.manage')->name('approval');
    });

    Route::prefix('notifications')->name('notifications.')->controller(NotificationController::class)->group(function () {
        Route::get('/feed', 'feed')->name('feed');
        Route::post('/clear-all', 'clearAll')->name('clear-all');
        Route::post('/mark-read', 'markAllRead')->name('mark-read');
        Route::post('/{notification}/read', 'markRead')->name('read');
        Route::post('/{notification}/unread', 'markUnread')->name('unread');
        Route::delete('/{notification}', 'destroy')->name('destroy');
    });

    Route::prefix('logout')->controller(AuthController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'logout')->name('logout');
    });
});

// Password setup links carry a signed reset token. Keep these accessible when
// an administrator is already authenticated, otherwise guest middleware would
// redirect the link to the dashboard before the recipient can set a password.
Route::get('/setup-password/{token}', [PasswordSetupController::class, 'showResetForm'])->name('password.reset');
Route::post('/setup-password', [PasswordSetupController::class, 'reset'])->name('password.update');
Route::get('/reset-password/{token}', function (string $token) {
    return redirect()->route('password.reset', array_filter([
        'token' => $token,
        'email' => request()->query('email'),
    ]));
});
