<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Exceptions\ReservationConflictException;
use App\Http\Requests\CancelReservationRequest;
use App\Http\Requests\StoreHostelReservationRequest;
use App\Models\HostelStayDetail;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Resource;
use App\Services\HostelBedCapacity;
use App\Services\HostelReservationService;
use App\Services\ReservationDocumentStore;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Hostel list/create/show/cancel. Interns: copy this + HostelReservationService, not a new booking stack. */
class HostelReservationController extends Controller
{
    public function __construct(private readonly HostelReservationService $hostel) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAnyHostel', Reservation::class);

        $this->hostel->expireOverduePendingReservations();
        $this->hostel->completePastApprovedReservations();

        $query = Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->with(['requester', 'location', 'hostelStay', 'items.resource', 'statusHistory.actor'])
            ->latest('reservation_date');

        $this->restrictToOwnUnlessStaff($query);

        $query
            ->search($request->input('search'))
            ->filter([
                'status' => $request->input('status'),
                'check_in_from' => $request->input('check_in_from'),
                'check_in_to' => $request->input('check_in_to'),
            ]);

        $reservations = $query->paginate(10)->appends($request->query());

        $base = Reservation::query()->where('type', ReservationType::HOSTEL->value);
        $this->restrictToOwnUnlessStaff($base);

        $todayStart = Carbon::now($this->hostelStayTimezone())->startOfDay()->utc();
        $todayEnd = $todayStart->copy()->addDay();

        $checkInsToday = (clone $base)
            ->whereHas('hostelStay', function ($stay) use ($todayStart, $todayEnd) {
                $stay->where('check_in_at', '>=', $todayStart)->where('check_in_at', '<', $todayEnd);
            })
            ->count();

        $pending = (clone $base)->where('status', ReservationStatus::PENDING_APPROVAL->value)->count();

        $totalBeds = (int) Resource::query()->hostelRooms()->sum('bed_count');
        $bedsInUse = $this->bedsInUse($todayStart, $todayEnd);

