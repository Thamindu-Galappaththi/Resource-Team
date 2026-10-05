<?php

namespace App\Http\Controllers;

use App\Enums\CanteenReservationStatus;
use App\Enums\MealType;
use App\Http\Requests\StoreCanteenReservationRequest;
use App\Http\Requests\UpdateCanteenReservationRequest;
use App\Http\Requests\UpdateCanteenReservationStatusRequest;
use App\Models\CanteenReservation;
use App\Models\Location;
use App\Models\Role;
use App\Models\Resource;
use App\Models\User;
use App\Notifications\CanteenReservationCreated;
use App\Notifications\CanteenReservationStatusUpdated;
use App\Notifications\NewCanteenReservationPending;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CanteenReservationController extends Controller
{
    public function dashboard(): View
    {
        $this->authorize('viewAny', CanteenReservation::class);

        $today = now()->toDateString();
        $reservations = CanteenReservation::query()
            ->with('requestedBy')
            ->whereBetween('reservation_date', [now()->startOfDay()->toDateString(), now()->addDays(6)->toDateString()])
            ->orderBy('reservation_date')
            ->get();

        $forecast = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->toDateString();
            $forecast->push([
                'date' => $date,
                'total' => $reservations
                    ->filter(fn (CanteenReservation $reservation) => $reservation->reservation_date->toDateString() === $date)
                    ->sum('number_of_orders'),
            ]);
        }

        $todayReservations = $reservations->filter(
            fn (CanteenReservation $reservation) => $reservation->reservation_date->toDateString() === $today
        );
        $peakSlot = $todayReservations
            ->groupBy('reservation_time')
            ->sortByDesc(fn ($slot) => $slot->sum('number_of_orders'))
            ->keys()
            ->first();

        return view('canteen.dashboard', [
            'forecast' => $forecast,
            'todayTotal' => $todayReservations->sum('number_of_orders'),
            'todayBookings' => $todayReservations->count(),
            'peakSlot' => $peakSlot ? \Illuminate\Support\Facades\Date::parse($peakSlot)->format('h:i A') : null,
        ]);
    }

    public function forecast(string $date): View
    {
        $this->authorize('viewAny', CanteenReservation::class);

        $records = CanteenReservation::query()
            ->with('requestedBy')
            ->whereDate('reservation_date', $date)
            ->orderBy('reservation_time')
            ->get();

        return view('canteen.forecast', compact('date', 'records'));
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CanteenReservation::class);

        $query = CanteenReservation::query()
            ->with(['requestedBy', 'approvedBy', 'location']);

        if (! auth()->user()?->hasRole('super_admin', 'admin', 'coordinator', 'canteen')) {
            $query->where('requested_by_user_id', auth()->id());
        }

        $query
            ->search($request->input('search'))
            ->filter([
                'status' => $request->input('status'),
                'meal_type' => $request->input('meal_type'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
            ])
            ->orderByDesc('created_at');

        $reservations = $query->paginate(15)->appends($request->query());

        $summaryQuery = CanteenReservation::query();
        if (! auth()->user()?->hasRole('super_admin', 'admin', 'coordinator', 'canteen')) {
            $summaryQuery->where('requested_by_user_id', auth()->id());
        }

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->where('status', CanteenReservationStatus::PENDING->value)->count(),
            'rejected_cancelled' => (clone $summaryQuery)->whereIn('status', [CanteenReservationStatus::REJECTED->value, CanteenReservationStatus::CANCELLED->value])->count(),
        ];

        return view('canteen.reservations.index', compact('reservations', 'summary'));
    }

    public function create(): View
    {
        $this->authorize('create', CanteenReservation::class);

        return view('canteen.reservations.create', [
            'mealTypes' => MealType::values(),
            'locations' => Location::query()->orderBy('name')->get(),
            'largeGroupThreshold' => config('canteen.large_group_threshold', 50),
        ]);
    }

    public function store(StoreCanteenReservationRequest $request): RedirectResponse
    {
        $this->authorize('create', CanteenReservation::class);

        $data = $request->validated();
        $data['requested_by_user_id'] = auth()->id();

        /**
         * Default behavior: orders above the configured threshold move to pending review,
         * otherwise they are confirmed immediately. This was not explicitly specified in the BRD,
         * so the default is documented here for review during UAT before final approval.
         */
        $data['status'] = (int) $data['number_of_orders'] > (int) config('canteen.large_group_threshold', 50)
            ? CanteenReservationStatus::PENDING->value
            : CanteenReservationStatus::CONFIRMED->value;

        $reservation = DB::transaction(function () use ($data) {
            $reservation = CanteenReservation::query()->create($data);

            $reservation->requestedBy()->first()?->notify(new CanteenReservationCreated($reservation));

            if ($reservation->status === CanteenReservationStatus::PENDING->value) {
                $approvers = User::query()->whereHas('role', function ($query) {
                    $query->whereIn('slug', ['super_admin', 'admin', 'coordinator']);
                })->get();

                foreach ($approvers as $approver) {
                    $approver->notify(new NewCanteenReservationPending($reservation));
                }
            }

            return $reservation;
        });

        return redirect()->route('canteen.index')->with('success', 'Canteen reservation created successfully.');
    }

    public function show(CanteenReservation $reservation): View
    {
        $this->authorize('view', $reservation);

        return view('canteen.reservations.show', compact('reservation'));
    }

    public function edit(CanteenReservation $reservation): View
    {
        $this->authorize('update', $reservation);

        return view('canteen.reservations.edit', [
            'reservation' => $reservation->load('requestedBy'),
            'mealTypes' => MealType::values(),
            'locations' => Location::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCanteenReservationRequest $request, CanteenReservation $reservation): RedirectResponse
    {
        $this->authorize('update', $reservation);

        $data = $request->validated();

        if (! $reservation->canBeEdited()) {
            abort(403, 'This reservation can no longer be edited.');
        }

        $data['status'] = ((int) $data['number_of_orders'] > (int) config('canteen.large_group_threshold', 50))
            ? CanteenReservationStatus::PENDING->value
            : CanteenReservationStatus::CONFIRMED->value;

        $reservation->update($data);

        return redirect()->route('canteen.index')->with('success', 'Reservation updated successfully.');
    }

    public function updateStatus(UpdateCanteenReservationStatusRequest $request, CanteenReservation $reservation): RedirectResponse
    {
        $this->authorize('manageStatus', $reservation);

        $status = $request->input('status');

        DB::transaction(function () use ($request, $reservation, $status) {
            $reservation->forceFill([
                'status' => $status,
                'approved_by_user_id' => auth()->id(),
                'approval_comments' => $request->input('approval_comments'),
            ])->save();

            $reservation->requestedBy()->first()?->notify(new CanteenReservationStatusUpdated($reservation, auth()->user()));
        });

        return redirect()->back()->with('success', 'Reservation status updated.');
    }

    public function destroy(CanteenReservation $reservation): RedirectResponse
    {
        $this->authorize('cancel', $reservation);

        if (! $reservation->canBeCancelled()) {
            abort(403, 'This reservation cannot be cancelled.');
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status' => CanteenReservationStatus::CANCELLED->value,
                'approved_by_user_id' => auth()->id(),
            ]);
        });

        return redirect()->route('canteen.index')->with('success', 'Reservation cancelled.');
    }

    public function maintenance(): View
    {
        $this->authorize('viewAny', CanteenReservation::class);

        $resources = Resource::query()
            ->with(['type.category', 'location'])
            ->where('status', 'under_maintenance')
            ->orderByDesc('updated_at')
            ->get();

        return view('canteen.maintenance', [
            'resources' => $resources,
            'resourceCount' => Resource::query()->count(),
        ]);
    }
}
