<?php

namespace App\Http\Controllers;

use App\Enums\CanteenReservationStatus;
use App\Enums\MealType;
use App\Http\Requests\StoreCanteenReservationRequest;
use App\Http\Requests\UpdateCanteenReservationRequest;
use App\Http\Requests\UpdateCanteenReservationStatusRequest;
use App\Models\CanteenReservation;
use App\Models\Location;
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
        $query = CanteenReservation::query()->with('requestedBy');
        $this->restrictToOwnUnlessStaff($query);

        $reservations = $query
            ->whereBetween('reservation_date', [$today, now()->addDays(6)->toDateString()])
            ->whereIn('status', CanteenReservationStatus::kitchen())
            ->orderBy('reservation_date')
            ->get();

        $forecast = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->toDateString();
            $dayReservations = $reservations->filter(
                fn (CanteenReservation $reservation) => $reservation->reservation_date->toDateString() === $date
            );
            $forecast->push([
                'date' => $date,
                'total' => $dayReservations->sum('number_of_orders'),
                'groups' => $dayReservations->count(),
            ]);
        }

        $todayReservations = $reservations->filter(
            fn (CanteenReservation $reservation) => $reservation->reservation_date->toDateString() === $today
        );
        $peakSlot = $todayReservations
            ->groupBy(fn (CanteenReservation $reservation) => $reservation->serviceTimeInput())
            ->sortByDesc(fn ($slot) => $slot->sum('number_of_orders'))
            ->keys()
            ->first();

        $mealBreakdown = $todayReservations
            ->groupBy('meal_type')
            ->map(fn ($group, $mealType) => [
                'meal' => MealType::tryFrom((string) $mealType)?->label() ?? ucfirst(str_replace('_', ' ', (string) $mealType)),
                'orders' => $group->sum('number_of_orders'),
                'groups' => $group->count(),
            ])
            ->values();

        return view('canteen.dashboard', [
            'forecast' => $forecast,
            'mealBreakdown' => $mealBreakdown,
            'todayTotal' => $todayReservations->sum('number_of_orders'),
            'todayConfirmed' => $todayReservations
                ->where('status', CanteenReservationStatus::CONFIRMED->value)
                ->sum('number_of_orders'),
            'todayPending' => $todayReservations
                ->where('status', CanteenReservationStatus::PENDING->value)
                ->count(),
            'todayBookings' => $todayReservations->count(),
            'peakSlot' => $peakSlot
                ? (\Illuminate\Support\Facades\Date::createFromFormat('H:i', $peakSlot)?->format('g:i A') ?? $peakSlot)
                : null,
        ]);
    }

    public function forecast(string $date): View
    {
        $this->authorize('viewAny', CanteenReservation::class);
        abort_unless((bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 404);

        $query = CanteenReservation::query()->with(['requestedBy', 'location']);
        $this->restrictToOwnUnlessStaff($query);

        $records = $query
            ->whereDate('reservation_date', $date)
            ->whereIn('status', CanteenReservationStatus::kitchen())
            ->orderBy('reservation_time')
            ->get();

        return view('canteen.forecast', compact('date', 'records'));
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CanteenReservation::class);

        $query = CanteenReservation::query()
            ->with(['requestedBy', 'approvedBy', 'location']);

        $this->restrictToOwnUnlessStaff($query);

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
        $this->restrictToOwnUnlessStaff($summaryQuery);

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

        return view('canteen.reservations.create', $this->formLookups());
    }

    public function store(StoreCanteenReservationRequest $request): RedirectResponse
    {
        $this->authorize('create', CanteenReservation::class);

        $data = $request->validated();
        $data['requested_by_user_id'] = auth()->id();
        $data['status'] = $this->statusForOrderCount((int) $data['number_of_orders']);

        $reservation = DB::transaction(function () use ($data) {
            $reservation = CanteenReservation::query()->create($data);

            $reservation->requestedBy()->first()?->notify(new CanteenReservationCreated($reservation));

            if ($reservation->status === CanteenReservationStatus::PENDING->value) {
                $approvers = User::query()->whereHas('role', function ($query) {
                    $query->whereIn('slug', ['developer', 'super_admin', 'admin', 'coordinator', 'canteen']);
                })->whereKeyNot(auth()->id())->get();

                foreach ($approvers as $approver) {
                    $approver->notify(new NewCanteenReservationPending($reservation));
                }
            }

            return $reservation;
        });

        return redirect()->route('canteen.show', $reservation)->with('success', 'Canteen reservation created successfully.');
    }

    public function show(CanteenReservation $reservation): View
    {
        $this->authorize('view', $reservation);

        $reservation->load(['requestedBy', 'location', 'approvedBy']);

        return view('canteen.reservations.show', compact('reservation'));
    }

    public function edit(CanteenReservation $reservation): View
    {
        $this->authorize('update', $reservation);

        if (! $reservation->canBeEdited() && ! auth()->user()?->hasRole('developer', 'super_admin', 'admin', 'coordinator')) {
            abort(403, 'This reservation can no longer be edited.');
        }

        return view('canteen.reservations.edit', array_merge($this->formLookups(), [
            'reservation' => $reservation->load('requestedBy'),
        ]));
    }

    public function update(UpdateCanteenReservationRequest $request, CanteenReservation $reservation): RedirectResponse
    {
        $this->authorize('update', $reservation);

        if (! $reservation->canBeEdited() && ! auth()->user()?->hasRole('developer', 'super_admin', 'admin', 'coordinator')) {
            abort(403, 'This reservation can no longer be edited.');
        }

        $data = $request->validated();
        $data['status'] = $this->statusForOrderCount((int) $data['number_of_orders']);

        $reservation->update($data);

        return redirect()->route('canteen.show', $reservation)->with('success', 'Reservation updated successfully.');
    }

    public function updateStatus(UpdateCanteenReservationStatusRequest $request, CanteenReservation $reservation): RedirectResponse
    {
        $this->authorize('manageStatus', $reservation);

        abort_unless($reservation->status === CanteenReservationStatus::PENDING->value, 403, 'Only pending reservations can be approved or rejected.');

        $status = $request->input('status');

        DB::transaction(function () use ($request, $reservation, $status) {
            $reservation->forceFill([
                'status' => $status,
                'approved_by_user_id' => auth()->id(),
                'approval_comments' => $request->input('approval_comments'),
            ])->save();

            $reservation->requestedBy()->first()?->notify(new CanteenReservationStatusUpdated($reservation, auth()->user()));
        });

        return redirect()
            ->route('canteen.show', $reservation)
            ->with('success', $status === CanteenReservationStatus::CONFIRMED->value
                ? 'Reservation confirmed.'
                : 'Reservation rejected.');
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

    private function formLookups(): array
    {
        return [
            'mealTypes' => MealType::cases(),
            'locations' => Location::query()->ordered()->get(),
            'largeGroupThreshold' => config('canteen.large_group_threshold', 50),
        ];
    }

    private function restrictToOwnUnlessStaff($query): void
    {
        if ($this->seesAllCanteenReservations()) {
            return;
        }

        $query->where('requested_by_user_id', auth()->id());
    }

    private function seesAllCanteenReservations(): bool
    {
        return (bool) auth()->user()?->hasRole('developer', 'super_admin', 'admin', 'coordinator', 'canteen');
    }

    private function statusForOrderCount(int $orders): string
    {
        return $orders > (int) config('canteen.large_group_threshold', 50)
            ? CanteenReservationStatus::PENDING->value
            : CanteenReservationStatus::CONFIRMED->value;
    }
}
