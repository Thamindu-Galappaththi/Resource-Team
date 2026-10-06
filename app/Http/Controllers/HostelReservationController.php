<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Exceptions\ReservationConflictException;
use App\Http\Requests\CancelReservationRequest;
use App\Http\Requests\StoreHostelReservationRequest;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Services\HostelReservationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['requester', 'location', 'hostelStay.roomType', 'items.resource', 'statusHistory.actor'])
            ->latest('reservation_date');

        $this->restrictToOwnUnlessStaff($query);

        $query
            ->search($request->input('search'))
            ->filter([
                'status' => $request->input('status'),
                'room_type_id' => $request->input('room_type_id'),
                'check_in_from' => $request->input('check_in_from'),
                'check_in_to' => $request->input('check_in_to'),
            ]);

        $reservations = $query->paginate(15)->appends($request->query());

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

        $hostelRooms = Resource::query()->hostelRooms();
        $occupiedIds = $this->occupiedRoomIds($todayStart, $todayEnd);
        $availableRooms = $occupiedIds === []
            ? (clone $hostelRooms)->count()
            : (clone $hostelRooms)->whereNotIn('id', $occupiedIds)->count();

        return view('hostel.index', [
            'reservations' => $reservations,
            'summary' => [
                'total' => (clone $base)->count(),
                'check_ins_today' => $checkInsToday,
                'available_rooms' => $availableRooms,
                'pending' => $pending,
            ],
            'roomTypes' => $this->roomTypes(),
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

    public function create(): View
    {
        $this->authorize('createHostel', Reservation::class);

        return view('hostel.create', [
            'locations' => Location::query()->ordered()->get(),
            'roomTypes' => $this->roomTypes(),
            'minCheckIn' => now()->toDateString(),
        ]);
    }

    public function store(StoreHostelReservationRequest $request): RedirectResponse
    {
        $this->authorize('createHostel', Reservation::class);

        try {
            $reservation = $this->hostel->create($request->validated(), $request->user());
        } catch (ReservationConflictException $exception) {
            return back()->withInput()->withErrors([
                'check_in_date' => $exception->getMessage(),
            ]);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return redirect()
            ->route('hostel.show', $reservation)
            ->with('success', 'Hostel reservation submitted successfully and sent for approval.');
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
            'items.resource',
            'hostelStay.roomType',
            'statusHistory.actor',
        ]);

        return view('hostel.show', compact('reservation'));
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
            ->route('hostel.index')
            ->with('success', 'Hostel reservation cancelled successfully.');
    }

    public function updateApproval(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->type === ReservationType::HOSTEL->value, 404);
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

    private function roomTypes()
    {
        return ResourceType::query()
            ->whereHas('category', fn ($query) => $query->where('name', config('hostel.category_name', 'Hostel Room')))
            ->orderBy('name')
            ->get();
    }

    private function restrictToOwnUnlessStaff($query): void
    {
        $user = auth()->user();
        if ($user?->hasRole('super_admin', 'admin', 'coordinator', 'hostel_manager')
            || $user?->hasPermission('hostel.manage')) {
            return;
        }

        $query->where(function ($inner) use ($user) {
            $inner->where('requester_id', $user?->id)
                ->orWhere('created_by_user_id', $user?->id);
        });
    }

    private function occupiedRoomIds($todayStart, $todayEnd)
    {
        return \App\Models\ReservationItem::query()
            ->whereIn('status', ReservationStatus::blockingItemStatuses())
            ->where('starts_at', '<', $todayEnd)
            ->where('ends_at', '>', $todayStart)
            ->whereHas('resource', fn ($resource) => $resource->hostelRooms())
            ->pluck('resource_id')
            ->all();
    }

    private function hostelStayTimezone(): string
    {
        return config('reservations.display_timezone', 'Asia/Colombo');
    }
}
