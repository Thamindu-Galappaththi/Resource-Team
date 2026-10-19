@extends('layouts.app')

@section('title', 'Special Approvals')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-role.css') }}?v={{ @filemtime(public_path('css/dashboard-role.css')) ?: time() }}">
<style>
    .rs-special .special-request { padding: 1rem; background: #fff; border: 1px solid #b7e2d9; border-radius: 14px; }
    .rs-special .special-request + .special-request { margin-top: .75rem; }
    .rs-special .special-reason { background: #f0fdfa; border: 1px solid #b7e2d9; border-radius: 12px; padding: 1rem; }
    .rs-special .special-summary { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(180px, .8fr) auto; gap: .5rem 1rem; align-items: center; }
    .rs-special .special-summary__main { min-width: 0; }
    .rs-special .special-summary__title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rs-special .special-summary__meta { color: #718096; font-size: .82rem; }
    .rs-special .special-actions { display: flex; justify-content: flex-end; }
    .rs-special .special-meta { color: #718096; font-size: .82rem; }
    @media (max-width: 767.98px) {
        .rs-special .special-summary { grid-template-columns: minmax(0, 1fr) auto; }
        .rs-special .special-summary__date { grid-column: 1; }
        .rs-special .special-actions { grid-column: 2; grid-row: 1 / span 2; }
    }
</style>
@endpush

@section('content')
<div class="rs-dash rs-special">
    <header class="rs-dash-hero">
        <div class="rs-dash-hero__copy">
            <p class="rs-dash-kicker">Reservation management · High-level review</p>
            <h1>Special approvals</h1>
            <p class="rs-dash-lead">Review pending reservations and apply an elevated decision when needed. Super Admin overrides are recorded with a reason in the reservation history.</p>
        </div>
        <div class="rs-dash-actions"><a class="rs-dash-action is-primary" href="{{ route('approvals.index') }}"><i class="ti ti-arrow-left"></i><span>All approvals</span></a></div>
    </header>

    @if(session('success')) <div class="alert alert-success" role="status">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div> @endif

    <section class="rs-dash-panel" aria-labelledby="special-pending-heading">
        <div class="rs-dash-panel__head"><h2 id="special-pending-heading">Pending requests <span class="opacity-75">· {{ $reservations->total() }} total</span></h2><i class="ti ti-shield-star fs-5 text-white"></i></div>
        <div class="rs-dash-panel__body">
            @forelse($reservations as $reservation)
                <article class="special-request">
                    <div class="special-summary">
                        <span class="special-summary__main">
                            <span class="d-flex flex-wrap align-items-center gap-2"><strong>{{ $reservation->reference }}</strong><span class="badge bg-warning text-dark">Pending</span></span>
                            <span class="d-block fw-semibold special-summary__title mt-1">{{ $reservation->title ?: $reservation->purpose }}</span>
                            <span class="special-summary__meta">{{ $reservation->requester?->name ?? 'Unknown requester' }} · {{ $reservation->resourceNames() ?: 'No resource listed' }}</span>
                        </span>
                        <span class="special-summary__date small">{{ $reservation->reservation_date?->format('D, d M Y') }} · {{ substr((string)$reservation->start_time, 0, 5) }}–{{ substr((string)$reservation->end_time, 0, 5) }}</span>
                        <span class="special-actions"><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#special-view-{{ $reservation->id }}"><i class="ti ti-eye me-1"></i>View</button></span>
                    </div>

                    <div class="modal fade" id="special-view-{{ $reservation->id }}" tabindex="-1" aria-labelledby="special-view-title-{{ $reservation->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div><h2 class="modal-title fs-5" id="special-view-title-{{ $reservation->id }}">{{ $reservation->reference }}</h2><span class="badge bg-warning text-dark">Pending approval</span></div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <h3 class="h6">{{ $reservation->title ?: $reservation->purpose }}</h3>
                                    <div class="row g-3 small mb-3">
                                        <div class="col-md-6"><span class="text-muted">Requester</span><div>{{ $reservation->requester?->name ?? 'Unknown' }} · {{ $reservation->requester?->email }}</div></div>
                                        <div class="col-md-6"><span class="text-muted">Submitted</span><div>{{ $reservation->submitted_at?->timezone(config('reservations.display_timezone', 'Asia/Colombo'))->format('d M Y, H:i') ?? '—' }}</div></div>
                                        <div class="col-md-6"><span class="text-muted">Reservation date and time</span><div>{{ $reservation->reservation_date?->format('D, d M Y') }} · {{ substr((string)$reservation->start_time, 0, 5) }}–{{ substr((string)$reservation->end_time, 0, 5) }}</div></div>
                                        <div class="col-md-6"><span class="text-muted">Location and attendees</span><div>{{ $reservation->location?->name ?? '—' }} · {{ $reservation->attendee_count ?? '—' }} attendees</div></div>
                                        <div class="col-12"><span class="text-muted">Purpose</span><div>{{ $reservation->purpose ?: '—' }}</div></div>
                                        <div class="col-12"><span class="text-muted">Resources</span><div>@foreach($reservation->items as $item)<span class="badge bg-light text-dark border me-1">{{ $item->resource_name_snapshot }} · {{ $item->resource?->type?->category?->name ?? 'Uncategorized' }}</span>@endforeach</div></div>
                                        @if($reservation->description)<div class="col-12"><span class="text-muted">Additional details</span><div>{{ $reservation->description }}</div></div>@endif
                                    </div>
                                    <a href="{{ route('reservations.show', $reservation) }}" class="small text-decoration-none"><i class="ti ti-external-link me-1"></i>Open full reservation page</a>
                                    @if($canOverride)
                                        <form method="POST" action="{{ route('approvals.special-decision', $reservation) }}" class="special-reason mt-3">
                                            @csrf
                                            <label class="form-label small fw-semibold" for="special-comment-{{ $reservation->id }}">Override reason <span class="text-danger">*</span></label>
                                            <textarea class="form-control form-control-sm mb-2" id="special-comment-{{ $reservation->id }}" name="comment" rows="2" maxlength="2000" required placeholder="Explain the exception or high-level decision"></textarea>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button class="btn btn-success btn-sm" name="decision" value="approve"><i class="ti ti-check me-1"></i>Approve override</button>
                                                <button class="btn btn-outline-warning btn-sm" name="decision" value="request_changes"><i class="ti ti-edit me-1"></i>Request changes</button>
                                                <button class="btn btn-outline-danger btn-sm" name="decision" value="reject"><i class="ti ti-x me-1"></i>Reject</button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rs-dash-empty text-center"><i class="ti ti-circle-check text-success fs-3 d-block mb-2"></i><strong>No pending special reviews</strong><div>There are no pending reservations requiring an elevated decision.</div></div>
            @endforelse
            @if($reservations->hasPages())<div class="pt-2">{{ $reservations->links() }}</div>@endif
        </div>
    </section>

    <section class="rs-dash-panel mt-3" aria-labelledby="override-history-heading">
        <div class="rs-dash-panel__head"><h2 id="override-history-heading">Override history</h2><i class="ti ti-history fs-5 text-white"></i></div>
        <div class="rs-dash-panel__body">
            @forelse($overrideHistory as $entry)
                <article class="special-request">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <strong>{{ $entry->reservation?->reference ?? 'Reservation' }}</strong>
                            <span class="badge ms-1 {{ \App\Enums\ReservationStatus::tryFrom($entry->to_status)?->badgeClass() ?? 'bg-secondary' }}">{{ \App\Enums\ReservationStatus::tryFrom($entry->to_status)?->label() ?? $entry->to_status }}</span>
                            <div class="special-meta mt-1">{{ $entry->actor?->name ?? 'System' }} · {{ $entry->created_at?->timezone(config('reservations.display_timezone', 'Asia/Colombo'))->format('d M Y, H:i') }} · {{ $entry->reservation?->requester?->name ?? 'Unknown requester' }}</div>
                        </div>
                        @if($entry->reservation)<a class="btn btn-sm btn-outline-primary" href="{{ route('reservations.show', $entry->reservation) }}"><i class="ti ti-eye me-1"></i>View</a>@endif
                    </div>
                    <p class="small text-muted mt-2 mb-0">{{ $entry->reason ?: 'No reason was recorded.' }}</p>
                </article>
            @empty
                <div class="rs-dash-empty text-center">No special overrides have been recorded.</div>
            @endforelse
            @if($overrideHistory->hasPages())<div class="pt-2">{{ $overrideHistory->links() }}</div>@endif
        </div>
    </section>
</div>
@endsection
