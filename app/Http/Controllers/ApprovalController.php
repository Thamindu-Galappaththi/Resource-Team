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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct(private readonly ReservationBookingService $bookings) {}

    public function index(Request $request): Response
    {
        return $this->htmlPage('approvals.index', $this->indexViewData($request));
    }

    public function feed(Request $request): JsonResponse
    {
        return $this->uncachedJson($this->indexPayload($request));
    }

    private function indexViewData(Request $request): array
    {
        $payload = $this->indexPayload($request);

        return [
            'payload' => $payload,
            'categories' => ResourceCategory::query()->orderBy('name')->get(),
            'locations' => Location::query()->ordered()->get(),
            'filters' => $payload['filters'],
            'hasFilters' => $payload['meta']['has_filters'],
            'canDecide' => $payload['can_decide'],
        ];
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

    private function indexPayload(Request $request): array
    {
        $user = $request->user();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in([
                ReservationStatus::APPROVED->value,
                ReservationStatus::REJECTED->value,
                ReservationStatus::CHANGES_REQUESTED->value,
            ])],
            'category_id' => ['nullable', 'integer', 'exists:resource_categories,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
        $base = $this->visibleQuery($user);
        $relations = ['requester', 'location', 'items.resource.type.category', 'statusHistory.actor'];

        $pending = $this->applyListFilters(
            (clone $base)->where('status', ReservationStatus::PENDING_APPROVAL->value),
            $filters,
            withStatus: false,
        )
            ->with($relations)
            ->orderBy('reservation_date')
            ->orderBy('start_time')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString()
            ->setPath(route('approvals.index'));

        $history = $this->applyListFilters(
            (clone $base)->whereIn('status', [
                ReservationStatus::APPROVED->value,
                ReservationStatus::REJECTED->value,
                ReservationStatus::CHANGES_REQUESTED->value,
            ]),
            $filters,
            withStatus: true,
        )
            ->with($relations)
            ->latest('updated_at')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString()
            ->setPath(route('approvals.index'));

        return [
            'pending' => $pending->getCollection()->map(fn (Reservation $reservation) => $this->present($reservation))->values(),
            'history' => $history->getCollection()->map(fn (Reservation $reservation) => $this->present($reservation))->values(),
            'summary' => [
                'pending' => $pending->total(),
                'history' => $history->total(),
            ],
            'pending_pager' => $this->pagerPayload($pending),
            'history_pager' => $this->pagerPayload($history),
            'filters' => $filters,
            'meta' => [
                'has_filters' => collect($filters)->contains(fn ($value) => filled($value)),
            ],
            'can_decide' => $user->hasRole('developer', 'super_admin', 'admin', 'resource_owner'),
        ];
    }

    private function present(Reservation $reservation): array
    {
        $status = $reservation->statusEnum();
        $latest = $reservation->statusHistory->sortByDesc('created_at')->first();

        return [
            'id' => $reservation->id,
            'reference' => $reservation->reference,
            'title' => $reservation->title ?: $reservation->purpose,
            'purpose' => $reservation->purpose,
            'description' => $reservation->description,
            'requester' => $reservation->requester?->name ?? 'Unknown',
            'requester_email' => $reservation->requester?->email,
            'resources' => $reservation->resourceNames() ?: 'Resource not listed',
            'resource_badges' => $reservation->items->map(fn ($item) => ($item->resource_name_snapshot ?: $item->resource?->name_model).' · '.($item->resource?->type?->category?->name ?? 'Uncategorized'))->values(),
            'date' => $reservation->reservation_date?->format('D, d M Y'),
            'short_date' => $reservation->reservation_date?->format('d M Y'),
            'time' => substr((string) $reservation->start_time, 0, 5).'–'.substr((string) $reservation->end_time, 0, 5),
            'location' => $reservation->location?->name ?? '—',
            'attendees' => $reservation->attendee_count,
            'status' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),
            'submitted' => $reservation->submitted_at?->timezone(config('reservations.display_timezone', 'Asia/Colombo'))->format('d M Y, H:i') ?? '—',
            'comment' => $latest?->reason,
            'url' => route('reservations.show', $reservation),
            'decision_url' => route('approvals.decision', $reservation),
            'archive_url' => route('approvals.archive', $reservation),
            'pending' => $reservation->status === ReservationStatus::PENDING_APPROVAL->value,
        ];
    }

    private function pagerPayload($paginator): array
    {
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect([1, $last, $current - 1, $current, $current + 1])
            ->filter(fn ($page) => $page >= 1 && $page <= $last)
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($page) => [
                'page' => $page,
                'url' => $paginator->url($page),
                'active' => $page === $current,
            ]);

        return [
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'last' => $last,
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
            'pages' => $pages->all(),
        ];
    }

    private function applyListFilters($query, array $filters, bool $withStatus)
    {
        return $query
            ->when(filled($filters['search'] ?? null), fn ($q) => $q->search($filters['search']))
            ->when($withStatus && filled($filters['status'] ?? null), fn ($q) => $q->where('status', $filters['status']))
            ->filter([
                'location_id' => $filters['location_id'] ?? null,
                'resource_category_id' => $filters['category_id'] ?? null,
                'from_date' => $filters['from_date'] ?? null,
                'to_date' => $filters['to_date'] ?? null,
            ]);
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
