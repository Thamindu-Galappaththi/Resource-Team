@extends('layouts.app')

@section('title', 'Approvals')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-role.css') }}?v={{ @filemtime(public_path('css/dashboard-role.css')) ?: time() }}">
<style>
    .rs-approvals .rs-dash-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rs-approvals .approval-card { padding: 1rem; background: #fff; border: 1px solid #b7e2d9; border-radius: 14px; }
    .rs-approvals .approval-card + .approval-card { margin-top: .75rem; }
    .rs-approvals .approval-summary { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(180px, .8fr) auto; align-items: center; gap: .5rem 1rem; }
    .rs-approvals .approval-summary__main { min-width: 0; }
    .rs-approvals .approval-summary__title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rs-approvals .approval-summary__meta { color: #718096; font-size: .8rem; }
    .rs-approvals .approval-actions { display: flex; justify-content: flex-end; gap: .4rem; white-space: nowrap; }
    .rs-approvals .approval-actions .btn { width: 36px; height: 36px; display: inline-grid; place-items: center; padding: 0; }
    .rs-approvals .approval-action { background: #f0fdfa; border: 1px solid #b7e2d9; border-radius: 12px; padding: 1rem; }
    .rs-approvals .approval-filters { background: #fff; border: 1px solid #b7e2d9; border-radius: 12px; padding: 1rem; }
    .rs-approvals .approval-history { background: #fff; border: 1px solid #b7e2d9; border-radius: 12px; padding: 1rem; }
    .rs-approvals .approval-history + .approval-history { margin-top: .75rem; }
    .rs-approvals .rs-dash-panel__body { gap: .75rem; }
    @media (max-width: 575.98px) { .rs-approvals .rs-dash-kpis { grid-template-columns: 1fr; } }
    @media (max-width: 767.98px) {
        .rs-approvals .approval-summary { grid-template-columns: minmax(0, 1fr) auto; }
        .rs-approvals .approval-summary__date { grid-column: 1; }
        .rs-approvals .approval-actions { grid-column: 2; grid-row: 1 / span 2; }
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
        <div class="rs-dash-panel__head"><h2 id="pending-heading">Pending requests <span class="opacity-75">· {{ $pendingCount }} awaiting a decision</span></h2><i class="ti ti-inbox fs-5 text-white"></i></div>
        <div class="rs-dash-panel__body">
            @forelse($pending as $reservation)
                <article class="approval-card">
                    <div class="approval-summary">
                            <span class="approval-summary__main">
                                <span class="d-flex flex-wrap align-items-center gap-2"><strong>{{ $reservation->reference }}</strong><span class="badge bg-warning text-dark">Pending</span></span>
                                <span class="d-block fw-semibold approval-summary__title mt-1">{{ $reservation->title ?: $reservation->purpose }}</span>
                                <span class="approval-summary__meta">{{ $reservation->requester?->name ?? 'Unknown' }} · {{ $reservation->resourceNames() ?: 'Resource not listed' }}</span>
                            </span>
                            <span class="approval-summary__date small">{{ $reservation->reservation_date?->format('D, d M Y') }} · {{ substr((string)$reservation->start_time, 0, 5) }}–{{ substr((string)$reservation->end_time, 0, 5) }}</span>
                            <div class="approval-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#approval-view-{{ $reservation->id }}" aria-label="View {{ $reservation->reference }}" title="View"><i class="ti ti-eye"></i></button>
                                @if($canDecide)
                                    <form method="POST" action="{{ route('approvals.archive', $reservation) }}" data-approval-archive>
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="archive_reason" value="">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete {{ $reservation->reference }} from approvals" title="Delete"><i class="ti ti-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                    </div>
                    <div class="modal fade" id="approval-view-{{ $reservation->id }}" tabindex="-1" aria-labelledby="approval-view-title-{{ $reservation->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div><h2 class="modal-title fs-5" id="approval-view-title-{{ $reservation->id }}">{{ $reservation->reference }}</h2><span class="badge bg-warning text-dark">Pending approval</span></div>
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
                                    @if($canDecide)
                                        <form method="POST" action="{{ route('approvals.decision', $reservation) }}" class="approval-action mt-3">
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
                        </div>
                    </div>
                </article>
            @empty
                <div class="rs-dash-empty text-center"><i class="ti ti-circle-check text-success fs-3 d-block mb-2"></i><strong>You’re all caught up</strong><div>No pending requests are assigned to you.</div></div>
            @endforelse
            @if($pending->hasPages())<div class="pt-2">{{ $pending->links() }}</div>@endif
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
                <div class="approval-summary">
                        <span class="approval-summary__main">
                            <span class="d-flex flex-wrap align-items-center gap-2"><strong>{{ $reservation->reference }}</strong><span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span></span>
                            <span class="d-block fw-semibold approval-summary__title mt-1">{{ $reservation->title ?: $reservation->purpose }}</span>
                            <span class="approval-summary__meta">{{ $reservation->requester?->name ?? 'Unknown' }} · {{ $reservation->resourceNames() }}</span>
                        </span>
                        <span class="approval-summary__date small text-muted">{{ $reservation->reservation_date?->format('d M Y') }} · {{ substr((string)$reservation->start_time,0,5) }}–{{ substr((string)$reservation->end_time,0,5) }}</span>
                        <div class="approval-actions">
                            <a href="{{ route('reservations.show', $reservation) }}" class="btn btn-sm btn-outline-primary" aria-label="View {{ $reservation->reference }}" title="View"><i class="ti ti-eye"></i></a>
                            @if($canDecide)
                                <form method="POST" action="{{ route('approvals.archive', $reservation) }}" data-approval-archive>
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="archive_reason" value="">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete {{ $reservation->reference }} from approvals" title="Delete"><i class="ti ti-trash"></i></button>
                                </form>
                            @endif
                        </div>
                </div>
            </article>
        @empty
            <div class="rs-dash-empty text-center">No decisions match these filters.</div>
        @endforelse
        @if($history->hasPages())<div class="pt-2">{{ $history->appends(request()->except('pending_page'))->links() }}</div>@endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-approval-archive]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!window.confirm('Remove this reservation from the Approvals page? Reservation history will be kept.')) return;

            const reason = window.prompt('Add a reason for the audit history:');
            if (!reason || !reason.trim()) return;

            form.querySelector('[name="archive_reason"]').value = reason.trim();
            form.submit();
        });
    });
</script>
@endpush
