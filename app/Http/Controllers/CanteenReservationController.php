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
use App\Notifications\CanteenReservationStatusUpdated;
use App\Notifications\NewCanteenReservationPending;
use App\Services\NotificationRecipients;
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
        $windowEnd = now()->addDays(6)->toDateString();
        $query = CanteenReservation::query()->with('requestedBy');
        $this->restrictToOwnUnlessStaff($query);

        $reservations = $query
            ->whereDate('reservation_date', '>=', $today)
            ->whereDate('reservation_date', '<=', $windowEnd)
            ->whereIn('status', CanteenReservationStatus::kitchen())
            ->orderBy('reservation_date')
            ->get()
            ->groupBy(fn (CanteenReservation $reservation) => $this->serviceDate($reservation));

        $forecast = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->toDateString();
            $dayReservations = $reservations->get($date, collect());
            $forecast->push([
                'date' => $date,
                'total' => $this->orderCount($dayReservations),
                'groups' => $dayReservations->count(),
            ]);
        }

        $todayReservations = $reservations->get($today, collect());
        $peakSlot = $todayReservations
            ->groupBy(fn (CanteenReservation $reservation) => $reservation->serviceTimeInput())
            ->sortByDesc(fn ($slot) => $this->orderCount($slot))
            ->keys()
            ->first();

        $mealBreakdown = collect(MealType::cases())
            ->map(function (MealType $meal) use ($todayReservations) {
                $group = $todayReservations->where('meal_type', $meal->value);

                return [
                    'meal' => $meal->label(),
                    'orders' => $this->orderCount($group),
                    'groups' => $group->count(),
                ];
            })
            ->filter(fn (array $row) => $row['orders'] > 0)
            ->values();

        $summaryQuery = CanteenReservation::query();
        $this->restrictToOwnUnlessStaff($summaryQuery);
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->where('status', CanteenReservationStatus::PENDING->value)->count(),
            'approved' => (clone $summaryQuery)->whereIn('status', CanteenReservationStatus::accepted())->count(),
            'rejected' => (clone $summaryQuery)->where('status', CanteenReservationStatus::REJECTED->value)->count(),
        ];

        $pendingApprovals = collect();
        $canReview = $this->seesAllCanteenReservations();

        if ($canReview) {
            $pendingApprovals = CanteenReservation::query()
                ->with(['requestedBy', 'location'])
                ->where('status', CanteenReservationStatus::PENDING->value)
                ->orderBy('reservation_date')
                ->orderBy('reservation_time')
                ->paginate(5, ['*'], 'pending_page')
                ->withQueryString()
                ->fragment('pending-requests');
        }

        return view('canteen.dashboard', [
            'summary' => $summary,
            'pendingApprovals' => $pendingApprovals,
            'canReview' => $canReview,
            'forecast' => $forecast,
            'mealBreakdown' => $mealBreakdown,
            'todayTotal' => $this->orderCount($todayReservations),
            'todayConfirmed' => $this->orderCount(
                $todayReservations->whereIn('status', CanteenReservationStatus::accepted())
            ),
            'todayPending' => $this->orderCount(
                $todayReservations->where('status', CanteenReservationStatus::PENDING->value)
            ),
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
            ->paginate(10)
            ->withQueryString();

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

        if ($this->seesAllCanteenReservations() && $request->filled('requested_by_user_id')) {
            $query->where('requested_by_user_id', $request->integer('requested_by_user_id'));
        }

        $reservations = $query->paginate(10)->appends($request->query());

        $requesters = $this->seesAllCanteenReservations()
            ? User::query()
                ->whereIn('id', CanteenReservation::query()->select('requested_by_user_id')->distinct())
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $summaryQuery = CanteenReservation::query();
        $this->restrictToOwnUnlessStaff($summaryQuery);

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->where('status', CanteenReservationStatus::PENDING->value)->count(),
            'approved' => (clone $summaryQuery)->whereIn('status', CanteenReservationStatus::accepted())->count(),
            'rejected' => (clone $summaryQuery)->where('status', CanteenReservationStatus::REJECTED->value)->count(),
        ];

        return view('canteen.reservations.index', compact('reservations', 'summary', 'requesters'));
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
        $data['status'] = CanteenReservationStatus::PENDING->value;

        $reservation = DB::transaction(function () use ($data) {
            $reservation = CanteenReservation::query()->create($data);

            NotificationRecipients::notify(
                'canteen.manage',
                new NewCanteenReservationPending($reservation),
                except: [auth()->id()]
            );

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
        unset($data['status']);

        $reservation->update($data);

        return redirect()->route('canteen.show', $reservation)->with('success', 'Reservation updated successfully.');
    }

    public function updateStatus(UpdateCanteenReservationStatusRequest $request, CanteenReservation $reservation): RedirectResponse
    {
        $this->authorize('manageStatus', $reservation);

        abort_unless($reservation->status === CanteenReservationStatus::PENDING->value, 403, 'Only pending reservations can be approved or rejected.');

        $status = $request->input('status');
        $status = $status === CanteenReservationStatus::CONFIRMED->value
            ? CanteenReservationStatus::APPROVED->value
            : $status;

        DB::transaction(function () use ($request, $reservation, $status) {
            $reservation->forceFill([
                'status' => $status,
                'approved_by_user_id' => auth()->id(),
                'approval_comments' => $request->input('approval_comments'),
            ])->save();

            NotificationRecipients::notifyOther(
                $reservation->requestedBy()->first(),
                new CanteenReservationStatusUpdated($reservation, auth()->user()),
                auth()->user()
            );
        });

        return redirect()
            ->route('canteen.show', $reservation)
            ->with('success', $status === CanteenReservationStatus::APPROVED->value
                ? 'Reservation approved.'
                : 'Reservation rejected.');
    }

    public function destroy(CanteenReservation $reservation): RedirectResponse
    {
        if (auth()->user()?->can('delete', $reservation)) {
            $reservation->delete();

            return redirect()->route('canteen.index')->with('success', 'Reservation deleted.');
        }

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
        ];
    }

    private function serviceDate(CanteenReservation $reservation): string
    {
        return substr((string) $reservation->getRawOriginal('reservation_date'), 0, 10);
    }

    private function orderCount($reservations): int
    {
        $items = $reservations instanceof \Illuminate\Support\Enumerable
            ? $reservations
            : collect($reservations);

        return (int) $items->sum(
            fn (CanteenReservation $reservation) => (int) $reservation->number_of_orders
        );
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
        return (bool) auth()->user()?->hasPermission('canteen.manage');
    }

}
