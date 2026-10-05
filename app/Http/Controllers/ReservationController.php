<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Exceptions\ReservationConflictException;
use App\Http\Requests\CancelReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Services\ReservationBookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationBookingService $bookings) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Reservation::class);

        $query = Reservation::query()
            ->with(['requester', 'createdBy', 'location', 'items.resource.type.category', 'statusHistory.actor'])
            ->latest('reservation_date')
            ->latest('start_time');

        $this->restrictToOwnUnlessStaff($query);

        $query
            ->search($request->input('search'))
            ->filter([
                'status' => $request->input('status'),
                'location_id' => $request->input('location_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'resource_id' => $request->input('resource_id'),
                'resource_category_id' => $request->input('resource_category_id'),
            ]);

        $reservations = $query->paginate(15)->appends($request->query());

        $base = Reservation::query();
        $this->restrictToOwnUnlessStaff($base);

        $summary = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', ReservationStatus::PENDING_APPROVAL->value)->count(),
            'approved' => (clone $base)->whereIn('status', [
                ReservationStatus::APPROVED->value,
                ReservationStatus::CONFIRMED->value,
            ])->count(),
            'cancelled' => (clone $base)->whereIn('status', [
                ReservationStatus::CANCELLED->value,
                ReservationStatus::REJECTED->value,
            ])->count(),
        ];

        return view('reservations.index', [
            'reservations' => $reservations,
            'summary' => $summary,
            'locations' => Location::query()->ordered()->get(),
            'categories' => ResourceCategory::query()->orderBy('name')->get(),
            'resources' => Resource::query()->orderBy('name_model')->get(['id', 'name_model', 'serial_number']),
            'statuses' => ReservationStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Reservation::class);

        return view('reservations.create', $this->formLookups($request));
    }

    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $this->authorize('create', Reservation::class);

        try {
            $reservation = $this->bookings->create($request->validated(), $request->user());
        } catch (ReservationConflictException $exception) {
            return back()->withInput()->withErrors([
                'start_time' => $exception->getMessage(),
            ]);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return redirect()
            ->route('reservations.show', $reservation)
            ->with('success', 'Reservation submitted successfully and sent for approval.');
    }

    public function show(Reservation $reservation): View
    {
        $this->authorize('view', $reservation);

        $reservation->load(['requester', 'createdBy', 'location', 'items.resource.type.category', 'statusHistory.actor']);

        return view('reservations.show', compact('reservation'));
    }

    public function cancel(CancelReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('cancel', $reservation);

        try {
            $this->bookings->cancel($reservation, $request->user(), $request->validated('cancellation_reason'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Reservation cancelled successfully.');
    }

    public function calendar(Request $request): View
    {
        $this->authorize('viewAny', Reservation::class);

        $currentDate = Carbon::now($this->bookings->timezone());
        if ($request->filled('year') && $request->filled('month')) {
            $currentDate = Carbon::create(
                (int) $request->year,
                (int) $request->month,
                1,
                0,
                0,
                0,
                $this->bookings->timezone()
            );
        }

        $year = $currentDate->year;
        $month = $currentDate->month;
        $daysInMonth = $currentDate->daysInMonth;
        $firstDayOfMonth = $currentDate->copy()->startOfMonth();
        $startingDayOfWeek = $firstDayOfMonth->dayOfWeek;

        $query = Reservation::query()
            ->with(['requester', 'location', 'items'])
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
            ->whereNotIn('status', [
                ReservationStatus::CANCELLED->value,
                ReservationStatus::REJECTED->value,
                ReservationStatus::DRAFT->value,
            ]);

        $this->restrictToOwnUnlessStaff($query);
        $query->filter([
            'status' => $request->input('status'),
            'location_id' => $request->input('location_id'),
            'resource_id' => $request->input('resource_id'),
            'resource_category_id' => $request->input('resource_category_id'),
        ]);

        $reservations = $query->orderBy('start_time')->get();

        $calendarReservations = $reservations->map(function (Reservation $reservation) {
            return [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'title' => $reservation->title,
                'purpose' => $reservation->purpose,
                'status' => $reservation->status,
                'status_label' => $reservation->statusEnum()->label(),
                'reservation_date' => $reservation->reservation_date?->toDateString(),
                'start_time' => substr((string) $reservation->start_time, 0, 5),
                'end_time' => substr((string) $reservation->end_time, 0, 5),
                'requester' => $reservation->requester?->name,
                'location' => $reservation->location?->name,
                'resources' => $reservation->resourceNames(),
                'url' => route('reservations.show', $reservation),
            ];
        });

        return view('reservations.calendar', [
            'currentDate' => $currentDate,
            'year' => $year,
            'month' => $month,
            'daysInMonth' => $daysInMonth,
            'startingDayOfWeek' => $startingDayOfWeek,
            'reservations' => $reservations,
            'calendarReservations' => $calendarReservations,
            'locations' => Location::query()->ordered()->get(),
            'categories' => ResourceCategory::query()->orderBy('name')->get(),
            'resources' => Resource::query()->orderBy('name_model')->get(['id', 'name_model']),
            'statuses' => ReservationStatus::cases(),
            'canCreate' => $request->user()?->can('create', Reservation::class),
        ]);
    }

    public function lookups(): JsonResponse
    {
        $this->authorize('create', Reservation::class);

        $resources = Resource::query()
            ->with(['type.category', 'location', 'linkedResources'])
            ->where('status', 'active')
            ->orderBy('name_model')
            ->get()
            ->map(fn (Resource $resource) => [
                'id' => $resource->id,
                'name_model' => $resource->name_model,
                'serial_number' => $resource->serial_number,
                'location_id' => $resource->location_id,
                'location_name' => $resource->location?->name,
                'category_id' => $resource->type?->resource_category_id,
                'category_name' => $resource->type?->category?->name,
                'type_id' => $resource->resource_type_id,
                'type_name' => $resource->type?->name,
                'linked_resources' => $resource->linkedResources->map(fn (Resource $linked) => [
                    'id' => $linked->id,
                    'name_model' => $linked->name_model,
                    'serial_number' => $linked->serial_number,
                    'status' => $linked->status,
                ])->values(),
            ]);

        return response()->json([
            'locations' => Location::query()->ordered()->get(['id', 'name']),
            'categories' => ResourceCategory::query()->orderBy('name')->get(['id', 'name']),
            'resources' => $resources,
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $this->authorize('create', Reservation::class);

        $data = $request->validate([
            'resource_ids' => ['required', 'array', 'min:1'],
            'resource_ids.*' => ['integer', 'exists:resources,id'],
            'reservation_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        try {
            [$startsAt, $endsAt] = $this->bookings->utcRange(
                $data['reservation_date'],
                $data['start_time'],
                $data['end_time'],
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'available' => false,
                'message' => collect($exception->errors())->flatten()->first(),
            ], 422);
        }

        $conflict = $this->bookings->hasConflict($data['resource_ids'], $startsAt, $endsAt);

        return response()->json([
            'available' => ! $conflict,
            'message' => $conflict ? 'Selected slot unavailable' : 'This slot is available.',
        ]);
    }

    private function restrictToOwnUnlessStaff($query): void
    {
        $user = auth()->user();
        if ($user?->hasRole('super_admin', 'admin', 'coordinator', 'resource_owner')) {
            return;
        }

        $query->where(function ($inner) use ($user) {
            $inner->where('requester_id', $user?->id)
                ->orWhere('created_by_user_id', $user?->id);
        });
    }

    private function formLookups(Request $request): array
    {
        return [
            'prefillDate' => $request->query('date', now($this->bookings->timezone())->toDateString()),
            'prefillStart' => $request->query('start_time', '09:00'),
            'prefillEnd' => $request->query('end_time', '10:00'),
            'prefillResourceId' => $request->query('resource_id'),
            'prefillResourceIds' => collect($request->query('resource_ids', []))
                ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values(),
        ];
    }
}
