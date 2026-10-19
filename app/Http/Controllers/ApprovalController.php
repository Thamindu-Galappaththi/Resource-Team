<?php

namespace App\Http\Controllers;

use App\Enums\ReservationItemStatus;
use App\Enums\ReservationStatus;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\ResourceCategory;
use App\Models\User;
use App\Notifications\ReservationDecision;
use App\Services\ReservationBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct(private readonly ReservationBookingService $bookings) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $base = $this->visibleQuery($user);
        $pendingCount = (clone $base)->where('status', ReservationStatus::PENDING_APPROVAL->value)->count();

        $history = (clone $base)
            ->whereIn('status', [ReservationStatus::APPROVED->value, ReservationStatus::REJECTED->value, ReservationStatus::CHANGES_REQUESTED->value])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->search($request->string('search')))
            ->when($request->filled('category_id'), fn ($q) => $q->filter(['resource_category_id' => $request->input('category_id')]))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('reservation_date', '>=', $request->input('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('reservation_date', '<=', $request->input('to_date')))
            ->with(['requester', 'items.resource.type.category', 'statusHistory.actor'])
            ->latest('updated_at')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        $pending = (clone $base)
            ->where('status', ReservationStatus::PENDING_APPROVAL->value)
            ->with(['requester', 'items.resource.type.category', 'items.resource.location', 'statusHistory.actor'])
            ->orderBy('reservation_date')->orderBy('start_time')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        return view('approvals.index', [
            'pending' => $pending,
            'history' => $history,
            'pendingCount' => $pendingCount,
            'historyCount' => (clone $base)->whereIn('status', [ReservationStatus::APPROVED->value, ReservationStatus::REJECTED->value, ReservationStatus::CHANGES_REQUESTED->value])->count(),
            'categories' => ResourceCategory::query()->orderBy('name')->get(),
            'locations' => Location::query()->ordered()->get(),
            'canDecide' => $user->hasRole('developer', 'super_admin', 'admin', 'resource_owner'),
        ]);
    }

    public function special(Request $request): View
    {
        $reservations = Reservation::query()
            ->whereNull('approval_archived_at')
            ->where('status', ReservationStatus::PENDING_APPROVAL->value)
            ->with(['requester', 'location', 'items.resource.type.category', 'statusHistory.actor'])
            ->orderBy('submitted_at')
            ->paginate(10)
            ->withQueryString();

        return view('approvals.special', [
            'reservations' => $reservations,
            'overrideHistory' => ReservationStatusHistory::query()
                ->whereJsonContains('context->action', 'special_override')
                ->with(['reservation.requester', 'actor'])
                ->latest('created_at')
                ->paginate(10, ['*'], 'override_history_page')
                ->withQueryString(),
            'canOverride' => $request->user()->hasRole('developer', 'super_admin'),
        ]);
    }

    public function specialDecision(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($request->user()->hasRole('developer', 'super_admin'), 403);
        return $this->applyDecision($request, $reservation, true);
    }

    public function decide(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeApprover($request->user());
        return $this->applyDecision($request, $reservation, false);
    }

    public function archive(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeApprover($request->user());
        abort_unless($this->visibleQuery($request->user())->whereKey($reservation->id)->exists(), 403);

        $request->validate(['archive_reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $reservation) {
            $locked = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            abort_unless(is_null($locked->approval_archived_at), 409, 'This record has already been removed from Approvals.');
            $fromStatus = $locked->status;
            if ($fromStatus === ReservationStatus::PENDING_APPROVAL->value) {
                $locked->update([
                    'status' => ReservationStatus::CANCELLED->value,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $request->input('archive_reason'),
                    'approval_archived_at' => now(),
                ]);
                $this->bookings->release($locked);
            } else {
                $locked->update(['approval_archived_at' => now()]);
            }
            ReservationStatusHistory::query()->create([
                'reservation_id' => $locked->id,
                'from_status' => $fromStatus,
                'to_status' => $fromStatus === ReservationStatus::PENDING_APPROVAL->value ? ReservationStatus::CANCELLED->value : $fromStatus,
                'actor_id' => $request->user()->id,
                'reason' => $request->input('archive_reason'),
                'context' => ['action' => 'approval_archive'],
                'created_at' => now(),
            ]);
            if ($fromStatus === ReservationStatus::PENDING_APPROVAL->value) {
                $locked->load('requester');
                $locked->requester?->notify(new ReservationDecision($locked, $request->user(), $request->input('archive_reason')));
            }
        });

        return back()->with('success', 'Reservation removed from Approvals. Its reservation and history remain available in Existing Reservations.');
    }

    private function applyDecision(Request $request, Reservation $reservation, bool $specialOverride): RedirectResponse
    {
        abort_unless(is_null($reservation->approval_archived_at), 404);
        abort_unless($reservation->status === ReservationStatus::PENDING_APPROVAL->value, 409, 'This reservation has already been reviewed.');
        if (! $specialOverride) {
            abort_unless($this->visibleQuery($request->user())->whereKey($reservation->id)->exists(), 403);
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'request_changes'])],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $status = match ($data['decision']) {
            'approve' => ReservationStatus::APPROVED,
            'reject' => ReservationStatus::REJECTED,
            default => ReservationStatus::CHANGES_REQUESTED,
        };

        DB::transaction(function () use ($reservation, $request, $data, $status, $specialOverride) {
            $locked = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === ReservationStatus::PENDING_APPROVAL->value, 409, 'This reservation has already been reviewed.');
            $locked->update(['status' => $status->value, 'approved_at' => $status === ReservationStatus::APPROVED ? now() : null]);
            if ($status === ReservationStatus::APPROVED) {
                $this->bookings->confirm($locked);
            } else {
                $this->bookings->release($locked);
            }
            ReservationStatusHistory::query()->create([
                'reservation_id' => $locked->id,
                'from_status' => ReservationStatus::PENDING_APPROVAL->value,
                'to_status' => $status->value,
                'actor_id' => $request->user()->id,
                'reason' => $data['comment'],
                'context' => $specialOverride ? ['action' => 'special_override'] : null,
                'created_at' => now(),
            ]);
            $locked->load('requester');
            $locked->requester?->notify(new ReservationDecision($locked, $request->user(), $data['comment']));
        });

        return redirect()->route($specialOverride ? 'approvals.special' : 'approvals.index')->with('success', 'Reservation '.$reservation->reference.' '.$status->label().'.');
    }

    private function visibleQuery(User $user)
    {
        $query = Reservation::query()->whereNull('approval_archived_at');
        if ($user->hasRole('resource_owner') && ! $user->hasRole('developer', 'super_admin', 'admin')) {
            $query->whereHas('items.resource', fn ($resource) => $resource->whereIn('resource_owner', array_filter([$user->name, $user->email, $user->service_id])));
        }
        return $query;
    }

    private function authorizeApprover(User $user): void
    {
        abort_unless($user->hasRole('developer', 'super_admin', 'admin', 'resource_owner'), 403);
    }
}
