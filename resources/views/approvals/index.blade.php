@extends('layouts.app')

@section('title', 'Approvals')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-role.css') }}?v={{ @filemtime(public_path('css/dashboard-role.css')) ?: time() }}">
<style>
    .rs-approvals .rs-dash-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rs-approvals .approval-card { padding: 1rem; background: #fff; border: 1px solid #b7e2d9; border-radius: 14px; }
    .rs-approvals .approval-card + .approval-card { margin-top: .75rem; }
    .rs-approvals .approval-disclosure > summary { list-style: none; cursor: pointer; }
    .rs-approvals .approval-disclosure > summary::-webkit-details-marker { display: none; }
    .rs-approvals .approval-disclosure > summary:focus-visible { outline: 3px solid rgba(23, 105, 194, .35); outline-offset: 4px; border-radius: 8px; }
    .rs-approvals .approval-summary { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(150px, .8fr) auto; align-items: center; gap: .5rem 1rem; }
    .rs-approvals .approval-summary__main { min-width: 0; }
    .rs-approvals .approval-summary__title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rs-approvals .approval-summary__meta { color: #718096; font-size: .8rem; }
    .rs-approvals .approval-summary__toggle { color: #1769c2; font-size: .82rem; font-weight: 700; white-space: nowrap; }
    .rs-approvals .approval-summary__toggle .is-open { display: none; }
    .rs-approvals details[open] .approval-summary__toggle .is-open { display: inline; }
    .rs-approvals details[open] .approval-summary__toggle .is-closed { display: none; }
    .rs-approvals .approval-expanded { border-top: 1px solid #e1eee9; margin-top: 1rem; padding-top: 1rem; }
    .rs-approvals .approval-action { background: #f0fdfa; border: 1px solid #b7e2d9; border-radius: 12px; padding: 1rem; }
    .rs-approvals .approval-filters { background: #fff; border: 1px solid #b7e2d9; border-radius: 12px; padding: 1rem; }
    .rs-approvals .approval-history { background: #fff; border: 1px solid #b7e2d9; border-radius: 12px; padding: 1rem; }
    .rs-approvals .approval-history + .approval-history { margin-top: .75rem; }
    .rs-approvals .rs-dash-panel__body { gap: .75rem; }
    .rs-approvals .approval-comment { border-left: 3px solid #0d9488; padding-left: .75rem; }
    @media (max-width: 575.98px) { .rs-approvals .rs-dash-kpis { grid-template-columns: 1fr; } }
    @media (max-width: 767.98px) {
        .rs-approvals .approval-summary { grid-template-columns: minmax(0, 1fr) auto; }
        .rs-approvals .approval-summary__date { grid-column: 1; }
        .rs-approvals .approval-summary__toggle { grid-column: 2; grid-row: 1 / span 2; }
    }
</style>
@endpush

@section('content')
<div class="rs-dash rs-approvals">
    <header class="rs-dash-hero">
        <div class="rs-dash-hero__copy">
            <p class="rs-dash-kicker">Reservation management · {{ now()->format('d M Y') }}</p>
            <h1>Approvals</h1>
            <p class="rs-dash-lead">Review reservation requests, record decisions, and follow the approval history.</p>
        </div>
        @if(auth()->user()->hasPermission('approvals.special'))
            <div class="rs-dash-actions"><a class="rs-dash-action is-primary" href="{{ route('approvals.special') }}"><i class="ti ti-shield-star"></i><span>Special approvals</span></a></div>
        @endif
    </header>

    @if(session('success')) <div class="alert alert-success" role="status">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div> @endif

    <section class="rs-dash-kpis" aria-label="Approval figures">
        <a href="#pending-heading" class="rs-dash-kpi tone-warning"><span class="rs-dash-kpi__icon"><i class="ti ti-clock-hour-4"></i></span><span class="rs-dash-kpi__label">Waiting for review</span><span class="rs-dash-kpi__value">{{ $pendingCount }}</span><span class="rs-dash-kpi__hint">Pending reservation requests</span></a>
        <a href="#history-heading" class="rs-dash-kpi tone-success"><span class="rs-dash-kpi__icon"><i class="ti ti-history"></i></span><span class="rs-dash-kpi__label">Approval history</span><span class="rs-dash-kpi__value">{{ $historyCount }}</span><span class="rs-dash-kpi__hint">Recorded decisions</span></a>
    </section>

    <section class="rs-dash-panel mb-3" aria-labelledby="pending-heading">
        <div class="rs-dash-panel__head"><h2 id="pending-heading">Pending requests <span class="opacity-75">· {{ $pending->count() }} awaiting a decision</span></h2><i class="ti ti-inbox fs-5 text-white"></i></div>
        <div class="rs-dash-panel__body">
            @forelse($pending as $reservation)
                <article class="approval-card">
                    <details class="approval-disclosure">
                        <summary class="approval-summary">
                            <span class="approval-summary__main">
                                <span class="d-flex flex-wrap align-items-center gap-2"><strong>{{ $reservation->reference }}</strong><span class="badge bg-warning text-dark">Pending</span></span>
                                <span class="d-block fw-semibold approval-summary__title mt-1">{{ $reservation->title ?: $reservation->purpose }}</span>
                                <span class="approval-summary__meta">{{ $reservation->requester?->name ?? 'Unknown' }} · {{ $reservation->resourceNames() ?: 'Resource not listed' }}</span>
                            </span>
                            <span class="approval-summary__date small">{{ $reservation->reservation_date?->format('D, d M Y') }} · {{ substr((string)$reservation->start_time, 0, 5) }}–{{ substr((string)$reservation->end_time, 0, 5) }}</span>
                            <span class="approval-summary__toggle"><span class="is-closed">Review <i class="ti ti-chevron-down"></i></span><span class="is-open">Close <i class="ti ti-chevron-up"></i></span></span>
                        </summary>
                        <div class="approval-expanded">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2"><a class="fw-semibold text-decoration-none" href="{{ route('reservations.show', $reservation) }}">{{ $reservation->reference }}</a><span class="badge bg-warning text-dark">Pending approval</span><span class="text-muted small">Submitted {{ $reservation->submitted_at?->timezone(config('reservations.display_timezone', 'Asia/Colombo'))->format('d M Y, H:i') ?? '—' }}</span></div>
                            <h3 class="h6 mb-2">{{ $reservation->title ?: $reservation->purpose }}</h3>
                            <div class="row g-2 small">
                                <div class="col-md-4"><span class="text-muted">Requester</span><div>{{ $reservation->requester?->name ?? 'Unknown' }} <span class="text-muted">{{ $reservation->requester?->email }}</span></div></div>
                                <div class="col-md-4"><span class="text-muted">Date and time</span><div>{{ $reservation->reservation_date?->format('D, d M Y') }} · {{ substr((string)$reservation->start_time, 0, 5) }}–{{ substr((string)$reservation->end_time, 0, 5) }}</div></div>
                                <div class="col-md-4"><span class="text-muted">Purpose</span><div>{{ $reservation->purpose }}</div></div>
                                <div class="col-md-8"><span class="text-muted">Resources</span><div>@foreach($reservation->items as $item)<span class="badge bg-light text-dark border me-1 mb-1">{{ $item->resource_name_snapshot }} <span class="text-muted">· {{ $item->resource?->type?->category?->name ?? 'Uncategorized' }}</span></span>@endforeach</div></div>
                                <div class="col-md-4"><span class="text-muted">Location / attendees</span><div>{{ $reservation->location?->name ?? '—' }} · {{ $reservation->attendee_count ?? '—' }} attendees</div></div>
                            </div>
                            @if($reservation->description)<p class="small text-muted mt-2 mb-0">{{ $reservation->description }}</p>@endif
                        </div>
                        @if($canDecide)
                            <form method="POST" action="{{ route('approvals.decision', $reservation) }}" class="approval-action" style="min-width:min(100%, 300px);max-width:360px">
                                @csrf
                                <label class="form-label small fw-semibold" for="comment-{{ $reservation->id }}">Decision comment <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm mb-2" id="comment-{{ $reservation->id }}" name="comment" rows="2" maxlength="2000" required placeholder="Add a note for the requester"></textarea>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-success btn-sm" name="decision" value="approve"><i class="ti ti-check me-1"></i>Approve</button>
                                    <button class="btn btn-outline-warning btn-sm" name="decision" value="request_changes"><i class="ti ti-edit me-1"></i>Request changes</button>
                                    <button class="btn btn-outline-danger btn-sm" name="decision" value="reject"><i class="ti ti-x me-1"></i>Reject</button>
                                </div>
                            </form>
                        @endif
                        </div>
                        </div>
                    </details>
                </article>
            @empty
                <div class="rs-dash-empty text-center"><i class="ti ti-circle-check text-success fs-3 d-block mb-2"></i><strong>You’re all caught up</strong><div>No pending requests are assigned to you.</div></div>
            @endforelse
        </div>
    </section>

    <section class="rs-dash-panel" aria-labelledby="history-heading">
        <div class="rs-dash-panel__head"><h2 id="history-heading">Approval history</h2><span class="small text-white opacity-75">Completed decisions and approver comments</span></div>
        <div class="rs-dash-panel__body">
            <form method="GET" action="{{ route('approvals.index') }}#history-heading" class="row g-2 align-items-end approval-filters">
                <div class="col-md-4"><label class="form-label small" for="approval-search">Search</label><input id="approval-search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Reference, requester, resource"></div>
                <div class="col-md-2"><label class="form-label small" for="approval-status">Decision</label><select id="approval-status" name="status" class="form-select"><option value="">All decisions</option>@foreach(['approved'=>'Approved','rejected'=>'Rejected','changes_requested'=>'Changes requested'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small" for="approval-category">Category</label><select id="approval-category" name="category_id" class="form-select"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)request('category_id')===(string)$category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small" for="approval-from">Reservation from</label><input type="date" id="approval-from" name="from_date" class="form-control" value="{{ request('from_date') }}"></div>
                <div class="col-md-2"><label class="form-label small" for="approval-to">To</label><input type="date" id="approval-to" name="to_date" class="form-control" value="{{ request('to_date') }}"></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary btn-sm"><i class="ti ti-filter me-1"></i>Apply filters</button><a class="btn btn-outline-secondary btn-sm" href="{{ route('approvals.index') }}#history-heading">Clear</a></div>
            </form>
        @forelse($history as $reservation)
            <article class="approval-history">
                <details class="approval-disclosure">
                    <summary class="approval-summary">
                        <span class="approval-summary__main">
                            <span class="d-flex flex-wrap align-items-center gap-2"><strong>{{ $reservation->reference }}</strong><span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span></span>
                            <span class="d-block fw-semibold approval-summary__title mt-1">{{ $reservation->title ?: $reservation->purpose }}</span>
                            <span class="approval-summary__meta">{{ $reservation->requester?->name ?? 'Unknown' }} · {{ $reservation->resourceNames() }}</span>
                        </span>
                        <span class="approval-summary__date small text-muted">{{ $reservation->reservation_date?->format('d M Y') }} · {{ substr((string)$reservation->start_time,0,5) }}–{{ substr((string)$reservation->end_time,0,5) }}</span>
                        <span class="approval-summary__toggle"><span class="is-closed">Details <i class="ti ti-chevron-down"></i></span><span class="is-open">Close <i class="ti ti-chevron-up"></i></span></span>
                    </summary>
                    <div class="approval-expanded">
                <div class="small text-muted mb-2">{{ $reservation->title }} · Requested by {{ $reservation->requester?->name ?? 'Unknown' }} · {{ $reservation->resourceNames() }}</div>
                @foreach($reservation->statusHistory->whereIn('to_status', ['approved','rejected','changes_requested']) as $entry)
                    <div class="d-flex align-items-start gap-2 small mt-2 approval-comment"><i class="ti ti-message-circle text-primary mt-1"></i><div><strong>{{ $entry->actor?->name ?? 'System' }}</strong> · {{ $entry->created_at?->timezone(config('reservations.display_timezone', 'Asia/Colombo'))->format('d M Y, H:i') }}<div class="text-muted">{{ $entry->reason ?: 'No comment recorded.' }}</div></div></div>
                @endforeach
                    </div>
                </details>
            </article>
        @empty
            <div class="rs-dash-empty text-center">No decisions match these filters.</div>
        @endforelse
        @if($history->hasPages())<div class="pt-2">{{ $history->links() }}</div>@endif
    </section>
</div>
@endsection
