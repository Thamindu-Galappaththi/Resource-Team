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
            ->paginate(8, ['*'], 'history_page')
            ->withQueryString();

        return view('approvals.index', [
            'pending' => (clone $base)->where('status', ReservationStatus::PENDING_APPROVAL->value)
                ->with(['requester', 'items.resource.type.category', 'items.resource.location', 'statusHistory.actor'])
                ->orderBy('reservation_date')->orderBy('start_time')->get(),
            'history' => $history,
            'pendingCount' => $pendingCount,
            'historyCount' => (clone $base)->whereIn('status', [ReservationStatus::APPROVED->value, ReservationStatus::REJECTED->value, ReservationStatus::CHANGES_REQUESTED->value])->count(),
            'categories' => ResourceCategory::query()->orderBy('name')->get(),
            'locations' => Location::query()->ordered()->get(),
            'canDecide' => $user->hasRole('developer', 'super_admin', 'admin', 'resource_owner'),
        ]);
    }

    public function decide(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeApprover($request->user());
        abort_unless($reservation->status === ReservationStatus::PENDING_APPROVAL->value, 409, 'This reservation has already been reviewed.');
        abort_unless($this->visibleQuery($request->user())->whereKey($reservation->id)->exists(), 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'request_changes'])],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $status = match ($data['decision']) {
            'approve' => ReservationStatus::APPROVED,
            'reject' => ReservationStatus::REJECTED,
            default => ReservationStatus::CHANGES_REQUESTED,
        };

        DB::transaction(function () use ($reservation, $request, $data, $status) {
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
                'created_at' => now(),
            ]);
            $locked->load('requester');
            $locked->requester?->notify(new ReservationDecision($locked, $request->user(), $data['comment']));
        });

        return redirect()->route('approvals.index')->with('success', 'Reservation '.$reservation->reference.' '.$status->label().'.');
    }

    private function visibleQuery(User $user)
    {
        $query = Reservation::query();
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
