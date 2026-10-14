@extends('layouts.app')

@section('title', 'Reservation Calendar')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-calendar.css') }}?v=4">
@endpush

@section('content')
<div class="container-fluid mt-4 rsv-page reservation-calendar-page">
    <div class="rsv-header">
        <div>
            <h1 class="rsv-title">Reservation Calendar</h1>
            <p class="rsv-subtitle mb-0">Click a date to view bookings or create one for that slot.</p>
        </div>
        @if($canCreate)
            <a href="{{ route('reservations.create') }}" class="btn rsv-create"><i class="ti ti-plus me-2"></i>Create Reservation</a>
        @endif
    </div>

    <div class="rsv-stats">
        <div class="rsv-stat" style="--stat-color:#1769c2">
            <span class="rsv-stat-icon"><i class="ti ti-calendar-event"></i></span>
            <div class="rsv-stat-label">This month</div>
            <div class="rsv-stat-number" id="rsv-stat-month">{{ $stats['month'] }}</div>
        </div>
        <div class="rsv-stat" style="--stat-color:#16a77a">
            <span class="rsv-stat-icon"><i class="ti ti-sun"></i></span>
            <div class="rsv-stat-label">Today</div>
            <div class="rsv-stat-number" id="rsv-stat-today">{{ $stats['today'] }}</div>
        </div>
        <div class="rsv-stat" style="--stat-color:#d97706">
            <span class="rsv-stat-icon"><i class="ti ti-clock-hour-4"></i></span>
            <div class="rsv-stat-label">Pending</div>
            <div class="rsv-stat-number" id="rsv-stat-pending">{{ $stats['pending'] }}</div>
        </div>
        <div class="rsv-stat" style="--stat-color:#7c5cbf">
            <span class="rsv-stat-icon"><i class="ti ti-circle-check"></i></span>
            <div class="rsv-stat-label">Approved</div>
            <div class="rsv-stat-number" id="rsv-stat-approved">{{ $stats['approved'] }}</div>
        </div>
    </div>

    <form method="GET" action="{{ route('reservations.calendar') }}" class="rsv-filters" id="reservation-calendar-filters">
        <input type="hidden" name="year" id="rsv-year" value="{{ $year }}">
        <input type="hidden" name="month" id="rsv-month" value="{{ $month }}">
        <div class="rsv-filter">
            <label for="calendar-location">Location</label>
            <x-rm-select id="calendar-location" name="location_id" placeholder="All locations" :value="request('location_id', '')"
                :options="collect([['value' => '', 'label' => 'All locations']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="calendar-category">Category</label>
            <x-rm-select id="calendar-category" name="resource_category_id" placeholder="All categories" :value="request('resource_category_id', '')"
                :options="collect([['value' => '', 'label' => 'All categories']])->concat($categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="calendar-type">Resource type</label>
            <x-rm-select id="calendar-type" name="resource_type_id" placeholder="All types" :value="request('resource_type_id', '')"
                :options="collect([['value' => '', 'label' => 'All types']])->concat($resourceTypes->map(fn ($type) => ['value' => (string) $type->id, 'label' => $type->name.($type->category ? ' · '.$type->category->name : '')]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="calendar-resource">Resource</label>
            <x-rm-select id="calendar-resource" name="resource_id" placeholder="All resources" :value="request('resource_id', '')"
                :options="collect([['value' => '', 'label' => 'All resources']])->concat($resources->map(fn ($resource) => ['value' => (string) $resource->id, 'label' => $resource->name_model]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="calendar-status">Status</label>
            <x-rm-select id="calendar-status" name="status" placeholder="All statuses" :value="request('status', '')"
                :options="collect([['value' => '', 'label' => 'All statuses']])->concat($statuses->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))->all()" />
        </div>
        @if($owners->count() > 1)
        <div class="rsv-filter">
            <label for="calendar-owner">Reservation owner</label>
            <x-rm-select id="calendar-owner" name="requester_id" placeholder="All owners" :value="request('requester_id', '')"
                :options="collect([['value' => '', 'label' => 'All owners']])->concat($owners->map(fn ($owner) => ['value' => (string) $owner->id, 'label' => $owner->name]))->all()" />
        </div>
        @endif
        <div class="rsv-filter-actions">
            <button type="submit" class="rsv-filter-btn rsv-filter-apply">Apply</button>
            <a href="{{ route('reservations.calendar', ['year' => $year, 'month' => $month]) }}" class="rsv-filter-btn rsv-filter-clear" id="rsv-clear">Clear</a>
        </div>
    </form>

    <div class="rsv-board" id="rsv-board">
        <div class="rsv-legend" aria-label="Holiday types">
            <span><i class="rsv-holiday-swatch poya"></i>Poya</span>
            <span><i class="rsv-holiday-swatch mercantile"></i>Mercantile</span>
            <span><i class="rsv-holiday-swatch bank"></i>Bank</span>
            <span><i class="rsv-holiday-swatch public"></i>Government / public</span>
        </div>
        <div id="calendar-refresh-status" class="rsv-calendar-status" aria-live="polite">Loading reservations…</div>
        <div id="reservationCalendar" data-events-url="{{ $eventsUrl }}" data-availability-url="{{ $availabilityUrl }}" data-create-url="{{ $createUrl }}" data-can-create="{{ $canCreate ? '1' : '0' }}" aria-label="Reservation calendar"></div>
        <div id="calendar-slot-feedback" class="calendar-slot-feedback alert mt-3 mb-0" role="status" aria-live="polite" hidden></div>
    </div>
</div>
<div class="modal fade" id="reservation-calendar-detail" tabindex="-1" aria-labelledby="reservation-calendar-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5 mb-1" id="reservation-calendar-detail-title"></h2>
                    <span id="reservation-calendar-detail-status" class="badge rounded-pill"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Resource</dt><dd class="col-sm-8" id="reservation-calendar-detail-resource"></dd>
                    <dt class="col-sm-4">Owner</dt><dd class="col-sm-8" id="reservation-calendar-detail-owner"></dd>
                    <dt class="col-sm-4">Location</dt><dd class="col-sm-8" id="reservation-calendar-detail-location"></dd>
                    <dt class="col-sm-4">When</dt><dd class="col-sm-8" id="reservation-calendar-detail-time"></dd>
                    <dt class="col-sm-4">Duration</dt><dd class="col-sm-8" id="reservation-calendar-detail-duration"></dd>
                    <dt class="col-sm-4">Purpose</dt><dd class="col-sm-8" id="reservation-calendar-detail-purpose"></dd>
                </dl>
                <div id="reservation-calendar-conflict" class="alert alert-danger mt-3 mb-0" hidden>This booking overlaps another reservation for the same resource. Please review the conflict.</div>
            </div>
            <div class="modal-footer">
                <a id="reservation-calendar-detail-link" class="btn btn-primary" hidden>View reservation</a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>window.RSVCal = { calendarUrl: @json(route('reservations.calendar')) };</script>
<script src="{{ asset('js/resource-select.js') }}?v=1"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
@vite('resources/js/reservation-calendar.js')
@endpush
