@extends('layouts.app')

@section('title', 'Approvals')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-index.css') }}?v=18">
<link rel="stylesheet" href="{{ asset('css/approvals-index.css') }}?v=7">
@endpush

@section('content')
@php
    $selectedLocation = $locations->firstWhere('id', (int) ($filters['location_id'] ?? 0));
    $selectedCategory = $categories->firstWhere('id', (int) ($filters['category_id'] ?? 0));
    $statusOptions = [
        '' => 'All decisions',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'changes_requested' => 'Changes requested',
    ];
    $selectedStatus = $statusOptions[$filters['status'] ?? ''] ?? 'All decisions';
@endphp
<div class="container-fluid mt-4 rsv-page ap-page">
    <div class="rsv-header">
        <div>
            <h1 class="rsv-title">Approvals</h1>
            <p class="rsv-subtitle mb-0">Review reservation requests, record decisions, and follow the approval history.</p>
        </div>
        @if(auth()->user()->hasPermission('approvals.special'))
            <div class="rsv-header-actions">
                <a href="{{ route('approvals.special') }}" class="btn rsv-header-btn rsv-create-btn"><i class="ti ti-shield-star me-1"></i>Special approvals</a>
            </div>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success rsv-auto-alert" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rsv-auto-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="ap-stats">
        <div class="rsv-stat p-3" style="--stat-color:#d97706;--stat-icon-bg:#fff4e5">
            <span class="rsv-stat-icon"><i class="ti ti-clock-hour-4"></i></span>
            <div class="rsv-stat-label mt-3">Waiting for review</div>
            <div class="rsv-stat-number" id="ap-stat-pending">{{ number_format($payload['summary']['pending']) }}</div>
        </div>
        <div class="rsv-stat p-3" style="--stat-color:#16a77a;--stat-icon-bg:#e7f6f0">
            <span class="rsv-stat-icon"><i class="ti ti-history"></i></span>
            <div class="rsv-stat-label mt-3">Approval history</div>
            <div class="rsv-stat-number" id="ap-stat-history">{{ number_format($payload['summary']['history']) }}</div>
        </div>
    </div>

    <form id="ap-filters" method="GET" action="{{ route('approvals.index') }}" class="rsv-filters">
        <div class="rsv-filter rsv-f-search">
            <label for="search">Search</label>
            <div class="input-group rsv-search-group">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Search reservations" autocomplete="off">
            </div>
        </div>
        <div class="rsv-filter">
            <label for="from_date">From</label>
            <input type="date" name="from_date" id="from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}">
        </div>
        <div class="rsv-filter">
            <label for="to_date">To</label>
            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}">
        </div>
        <div class="rsv-filter">
            <label for="location_id">Location</label>
            <x-rm-select id="location_id" name="location_id" placeholder="All locations" :value="(string) ($filters['location_id'] ?? '')" :selected-label="$selectedLocation->name ?? 'All locations'"
                :options="collect([['value' => '', 'label' => 'All locations']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="category_id">Category</label>
            <x-rm-select id="category_id" name="category_id" placeholder="All categories" :value="(string) ($filters['category_id'] ?? '')" :selected-label="$selectedCategory->name ?? 'All categories'"
                :options="collect([['value' => '', 'label' => 'All categories']])->concat($categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="status">Decision</label>
            <x-rm-select id="status" name="status" placeholder="All decisions" :value="(string) ($filters['status'] ?? '')" :selected-label="$selectedStatus"
                :options="collect($statusOptions)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values()->all()" />
        </div>
        <div class="rsv-filter-actions">
            <button class="rsv-filter-btn rsv-filter-clear" type="button" id="ap-clear" @disabled(! $hasFilters)>Clear</button>
        </div>
    </form>

    <div class="card border-0 rsv-panel mb-4">
        <div class="card-body">
            <div class="ap-panel-title">
                <h2>Pending requests</h2>
                <span id="ap-pending-count">{{ $payload['summary']['pending'] }} awaiting a decision</span>
            </div>
            <div id="ap-pending-results">
                @include('approvals.partials.table', [
                    'rows' => $payload['pending'],
                    'pager' => $payload['pending_pager'],
                    'statusLabel' => 'Status',
                    'emptyIcon' => 'ti-circle-check',
                    'emptyTitle' => 'You’re all caught up',
                    'emptyCopy' => 'No pending requests match these filters.',
                    'canDecide' => $canDecide,
                ])
            </div>
        </div>
    </div>

    <div class="card border-0 rsv-panel">
        <div class="card-body">
            <div class="ap-panel-title">
                <h2>Approval history</h2>
                <span>Completed decisions and approver comments</span>
            </div>
            <div id="ap-history-results">
                @include('approvals.partials.table', [
                    'rows' => $payload['history'],
                    'pager' => $payload['history_pager'],
                    'statusLabel' => 'Decision',
                    'emptyIcon' => 'ti-history',
                    'emptyTitle' => 'No decisions match these filters.',
                    'emptyCopy' => 'Try a different search or clear the filters.',
                    'canDecide' => $canDecide,
                ])
            </div>
        </div>
    </div>
</div>

<div class="modal fade rsv-modal" id="ap-view-modal" tabindex="-1" aria-labelledby="ap-view-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <span class="rsv-modal-icon" aria-hidden="true"><i class="ti ti-clipboard-check"></i></span>
                <div>
                    <h2 class="modal-title fs-5" id="ap-view-title">Reservation</h2>
                    <p class="rsv-modal-sub mb-0" id="ap-view-sub">Booking information</p>
                </div>
                <span class="badge" id="ap-view-badge"></span>
                <button type="button" class="rsv-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <div id="ap-view-body"></div>
                @if($canDecide)
                    <form id="ap-decision-form" method="POST" class="approval-action mt-3" hidden>
                        @csrf
                        <label class="form-label small fw-semibold" for="ap-decision-comment">Decision comment <span class="text-danger">*</span></label>
                        <textarea class="form-control mb-3 ap-decision-comment" id="ap-decision-comment" name="comment" rows="3" maxlength="2000" required placeholder="Add a note for the requester"></textarea>
                        <div class="ap-decision-actions">
                            <button class="btn btn-success" name="decision" value="approve"><i class="ti ti-check me-1"></i>Approve</button>
                            <button class="btn btn-outline-warning" name="decision" value="request_changes"><i class="ti ti-edit me-1"></i>Request changes</button>
                            <button class="btn btn-outline-danger" name="decision" value="reject"><i class="ti ti-x me-1"></i>Reject</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/resource-select.js') }}?v=2"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script>
    window.APIndex = {
        url: @json(route('approvals.index')),
        feed: @json(route('approvals.feed')),
        canDecide: @json($canDecide),
        payload: @json($payload),
    };
</script>
<script src="{{ asset('js/approvals-index.js') }}?v=8"></script>
@endpush
