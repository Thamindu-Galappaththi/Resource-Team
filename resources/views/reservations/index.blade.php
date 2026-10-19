@extends('layouts.app')

@section('title', 'Existing Reservations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-index.css') }}?v=18">
@endpush

@section('content')
@php
    $selectedLocation = $locations->firstWhere('id', (int) request('location_id'));
    $selectedCategory = $categories->firstWhere('id', (int) request('resource_category_id'));
    $selectedResource = $resources->firstWhere('id', (int) request('resource_id'));
    $selectedStatus = collect($statuses)->first(fn ($status) => $status->value === request('status'));
@endphp
<div class="container-fluid mt-4 rsv-page">
    <div class="rsv-header">
        <div>
            <h1 class="rsv-title">Existing Reservations</h1>
            <p class="rsv-subtitle mb-0">Search, filter, and review reservation history.</p>
        </div>
        <div class="rsv-header-actions">
            <a href="{{ route('reservations.calendar') }}" class="btn rsv-header-btn rsv-calendar-btn"><i class="ti ti-calendar-month me-1"></i>Calendar</a>
            @can('create', \App\Models\Reservation::class)
                <a href="{{ route('reservations.create') }}" class="btn rsv-header-btn rsv-create-btn"><i class="ti ti-plus me-1"></i>Create Reservation</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rsv-auto-alert" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rsv-auto-alert" role="alert">{{ $errors->first() }}</div>
    @endif
    <div id="rsv-flash" class="alert rsv-auto-alert d-none" role="status"></div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="rsv-stat p-3 p-md-4" style="--stat-color:#1769c2;--stat-icon-bg:#e8f0fa">
                <span class="rsv-stat-icon"><i class="ti ti-calendar-event"></i></span>
                <div class="rsv-stat-label mt-3">Total</div>
                <div class="rsv-stat-number" id="rsv-stat-total">{{ number_format($payload['summary']['total']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rsv-stat p-3 p-md-4" style="--stat-color:#d97706;--stat-icon-bg:#fff4e5">
                <span class="rsv-stat-icon"><i class="ti ti-clock-hour-4"></i></span>
                <div class="rsv-stat-label mt-3">Pending approval</div>
                <div class="rsv-stat-number" id="rsv-stat-pending">{{ number_format($payload['summary']['pending']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rsv-stat p-3 p-md-4" style="--stat-color:#16a77a;--stat-icon-bg:#e7f6f0">
                <span class="rsv-stat-icon"><i class="ti ti-circle-check"></i></span>
                <div class="rsv-stat-label mt-3">Approved / confirmed</div>
                <div class="rsv-stat-number" id="rsv-stat-approved">{{ number_format($payload['summary']['approved']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rsv-stat p-3 p-md-4" style="--stat-color:#dc3545;--stat-icon-bg:#fdecee">
                <span class="rsv-stat-icon"><i class="ti ti-ban"></i></span>
                <div class="rsv-stat-label mt-3">Rejected / cancelled</div>
                <div class="rsv-stat-number" id="rsv-stat-cancelled">{{ number_format($payload['summary']['cancelled']) }}</div>
            </div>
        </div>
    </div>

    <form id="rsv-filters" class="rsv-filters">
        <div class="rsv-filter rsv-f-search">
            <label for="search">Search</label>
            <div class="input-group rsv-search-group">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="ID, purpose, requester, resource" autocomplete="off">
            </div>
        </div>
        <div class="rsv-filter">
            <label for="from_date">From</label>
            <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
        </div>
        <div class="rsv-filter">
            <label for="to_date">To</label>
            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
        </div>
        <div class="rsv-filter">
            <label for="location_id">Location</label>
            <x-rm-select id="location_id" name="location_id" placeholder="All locations" :value="(string) request('location_id', '')" :selected-label="$selectedLocation->name ?? 'All locations'"
                :options="collect([['value' => '', 'label' => 'All locations']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="resource_category_id">Category</label>
            <x-rm-select id="resource_category_id" name="resource_category_id" placeholder="All categories" :value="(string) request('resource_category_id', '')" :selected-label="$selectedCategory->name ?? 'All categories'"
                :options="collect([['value' => '', 'label' => 'All categories']])->concat($categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="resource_id">Resource</label>
            <x-rm-select id="resource_id" name="resource_id" placeholder="All resources" :value="(string) request('resource_id', '')" :selected-label="$selectedResource->name_model ?? 'All resources'"
                :options="collect([['value' => '', 'label' => 'All resources']])->concat($resources->map(fn ($resource) => ['value' => (string) $resource->id, 'label' => $resource->name_model.($resource->serial_number ? ' ('.$resource->serial_number.')' : '')]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="status">Status</label>
            <x-rm-select id="status" name="status" placeholder="All statuses" :value="(string) request('status', '')" :selected-label="$selectedStatus?->label() ?? 'All statuses'"
                :options="collect([['value' => '', 'label' => 'All statuses']])->concat(collect($statuses)->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))->all()" />
        </div>
        <div class="rsv-filter-actions">
            <button class="rsv-filter-btn rsv-filter-clear" type="button" id="rsv-clear" @disabled(! $payload['meta']['has_filters'])>Clear</button>
        </div>
    </form>

    <div class="card border-0 rsv-panel">
        <div class="card-body position-relative">
            <div id="rsv-loading" class="rsv-loading" hidden>Updating…</div>
            <div id="rsv-results"></div>
        </div>
    </div>
</div>

<div class="modal fade rsv-modal" id="rsv-view-modal" tabindex="-1" aria-labelledby="rsv-view-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <span class="rsv-modal-icon" aria-hidden="true"><i class="ti ti-calendar-event"></i></span>
                <div>
                    <h2 class="modal-title fs-5" id="rsv-view-title"></h2>
                    <p class="rsv-modal-sub mb-0" id="rsv-view-sub"></p>
                </div>
                <span class="badge" id="rsv-view-badge"></span>
                <button type="button" class="rsv-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body" id="rsv-view-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" id="rsv-view-cancel" hidden>Cancel reservation</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade rsv-modal" id="rsv-cancel-modal" tabindex="-1" aria-labelledby="rsv-cancel-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="rsv-cancel-form" class="modal-content">
            <div class="modal-header">
                <span class="rsv-modal-icon" aria-hidden="true"><i class="ti ti-ban"></i></span>
                <div>
                    <h2 class="modal-title fs-5" id="rsv-cancel-title">Cancel reservation</h2>
                    <p class="rsv-modal-sub mb-0">This will release the booked slot.</p>
                </div>
                <button type="button" class="rsv-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="rsv-cancel-reason">Cancellation reason</label>
                <textarea id="rsv-cancel-reason" name="cancellation_reason" class="form-control" rows="3" required placeholder="Reason is required"></textarea>
                <div class="invalid-feedback d-none" id="rsv-cancel-error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep reservation</button>
                <button type="submit" class="btn btn-danger" id="rsv-cancel-submit">Cancel reservation</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=2"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script>
window.RSVIndex = {
    url: @json(route('reservations.index')),
    feed: @json(route('reservations.feed')),
    payload: @json($payload),
};
</script>
<script src="{{ asset('js/reservation-index.js') }}?v=8"></script>
@endpush
