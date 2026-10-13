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
use App\Models\ResourceType;
use App\Models\User;
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
        $user = $request->user();
        $ownersQuery = User::query()
            ->whereIn('id', Reservation::query()->select('requester_id')->whereNotNull('requester_id')->distinct())
            ->orderBy('name');
        if (! $user?->hasRole('developer', 'super_admin', 'admin', 'coordinator', 'resource_owner')) {
            $ownersQuery->whereKey($user?->id);
        }
        return view('reservations.calendar', [
            'locations' => Location::query()->ordered()->get(),
            'categories' => ResourceCategory::query()->orderBy('name')->get(),
            'resourceTypes' => ResourceType::query()->with('category')->orderBy('name')->get(),
            'resources' => Resource::query()->where('status', 'active')->orderBy('name_model')->get(['id', 'name_model']),
            'owners' => $ownersQuery->get(['id', 'name']),
            'statuses' => ReservationStatus::cases(),
            'canCreate' => $request->user()?->can('create', Reservation::class),
            'eventsUrl' => route('reservations.calendar.events'),
            'availabilityUrl' => route('reservations.availability'),
            'createUrl' => route('reservations.create'),
        ]);
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reservation::class);
        $filters = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'resource_category_id' => ['nullable', 'integer', 'exists:resource_categories,id'],
            'resource_type_id' => ['nullable', 'integer', 'exists:resource_types,id'],
            'resource_id' => ['nullable', 'integer', 'exists:resources,id'],
            'requester_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:'.implode(',', ReservationStatus::values())],
        ]);

        $rangeStart = Carbon::parse($filters['start'])->utc();
        $rangeEnd = Carbon::parse($filters['end'])->utc();
        $baseQuery = Reservation::query()
            ->with(['requester', 'location', 'items.resource.type.category'])
            ->where('status', '!=', ReservationStatus::DRAFT->value)
            ->whereHas('items', fn ($items) => $items->where('starts_at', '<', $rangeEnd)->where('ends_at', '>', $rangeStart));
        $this->restrictToOwnUnlessStaff($baseQuery);

        $visibleReservations = (clone $baseQuery)->get();
        $blockingStatuses = ReservationStatus::blockingItemStatuses();
        $bookedItems = $visibleReservations->flatMap(fn (Reservation $reservation) =>
            $reservation->items
                ->filter(fn ($item) => in_array($item->status, $blockingStatuses, true) && $item->starts_at && $item->ends_at)
                ->map(fn ($item) => [
                    'reservation_id' => $reservation->id,
                    'resource_id' => $item->resource_id,
                    'starts_at' => $item->starts_at,
                    'ends_at' => $item->ends_at,
                ])
        );
        $conflicts = [];
        foreach ($bookedItems->groupBy('resource_id') as $items) {
            $items = $items->sortBy(fn ($item) => $item['starts_at']->getTimestamp())->values();
            for ($i = 0; $i < $items->count(); $i++) {
                for ($j = $i + 1; $j < $items->count(); $j++) {
                    if (! $items[$i]['starts_at']->lt($items[$j]['ends_at'])) break;
                    if ($items[$i]['reservation_id'] !== $items[$j]['reservation_id']
                        && $items[$i]['ends_at']->gt($items[$j]['starts_at'])) {
                        $conflicts[$items[$i]['reservation_id']] = true;
                        $conflicts[$items[$j]['reservation_id']] = true;
                    }
                }
            }
        }

        $query = (clone $baseQuery)
            ->when($filters['location_id'] ?? null, fn ($q, $value) => $q->where('location_id', $value))
            ->when($filters['requester_id'] ?? null, fn ($q, $value) => $q->where('requester_id', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when(($filters['resource_id'] ?? null) || ($filters['resource_type_id'] ?? null) || ($filters['resource_category_id'] ?? null), function ($q) use ($filters) {
                $q->whereHas('items.resource', function ($resources) use ($filters) {
                    if ($filters['resource_id'] ?? null) $resources->whereKey($filters['resource_id']);
                    if ($filters['resource_type_id'] ?? null) $resources->where('resource_type_id', $filters['resource_type_id']);
                    if ($filters['resource_category_id'] ?? null) {
                        $resources->whereHas('type', fn ($types) => $types->where('resource_category_id', $filters['resource_category_id']));
                    }
                });
            });

        $events = $query->orderBy('reservation_date')->orderBy('start_time')->get()->map(function (Reservation $reservation) use ($conflicts) {
            $item = $reservation->items->first();
            $start = $item?->starts_at?->timezone($this->bookings->timezone())
                ?? Carbon::parse($reservation->reservation_date->toDateString().' '.$reservation->start_time, $this->bookings->timezone());
            $end = $item?->ends_at?->timezone($this->bookings->timezone())
                ?? Carbon::parse($reservation->reservation_date->toDateString().' '.$reservation->end_time, $this->bookings->timezone());
            $minutes = max(0, (int) $start->diffInMinutes($end));
            $duration = trim(implode(' ', array_filter([
                intdiv($minutes, 60) ? intdiv($minutes, 60).' hr' : null,
                $minutes % 60 ? ($minutes % 60).' min' : null,
            ]))) ?: 'Less than a minute';
            $conflict = isset($conflicts[$reservation->id]);
            [$color, $textColor] = match ($reservation->status) {
                ReservationStatus::APPROVED->value, ReservationStatus::CONFIRMED->value, ReservationStatus::IN_PROGRESS->value => ['#16845b', '#fff'],
                ReservationStatus::CANCELLED->value, ReservationStatus::REJECTED->value, ReservationStatus::EXPIRED->value => ['#7b8493', '#fff'],
                ReservationStatus::COMPLETED->value => ['#4778d0', '#fff'],
                default => ['#d18b00', '#fff'],
            };

            return [
                'id' => (string) $reservation->id,
                'title' => $reservation->reference.' · '.($reservation->title ?: $reservation->purpose),
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'backgroundColor' => $conflict ? '#c0392b' : $color,
                'borderColor' => $conflict ? '#a93226' : $color,
                'textColor' => $textColor,
                'classNames' => $conflict ? ['reservation-conflict'] : [],
                'extendedProps' => [
                    'reference' => $reservation->reference,
                    'purpose' => $reservation->purpose,
                    'resource' => $reservation->resourceNames() ?: '—',
                    'owner' => $reservation->requester?->name ?? 'Unknown',
                    'location' => $reservation->location?->name ?? '—',
                    'duration' => $duration,
                    'status' => $reservation->statusEnum()->label(),
                    'statusKey' => $reservation->status,
                    'conflict' => $conflict,
                    'url' => request()->user()?->can('view', $reservation) ? route('reservations.show', $reservation) : null,
                ],
            ];
        });

        return response()->json($events->values());
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
        if ($user?->hasRole('developer', 'super_admin', 'admin', 'coordinator', 'resource_owner')) {
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