        return view('hostel.index', [
            'reservations' => $reservations,
            'summary' => [
                'total' => (clone $base)->count(),
                'check_ins_today' => $checkInsToday,
                'available_beds' => max(0, $totalBeds - $bedsInUse),
                'total_beds' => $totalBeds,
                'pending' => $pending,
            ],
            'statuses' => [
                ReservationStatus::PENDING_APPROVAL,
                ReservationStatus::APPROVED,
                ReservationStatus::COMPLETED,
                ReservationStatus::REJECTED,
                ReservationStatus::CANCELLED,
                ReservationStatus::EXPIRED,
            ],
        ]);
    }

    /** GET /hostel/bed-map?date=YYYY-MM-DD: bus-style bed availability fragment for the dashboard. */
    public function bedMap(Request $request, HostelBedCapacity $beds): View
    {
        $this->authorize('viewAnyHostel', Reservation::class);
        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $day = Carbon::parse($data['date'] ?? now($this->hostelStayTimezone())->toDateString(), $this->hostelStayTimezone());

        return view('hostel.partials.bed-map', [
            'date' => $day->toDateString(),
            'locations' => $beds->bedMap($day),
        ]);
    }

    /** GET /hostel/gate-list?date=YYYY-MM-DD: approved guests staying that day, as a PDF for the gate security. */
    public function gateList(Request $request): Response
    {
        $this->authorize('viewAnyHostel', Reservation::class);
        abort_unless($request->user()->hasPermission('hostel.manage'), 403);

        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $tz = $this->hostelStayTimezone();
        $day = Carbon::parse($data['date'] ?? now($tz)->toDateString(), $tz)->startOfDay();
        $dayStart = $day->copy()->utc();
        $dayEnd = $day->copy()->addDay()->utc();

        $rows = Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->where('status', ReservationStatus::APPROVED->value)
            ->whereHas('hostelStay', fn ($stay) => $stay->where('check_in_at', '<', $dayEnd)->where('check_out_at', '>', $dayStart))
            ->with(['hostelStay', 'location', 'requester', 'items'])
            ->get()
            ->map(function (Reservation $reservation) use ($tz, $dayStart, $dayEnd) {
                $stay = $reservation->hostelStay;
                $checkIn = $stay->check_in_at->copy()->timezone($tz);
                $checkOut = $stay->check_out_at->copy()->timezone($tz);
                $phone = null;
                if ($stay->guest_phone_encrypted) {
                    try {
                        $phone = Crypt::decryptString($stay->guest_phone_encrypted);
                    } catch (\Throwable) {
                        $phone = null;
                    }
                }

                return [
                    'reference' => $reservation->reference,
                    'guest' => $stay->displayGuestName(),
                    'employee_ids' => $stay->employeeIdList(),
                    'phone' => $phone,
                    'guests' => (int) $stay->number_of_guests,
                    'beds' => (int) $reservation->items->sum('quantity'),
                    'location' => $reservation->location?->name ?? '—',
                    'bed_group' => $stay->bedGroupLabel() ?? '—',
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'today' => match (true) {
                        $stay->check_in_at->gte($dayStart) && $stay->check_out_at->lte($dayEnd) => 'Arriving & leaving',
                        $stay->check_in_at->gte($dayStart) => 'Arriving',
                        $stay->check_out_at->lte($dayEnd) => 'Leaving',
                        default => 'In stay',
                    },
                    'requester' => $reservation->requester?->name,
                    'requirements' => $stay->special_requirements,
                ];
            })
            ->sortBy([
                fn ($a, $b) => array_search($a['location'], Location::CAMPUS_ORDER, true) <=> array_search($b['location'], Location::CAMPUS_ORDER, true),
                fn ($a, $b) => $a['check_in'] <=> $b['check_in'],
            ])
            ->values();

        $logo = public_path('images/logos/nebula.png');
        $pdf = Pdf::loadView('hostel.pdf.gate-list', [
            'day' => $day,
            'rows' => $rows,
            'groups' => $rows->groupBy('location'),
            'generatedAt' => now($tz),
            'generatedBy' => $request->user()->name,
            'logo' => is_file($logo) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logo)) : null,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('hostel-guest-list-'.$day->toDateString().'.pdf');
    }

    public function create(Request $request): View
    {
        $this->authorize('createHostel', Reservation::class);

        $prefill = $request->query('date');
        $minCheckIn = now()->toDateString();
        if (is_string($prefill) && $prefill < $minCheckIn) {
            $prefill = $minCheckIn;
        }

        $locations = $this->hostelLocations();

        return view('hostel.create', [
            'locations' => $locations,
            'hasRooms' => $locations->isNotEmpty(),
            'guestTitles' => HostelStayDetail::GUEST_TITLES,
            'bedGroups' => config('hostel.bed_groups', []),
            'maxGuests' => StoreHostelReservationRequest::MAX_GUESTS,
            'minCheckIn' => $minCheckIn,
            'prefillCheckIn' => old('check_in_date', $prefill),
            'hostelCapacities' => $this->hostelCapacities(),
        ]);
    }

    public function store(StoreHostelReservationRequest $request, ReservationDocumentStore $documents): RedirectResponse
    {
        $this->authorize('createHostel', Reservation::class);

        $user = $request->user();
        $payload = $request->validated();

        $recentDuplicate = Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->where(function ($q) use ($user) {
                $q->where('created_by_user_id', $user->id)
                    ->orWhere('requester_id', $user->id);
            })
            ->where('status', '!=', ReservationStatus::CANCELLED->value)
            ->whereDoesntHave('items', function ($items) {
                $items->where('status', 'cancelled');
            })
            ->where('created_at', '>=', now()->subSeconds(10))
            ->where('location_id', $payload['location_id'])
            ->whereHas('hostelStay', function ($stay) use ($payload) {
                $stay->where('guest_name', $payload['guest_name'])
                    ->where('bed_group', $payload['bed_group'])
                    ->where('number_of_guests', (int) $payload['number_of_guests']);
            })
            ->first();

        if ($recentDuplicate) {
            return redirect()
                ->route('hostel.show', $recentDuplicate)
                ->with('info', 'Your reservation was already created.');
        }

        try {
            $reservation = $this->hostel->create($payload, $user);
        } catch (ReservationConflictException $exception) {
            return back()->withInput()->withErrors([
                'number_of_guests' => $exception->getMessage(),
                'bed_group' => $exception->getMessage(),
            ]);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        $documents->attach($reservation, $request->file('special_documents'), $request->user());

        return redirect()
            ->route('hostel.show', $reservation)
            ->with('success', $reservation->special_approval
                ? 'Hostel reservation submitted successfully and sent for special approval.'
                : 'Hostel reservation submitted successfully and sent for approval.');
    }

    public function show(Reservation $reservation): View
    {
        abort_unless($reservation->type === ReservationType::HOSTEL->value, 404);
        $this->authorize('viewHostel', $reservation);

        if ($this->hostel->expirePendingAtCheckIn($reservation->id)) {
            $reservation->refresh();
        }
        if ($this->hostel->completeApprovedAtCheckout($reservation->id)) {
            $reservation->refresh();
        }

        $reservation->load([
            'requester',
            'createdBy',
            'location',
            'items.resource.type.category',
            'hostelStay',
            'statusHistory.actor',
            'documents',
        ]);

        return view('hostel.show', compact('reservation'));
    }

    public function edit(Reservation $reservation): View
    {
        abort_unless($reservation->type === ReservationType::HOSTEL->value, 404);
        $this->authorize('update', $reservation);
        abort_unless($reservation->canBeEdited(), 403);

        $reservation->load(['hostelStay', 'location', 'documents']);
        $stay = $reservation->hostelStay;
        $tz = $this->hostelStayTimezone();
        $checkIn = $stay?->check_in_at?->copy()->timezone($tz);
        $checkOut = $stay?->check_out_at?->copy()->timezone($tz);
        $locations = $this->hostelLocations();

        return view('hostel.create', [
            'reservation' => $reservation,
            'locations' => $locations,
            'hasRooms' => $locations->isNotEmpty(),
            'guestPhone' => $stay?->guest_phone_encrypted ? Crypt::decryptString($stay->guest_phone_encrypted) : null,
            'guestTitles' => HostelStayDetail::GUEST_TITLES,
            'bedGroups' => config('hostel.bed_groups', []),
            'maxGuests' => StoreHostelReservationRequest::MAX_GUESTS,
            'minCheckIn' => now()->toDateString(),
            'prefillCheckIn' => old('check_in_date', $checkIn?->toDateString()),
            'prefillCheckInTime' => old('check_in_time', $checkIn?->format('H:i')),
            'prefillCheckOut' => old('check_out_date', $checkOut?->toDateString()),
            'prefillCheckOutTime' => old('check_out_time', $checkOut?->format('H:i')),
            'hostelCapacities' => $this->hostelCapacities(),
        ]);
    }

    public function update(StoreHostelReservationRequest $request, Reservation $reservation, ReservationDocumentStore $documents): RedirectResponse
    {
        abort_unless($reservation->type === ReservationType::HOSTEL->value, 404);
        $this->authorize('update', $reservation);
        abort_unless($reservation->canBeEdited(), 403);

        try {
            $reservation = $this->hostel->updatePending($reservation, $request->validated(), $request->user());
        } catch (ReservationConflictException $exception) {
            return back()->withInput()->withErrors([
                'number_of_guests' => $exception->getMessage(),
                'bed_group' => $exception->getMessage(),
            ]);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        $documents->attach($reservation, $request->file('special_documents'), $request->user());

        return redirect()
            ->route('hostel.show', $reservation)
            ->with('success', 'Hostel reservation updated successfully.');
    }

    public function cancel(CancelReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->type === ReservationType::HOSTEL->value, 404);
        $this->authorize('cancelHostel', $reservation);

        try {
            $this->hostel->cancel($reservation, $request->user(), $request->validated('cancellation_reason'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route($request->user()?->hasRole('slt_employee') ? 'hostel.mine' : 'hostel.index')
            ->with('success', 'Hostel reservation cancelled successfully.');
    }

    public function updateApproval(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->type === ReservationType::HOSTEL->value, 404);
        if ($reservation->special_approval) {
            return back()->withErrors(['status' => 'This stay was sent for special approval, so it is decided in Special approvals.']);
        }
        $this->authorize('manageHostel', $reservation);

        $data = $request->validate([
            'status' => ['required', 'in:'.ReservationStatus::APPROVED->value.','.ReservationStatus::REJECTED->value],
            'reason' => ['nullable', 'required_if:status,'.ReservationStatus::REJECTED->value, 'string', 'max:1000'],
        ]);

        try {
            $updatedReservation = $this->hostel->updateApproval($reservation, $request->user(), $data['status'], $data['reason'] ?? null);
        } catch (ReservationConflictException $exception) {
            return back()->withInput()->withErrors(['status' => 'This room is no longer available for the selected dates.']);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        if ($updatedReservation->status === ReservationStatus::EXPIRED->value) {
            return back()->with('success', 'Reservation expired at check-in; the held room was released and was not approved.');
        }

        return back()->with('success', $data['status'] === ReservationStatus::APPROVED->value
            ? 'Hostel reservation approved.'
            : 'Hostel reservation rejected.');
    }

    private function hostelLocations()
    {
        return Location::query()
            ->forResources()
            ->whereIn('id', Resource::query()->hostelRooms()->where('bed_count', '>', 0)->select('location_id'))
            ->ordered()
            ->get();
    }

    private function hostelCapacities(): array
    {
        return Resource::query()
            ->hostelRooms()
            ->get(['id', 'location_id', 'bed_counts'])
            ->mapWithKeys(fn (Resource $h) => [(string) $h->location_id => $h->bed_counts ?? []])
            ->all();
    }

    private function restrictToOwnUnlessStaff($query): void
    {
        $user = auth()->user();
        if ($user?->hasRole('developer', 'super_admin', 'admin', 'coordinator', 'hostel_manager')
            || $user?->hasPermission('hostel.manage')) {
            return;
        }

        $query->where(function ($inner) use ($user) {
            $inner->where('requester_id', $user?->id)
                ->orWhere('created_by_user_id', $user?->id);
        });
    }

    private function bedsInUse($todayStart, $todayEnd): int
    {
        return (int) round((float) \App\Models\ReservationItem::query()
            ->whereIn('status', ReservationStatus::blockingItemStatuses())
            ->where('starts_at', '<', $todayEnd)
            ->where('ends_at', '>', $todayStart)
            ->whereHas('resource', fn ($resource) => $resource->hostelRooms())
            ->sum('quantity'));
    }

    private function hostelStayTimezone(): string
    {
        return config('reservations.display_timezone', 'Asia/Colombo');
    }
}
