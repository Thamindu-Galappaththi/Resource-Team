<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Exceptions\ReservationConflictException;
use App\Http\Requests\CancelReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\User;
use App\Services\ReservationBookingService;
use App\Support\SriLankaHolidays;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationBookingService $bookings) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Reservation::class);

        return $this->htmlPage('reservations.index', [
            'payload' => $this->indexPayload($request),
            'locations' => Location::query()->ordered()->get(),
            'categories' => ResourceCategory::query()->orderBy('name')->get(),
            'resources' => Resource::query()->orderBy('name_model')->get(['id', 'name_model', 'serial_number']),
            'statuses' => ReservationStatus::cases(),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reservation::class);

        return $this->uncachedJson($this->indexPayload($request));
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

    public function edit(Request $request, Reservation $reservation): View
    {
        abort_if($reservation->type === ReservationType::HOSTEL->value, 404);
        $this->authorize('update', $reservation);
        abort_unless($reservation->canBeEdited(), 403);

        $reservation->load(['items.resource.type']);
        $primary = $reservation->items->first()?->resource;
        $lookups = $this->formLookups($request);

        return view('reservations.create', array_merge($lookups, [
            'reservation' => $reservation,
            'prefillDate' => old('reservation_date', $reservation->reservation_date?->toDateString() ?: $lookups['prefillDate']),
            'prefillStart' => old('start_time', substr((string) $reservation->start_time, 0, 5)),
            'prefillEnd' => old('end_time', substr((string) $reservation->end_time, 0, 5)),
            'prefillResourceId' => old('resource_id', $primary?->id),
            'prefillResourceIds' => collect(old('resource_ids', $reservation->items->pluck('resource_id')->all()))
                ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values(),
            'prefillLocationId' => old('location_id', $reservation->location_id ?: $primary?->location_id),
            'prefillCategoryId' => old('category_id', $primary?->type?->resource_category_id),
        ]));
    }

    public function update(StoreReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        abort_if($reservation->type === ReservationType::HOSTEL->value, 404);
        $this->authorize('update', $reservation);
        abort_unless($reservation->canBeEdited(), 403);

        try {
            $reservation = $this->bookings->updatePending($reservation, $request->validated(), $request->user());
        } catch (ReservationConflictException $exception) {
            return back()->withInput()->withErrors([
                'start_time' => $exception->getMessage(),
            ]);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return redirect()
            ->route('reservations.show', $reservation)
            ->with('success', 'Reservation updated successfully.');
    }

    public function cancel(CancelReservationRequest $request, Reservation $reservation): RedirectResponse|JsonResponse
    {
        $this->authorize('cancel', $reservation);

        try {
            $this->bookings->cancel($reservation, $request->user(), $request->validated('cancellation_reason'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Reservation cancelled successfully.']);
        }

        return redirect()
            ->route($request->user()?->hasRole('slt_employee') ? 'reservations.mine' : 'reservations.index')
            ->with('success', 'Reservation cancelled successfully.');
    }

    public function calendar(Request $request): Response
    {
        $this->authorize('viewAny', Reservation::class);

        return $this->htmlPage('reservations.calendar', $this->calendarPayload($request) + $this->calendarFilterOptions($request->user()) + [
            'canCreate' => $request->user()?->can('create', Reservation::class),
            'eventsUrl' => route('reservations.calendar.events'),
            'availabilityUrl' => route('reservations.availability'),
            'createUrl' => route('reservations.create'),
        ]);
    }

    public function calendarStats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reservation::class);

        return $this->uncachedJson(collect($this->calendarPayload($request))->except('currentDate')->all());
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
        $this->restrictCalendarVisibility($baseQuery);
        $this->excludeHostelForSlt($baseQuery);
        $this->excludeInactiveForSlt($baseQuery);

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
            ->when($this->sltCalendarFiltersRestrictToOwn($request, $filters), function ($q) use ($request) {
                $user = $request->user();
                $q->where(function ($own) use ($user) {
                    $own->where('requester_id', $user->id)
                        ->orWhere('created_by_user_id', $user->id);
                });
            })
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

        $events = $query->orderBy('reservation_date')->orderBy('start_time')->get()
            ->flatMap(function (Reservation $reservation) use ($conflicts, $filters, $rangeStart, $rangeEnd) {
                $items = $reservation->items->filter(function ($item) use ($filters, $rangeStart, $rangeEnd) {
                    if (! $item->starts_at || ! $item->ends_at || ! $item->starts_at->lt($item->ends_at)) {
                        return false;
                    }
                    if (! $item->starts_at->lt($rangeEnd) || ! $item->ends_at->gt($rangeStart)) {
                        return false;
                    }
                    if (($filters['resource_id'] ?? null) && (int) $item->resource_id !== (int) $filters['resource_id']) {
                        return false;
                    }
                    if (($filters['resource_type_id'] ?? null) && (int) $item->resource?->resource_type_id !== (int) $filters['resource_type_id']) {
                        return false;
                    }
                    if (($filters['resource_category_id'] ?? null)
                        && (int) $item->resource?->type?->resource_category_id !== (int) $filters['resource_category_id']) {
                        return false;
                    }

                    return true;
                });

                if ($items->isEmpty()) {
                    return [];
                }

                return $items->flatMap(fn ($item) => $this->calendarItemSegments($reservation, $item, $conflicts, $rangeStart, $rangeEnd));
            });

        $holidays = collect(SriLankaHolidays::calendarEvents())->filter(function (array $event) use ($filters) {
            $date = substr((string) ($event['start'] ?? ''), 0, 10);

            return $date >= substr($filters['start'], 0, 10) && $date < substr($filters['end'], 0, 10);
        });

        return response()->json($events->values()->merge($holidays)->values());
    }

    public function lookups(): JsonResponse
    {
        $this->authorize('create', Reservation::class);

        return response()->json($this->bookingLookups());
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
            'ignore_reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
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

        $conflict = $this->bookings->hasConflict($data['resource_ids'], $startsAt, $endsAt, $data['ignore_reservation_id'] ?? null);

        return response()->json([
            'available' => ! $conflict,
            'message' => $conflict ? 'Selected slot unavailable' : 'This slot is available.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function calendarPayload(Request $request): array
    {
        $timezone = $this->bookings->timezone();
        $now = Carbon::now($timezone);
        $year = $request->filled('year') ? max(2000, min(2100, (int) $request->year)) : $now->year;
        $month = $request->filled('month') ? max(1, min(12, (int) $request->month)) : $now->month;
        $currentDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
        $prev = $currentDate->copy()->subMonth();
        $next = $currentDate->copy()->addMonth();

        $filters = array_filter([
            'status' => $request->input('status'),
            'location_id' => $request->input('location_id'),
            'resource_id' => $request->input('resource_id'),
            'resource_category_id' => $request->input('resource_category_id'),
        ], fn ($value) => $value !== null && $value !== '');

        $query = Reservation::query()
            ->with(['requester', 'location', 'items'])
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
            ->whereNotIn('status', [
                ReservationStatus::CANCELLED->value,
                ReservationStatus::REJECTED->value,
                ReservationStatus::DRAFT->value,
            ]);

        $this->restrictCalendarVisibility($query);
        $this->excludeHostelForSlt($query);
        if ($this->sltCalendarFiltersRestrictToOwn($request, $filters)) {
            $query->where(function ($own) use ($request) {
                $user = $request->user();
                $own->where('requester_id', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            });
        }
        $query->filter($filters);

        $isSlt = $request->user()?->hasRole('slt_employee');
        $calendarReservations = $query->orderBy('start_time')->get()->map(function (Reservation $reservation) use ($isSlt) {
            $mine = $this->ownsCalendarReservation($reservation);

            return [
                'id' => $reservation->id,
                'reference' => ($mine || ! $isSlt) ? $reservation->reference : null,
                'title' => $reservation->title,
                'purpose' => ($mine || ! $isSlt) ? $reservation->purpose : null,
                'status' => $reservation->status,
                'status_label' => $reservation->statusEnum()->label(),
                'reservation_date' => $reservation->reservation_date?->toDateString(),
                'start_time' => substr((string) $reservation->start_time, 0, 5),
                'end_time' => substr((string) $reservation->end_time, 0, 5),
                'requester' => ($mine || ! $isSlt) ? $reservation->requester?->name : null,
                'location' => $reservation->location?->name,
                'resources' => $reservation->resourceNames(),
                'mine' => $mine,
                'url' => $mine || ! $isSlt ? $this->calendarReservationUrl($reservation) : null,
            ];
        });

        $byDate = $calendarReservations->groupBy('reservation_date');
        $holidaysByDate = SriLankaHolidays::forMonth($year, $month);
        $cells = [];

        for ($i = 0; $i < $currentDate->dayOfWeek; $i++) {
            $cells[] = ['empty' => true];
        }

        for ($day = 1; $day <= $currentDate->daysInMonth; $day++) {
            $date = $currentDate->copy()->day($day);
            $iso = $date->toDateString();
            $dayItems = $byDate->get($iso, collect());

            $holiday = $holidaysByDate[$iso] ?? null;
            $previewLimit = $holiday ? 1 : 2;

            $cells[] = [
                'empty' => false,
                'day' => $day,
                'iso' => $iso,
                'display' => $date->format('l, F j, Y'),
                'isToday' => $date->isToday(),
                'isWeekend' => in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true),
                'count' => $dayItems->count(),
                'preview' => $dayItems->take($previewLimit)->values()->all(),
                'holiday' => $holiday,
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = ['empty' => true];
        }

        $todayKey = $now->toDateString();
        $countedReservations = $isSlt
            ? $calendarReservations->where('mine', true)
            : $calendarReservations;

        return [
            'currentDate' => $currentDate,
            'year' => $year,
            'month' => $month,
            'label' => $currentDate->format('F Y'),
            'prev' => ['year' => $prev->year, 'month' => $prev->month],
            'next' => ['year' => $next->year, 'month' => $next->month],
            'cells' => $cells,
            'calendarQuery' => $filters,
            'reservationsByDate' => $byDate->map(fn ($items) => $items->values())->all(),
            'holidaysByDate' => $holidaysByDate,
            'stats' => [
                'month' => $countedReservations->count(),
                'today' => $countedReservations->where('reservation_date', $todayKey)->count(),
                'pending' => $countedReservations->where('status', ReservationStatus::PENDING_APPROVAL->value)->count(),
                'approved' => $countedReservations->whereIn('status', [
                    ReservationStatus::APPROVED->value,
                    ReservationStatus::CONFIRMED->value,
                ])->count(),
            ],
        ];
    }

    private function indexPayload(Request $request): array
    {
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

        $reservations = $query->paginate(10)->appends($request->except('partial'))->setPath(route('reservations.index'));
        $user = $request->user();

        $base = Reservation::query();
        $this->restrictToOwnUnlessStaff($base);

        $current = $reservations->currentPage();
        $last = max(1, $reservations->lastPage());
        $start = max(1, $current - 2);
        $end = min($last, $start + 4);
        $start = max(1, $end - 4);

        $pages = [];
        for ($page = $start; $page <= $end; $page++) {
            $pages[] = [
                'page' => $page,
                'url' => $reservations->url($page),
                'active' => $page === $current,
            ];
        }

        return [
            'summary' => [
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
            ],
            'reservations' => $reservations->getCollection()
                ->map(fn (Reservation $reservation) => $this->reservationListItem($reservation, $user))
                ->values(),
            'meta' => [
                'from' => $reservations->firstItem(),
                'to' => $reservations->lastItem(),
                'total' => $reservations->total(),
                'current_page' => $current,
                'last_page' => $last,
                'has_filters' => $this->indexHasFilters($request),
            ],
            'links' => [
                'prev' => $reservations->previousPageUrl(),
                'next' => $reservations->nextPageUrl(),
                'pages' => $pages,
            ],
        ];
    }

    private function indexHasFilters(Request $request): bool
    {
        return collect(['search', 'from_date', 'to_date', 'location_id', 'resource_category_id', 'resource_id', 'status'])
            ->contains(fn (string $key) => filled($request->input($key)));
    }

    private function reservationListItem(Reservation $reservation, $user): array
    {
        $timezone = config('reservations.display_timezone', 'Asia/Colombo');

        return [
            'id' => $reservation->id,
            'reference' => $reservation->reference,
            'purpose' => $reservation->purpose,
            'title' => $reservation->title,
            'resource_names' => $reservation->resourceNames() ?: '—',
            'requester' => $reservation->requester?->name ?? 'Unknown',
            'created_by' => $reservation->createdBy?->name ?? 'Unknown',
            'location' => $reservation->location?->name ?? '—',
            'date' => $reservation->reservation_date?->format('d M Y') ?? '—',
            'time' => substr((string) $reservation->start_time, 0, 5).'–'.substr((string) $reservation->end_time, 0, 5),
            'attendee_count' => $reservation->attendee_count,
            'status' => $reservation->status,
            'status_label' => $reservation->statusEnum()->label(),
            'status_badge' => $reservation->statusEnum()->badgeClass(),
            'cancellation_reason' => $reservation->cancellation_reason,
            'can_view' => $user?->can('view', $reservation) ?? false,
            'can_cancel' => $user?->can('cancel', $reservation) ?? false,
            'cancel_url' => route('reservations.cancel', $reservation),
            'items' => $reservation->items->map(fn ($item) => [
                'name' => $item->resource_name_snapshot,
                'category' => $item->resource?->type?->category?->name ?? '—',
                'status' => $reservation->bookedItemStatusLabel($item),
                'status_badge' => $reservation->statusEnum()->badgeClass(),
            ])->values(),
            'history' => $reservation->statusHistory->map(fn ($history) => [
                'from' => $this->statusLabel($history->from_status),
                'to' => $this->statusLabel($history->to_status),
                'actor' => $history->actor?->name ?? 'System',
                'at' => $history->created_at?->timezone($timezone)->format('d M Y H:i'),
                'reason' => $history->reason,
            ])->values(),
        ];
    }

    private function statusLabel(?string $value): string
    {
        if (! filled($value)) {
            return '';
        }

        return ReservationStatus::tryFrom($value)?->label() ?? $value;
    }

    private function restrictToOwnUnlessStaff($query): void
    {
        $query->visibleTo(auth()->user());
    }

    private function restrictCalendarVisibility($query): void
    {
        if (auth()->user()?->hasRole('slt_employee')) {
            return;
        }

        $this->restrictToOwnUnlessStaff($query);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function sltCalendarFiltersRestrictToOwn(Request $request, array $filters): bool
    {
        if (! $request->user()?->hasRole('slt_employee')) {
            return false;
        }

        return collect(['location_id', 'resource_category_id', 'resource_type_id', 'resource_id', 'status'])
            ->contains(fn (string $key) => filled($filters[$key] ?? null));
    }

    /**
     * @return array{
     *     locations: \Illuminate\Support\Collection<int, Location>,
     *     categories: \Illuminate\Support\Collection<int, ResourceCategory>,
     *     resources: \Illuminate\Support\Collection<int, Resource>,
     *     statuses: \Illuminate\Support\Collection<int, ReservationStatus>,
     *     filterCatalog: array{categories: \Illuminate\Support\Collection, resources: \Illuminate\Support\Collection}
     * }
     */
    private function calendarFilterOptions(?User $user): array
    {
        $hiddenStatuses = [
            ReservationStatus::DRAFT,
            ReservationStatus::CANCELLED,
            ReservationStatus::REJECTED,
        ];

        if ($user?->hasRole('slt_employee')) {
            $own = Reservation::query()
                ->with(['location', 'items.resource.type.category'])
                ->where(function ($query) use ($user) {
                    $query->where('requester_id', $user->id)
                        ->orWhere('created_by_user_id', $user->id);
                })
                ->whereNotIn('type', [ReservationType::HOSTEL->value, ReservationType::CANTEEN->value])
                ->whereNotIn('status', [
                    ReservationStatus::DRAFT->value,
                    ReservationStatus::CANCELLED->value,
                    ReservationStatus::REJECTED->value,
                ])
                ->get();

            $locationIds = $own->pluck('location_id')->filter()->unique()->values();
            $resources = $own->flatMap(fn (Reservation $reservation) => $reservation->items
                ->map(fn ($item) => $item->resource)
                ->filter())
                ->unique('id')
                ->sortBy('name_model')
                ->values();
            $categoryIds = $resources->map(fn (Resource $resource) => $resource->type?->resource_category_id)
                ->filter()
                ->unique()
                ->values();
            $statusValues = $own->pluck('status')->unique()->values();

            $locations = $locationIds->isEmpty()
                ? collect()
                : Location::query()->ordered()->whereIn('id', $locationIds)->get();
            $categories = $categoryIds->isEmpty()
                ? collect()
                : ResourceCategory::query()->orderBy('name')->whereIn('id', $categoryIds)->get();
            $statuses = collect(ReservationStatus::cases())
                ->reject(fn (ReservationStatus $status) => in_array($status, $hiddenStatuses, true))
                ->filter(fn (ReservationStatus $status) => $statusValues->contains($status->value))
                ->values();
        } else {
            $categories = ResourceCategory::query()->orderBy('name')->get();
            $resources = Resource::query()
                ->with('type:id,resource_category_id')
                ->orderBy('name_model')
                ->get(['id', 'name_model', 'location_id', 'resource_type_id']);
            $locations = Location::query()->ordered()->get();
            $statuses = collect(ReservationStatus::cases())
                ->reject(fn (ReservationStatus $status) => in_array($status, $hiddenStatuses, true))
                ->values();
        }

        return [
            'locations' => $locations,
            'categories' => $categories,
            'resources' => $resources,
            'statuses' => $statuses,
            'filterCatalog' => [
                'categories' => $categories->map(fn (ResourceCategory $category) => [
                    'id' => (string) $category->id,
                    'name' => $category->name,
                ])->values(),
                'resources' => $resources->map(fn (Resource $resource) => [
                    'id' => (string) $resource->id,
                    'name' => $resource->name_model,
                    'location_id' => (string) $resource->location_id,
                    'category_id' => (string) ($resource->type?->resource_category_id ?? ''),
                ])->values(),
            ],
        ];
    }

    private function ownsCalendarReservation(Reservation $reservation): bool
    {
        $user = request()->user();

        return $user !== null
            && ((int) $reservation->requester_id === (int) $user->id
                || (int) $reservation->created_by_user_id === (int) $user->id);
    }

    private function excludeHostelForSlt($query): void
    {
        if (auth()->user()?->hasRole('slt_employee')) {
            $query->where('type', '!=', ReservationType::HOSTEL->value);
        }
    }

    private function excludeInactiveForSlt($query): void
    {
        if (auth()->user()?->hasRole('slt_employee')) {
            $query->whereIn('status', ReservationStatus::calendarActive());
        }
    }

    private function formLookups(Request $request): array
    {
        $today = now($this->bookings->timezone())->toDateString();

        return [
            'lookups' => $this->bookingLookups(),
            'holidays' => SriLankaHolidays::indexedByDate(),
            'today' => $today,
            'prefillDate' => old('reservation_date', $request->query('date', $today)),
            'prefillStart' => old('start_time', $request->query('start_time', '')),
            'prefillEnd' => old('end_time', $request->query('end_time', '')),
            'prefillResourceId' => old('resource_id', $request->query('resource_id')),
            'prefillResourceIds' => collect(old('resource_ids', $request->query('resource_ids', [])))
                ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values(),
            'prefillLocationId' => old('location_id', $request->query('location_id')),
            'prefillCategoryId' => old('category_id', $request->query('category_id')),
        ];
    }

    /**
     * @return array{locations: \Illuminate\Support\Collection, categories: \Illuminate\Support\Collection, resources: \Illuminate\Support\Collection}
     */
    private function bookingLookups(): array
    {
        $resources = Resource::query()
            ->with(['type.category', 'location', 'linkedResources'])
            ->where('status', 'active')
            ->orderBy('name_model')
            ->get()
            ->map(fn (Resource $resource) => [
                'id' => $resource->id,
                'name_model' => $resource->name_model,
                'serial_number' => $resource->serial_number,
                'status' => $resource->status,
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

        return [
            'locations' => Location::query()->ordered()->get(['id', 'name']),
            'categories' => ResourceCategory::query()->orderBy('name')->get(['id', 'name']),
            'resources' => $resources,
        ];
    }

    /**
     * @param  array<int, bool>  $conflicts
     * @return list<array<string, mixed>>
     */
    private function calendarItemSegments(Reservation $reservation, $item, array $conflicts, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $timezone = $this->bookings->timezone();
        $start = $item->starts_at->timezone($timezone);
        $end = $item->ends_at->timezone($timezone);
        $minutes = max(0, (int) $start->diffInMinutes($end));
        $overnight = $start->toDateString() !== $end->copy()->subSecond()->toDateString();
        $duration = trim(implode(' ', array_filter([
            intdiv($minutes, 60) ? intdiv($minutes, 60).' hr' : null,
            $minutes % 60 ? ($minutes % 60).' min' : null,
        ]))) ?: 'Less than a minute';
        if ($reservation->type === ReservationType::HOSTEL->value && $overnight) {
            $nights = max(1, (int) $start->copy()->startOfDay()->diffInDays($end->copy()->subSecond()->startOfDay()));
            $duration = $nights === 1 ? '1 night' : $nights.' nights';
        }
        $conflict = isset($conflicts[$reservation->id]);
        [$color, $textColor] = match ($reservation->status) {
            ReservationStatus::APPROVED->value, ReservationStatus::CONFIRMED->value, ReservationStatus::IN_PROGRESS->value => ['#16845b', '#fff'],
            ReservationStatus::CANCELLED->value, ReservationStatus::REJECTED->value, ReservationStatus::EXPIRED->value => ['#7b8493', '#fff'],
            ReservationStatus::COMPLETED->value => ['#4778d0', '#fff'],
            default => ['#d18b00', '#fff'],
        };
        $resourceName = $item->resource_name_snapshot ?: $item->resource?->name_model ?: '—';
        $mine = $this->ownsCalendarReservation($reservation);
        $isSlt = request()->user()?->hasRole('slt_employee');
        $publicTitle = $reservation->location?->name ?: $resourceName;
        $title = ($mine || ! $isSlt)
            ? $reservation->reference.' · '.($reservation->title ?: $reservation->purpose)
            : $publicTitle;
        $segments = [];
        $segmentStart = $start->copy();

        while ($segmentStart->lt($end)) {
            $midnight = $segmentStart->copy()->startOfDay()->addDay();
            $segmentEnd = $end->lt($midnight) ? $end->copy() : $midnight;

            if ($segmentStart->lt($rangeEnd) && $segmentEnd->gt($rangeStart)) {
                $visibleStart = $segmentStart->lt($rangeStart)
                    ? $rangeStart->copy()->timezone($timezone)
                    : $segmentStart->copy();
                $visibleEnd = $segmentEnd->gt($rangeEnd)
                    ? $rangeEnd->copy()->timezone($timezone)
                    : $segmentEnd->copy();

                $segments[] = [
                    'id' => $reservation->id.'-'.$item->id.'-'.$segmentStart->toDateString(),
                    'title' => $title,
                    'start' => $visibleStart->toIso8601String(),
                    'end' => $visibleEnd->toIso8601String(),
                    'backgroundColor' => $conflict ? '#c0392b' : $color,
                    'borderColor' => $conflict ? '#a93226' : $color,
                    'textColor' => $textColor,
                    'classNames' => array_values(array_filter([
                        $conflict ? 'reservation-conflict' : null,
                        $mine ? 'is-mine' : null,
                    ])),
                    'extendedProps' => [
                        'reservationId' => $reservation->id,
                        'reference' => ($mine || ! $isSlt) ? $reservation->reference : null,
                        'purpose' => ($mine || ! $isSlt) ? ($reservation->title ?: $reservation->purpose) : null,
                        'reservationStart' => $start->toIso8601String(),
                        'reservationEnd' => $end->toIso8601String(),
                        'resource' => $resourceName,
                        'owner' => ($mine || ! $isSlt) ? ($reservation->requester?->name ?? 'Unknown') : null,
                        'location' => $reservation->location?->name ?? '—',
                        'duration' => $duration,
                        'reservationType' => $reservation->type,
                        'status' => $reservation->statusEnum()->label(),
                        'statusKey' => $reservation->status,
                        'statusValue' => $reservation->status,
                        'conflict' => $conflict,
                        'mine' => $mine,
                        'url' => ($mine || ! $isSlt) ? $this->calendarReservationUrl($reservation) : null,
                    ],
                ];
            }

            $segmentStart = $segmentEnd;
        }

        return $segments;
    }

    private function calendarReservationUrl(Reservation $reservation): ?string
    {
        $user = request()->user();
        if ($reservation->type === ReservationType::HOSTEL->value && $user?->can('viewHostel', $reservation)) {
            return route('hostel.show', $reservation);
        }

        if ($user?->can('view', $reservation) && $user?->hasPermission('reservations.index')) {
            return route('reservations.show', $reservation);
        }

        return null;
    }
}
