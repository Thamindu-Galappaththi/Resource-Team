@extends('layouts.app')

@section('title', 'Reservation Calendar')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/resource-management.css') }}?v=11">
<link rel="stylesheet" href="{{ asset('css/resource-calendar.css') }}?v=38">
<link rel="stylesheet" href="{{ asset('css/reservation-calendar.css') }}?v=11">
@endpush

@section('content')
<div class="rc-page reservation-calendar-page{{ auth()->user()?->hasRole('slt_employee') ? ' is-slt-calendar' : '' }}">
    <div class="rc-header">
        <div>
            <h1 class="rc-title">Reservation Calendar</h1>
            <p class="rc-subtitle">Click a date to view bookings or create one for that slot.</p>
        </div>
        @if($canCreate)
            <a href="{{ route('reservations.create') }}" class="btn rc-header-create"><i class="ti ti-plus me-2"></i>Create Reservation</a>
        @endif
    </div>

    <div class="rc-cards">
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-calendar-event"></i></span>
            <div class="rc-card-label">This month</div>
            <div class="rc-card-value" id="rsv-stat-month">{{ $stats['month'] }}</div>
        </div>
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-sun"></i></span>
            <div class="rc-card-label">Today's bookings</div>
            <div class="rc-card-value" id="rsv-stat-today">{{ $stats['today'] }}</div>
        </div>
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-clock-hour-4"></i></span>
            <div class="rc-card-label">Pending approvals</div>
            <div class="rc-card-value" id="rsv-stat-pending">{{ $stats['pending'] }}</div>
        </div>
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-circle-check"></i></span>
            <div class="rc-card-label">Approved</div>
            <div class="rc-card-value" id="rsv-stat-approved">{{ $stats['approved'] }}</div>
        </div>
    </div>

    <form method="GET" action="{{ route('reservations.calendar') }}" class="rc-filters" id="reservation-calendar-filters">
        <input type="hidden" name="year" id="rsv-year" value="{{ $year }}">
        <input type="hidden" name="month" id="rsv-month" value="{{ $month }}">
        <div class="rc-filter">
            <label for="calendar-location">Location</label>
            <x-rm-select id="calendar-location" name="location_id" placeholder="All locations" :value="request('location_id', '')"
                :options="collect([['value' => '', 'label' => 'All locations']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
        </div>
        <div class="rc-filter">
            <label for="calendar-category">Category</label>
            <x-rm-select id="calendar-category" name="resource_category_id" placeholder="All categories" :value="request('resource_category_id', '')"
                :options="collect([['value' => '', 'label' => 'All categories']])->concat($categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name]))->all()" />
        </div>
        <div class="rc-filter">
            <label for="calendar-resource">Resource</label>
            <x-rm-select id="calendar-resource" name="resource_id" placeholder="All resources" :value="request('resource_id', '')"
                :options="collect([['value' => '', 'label' => 'All resources']])->concat($resources->map(fn ($resource) => ['value' => (string) $resource->id, 'label' => $resource->name_model]))->all()" />
        </div>
        <div class="rc-filter">
            <label for="calendar-status">Status</label>
            <x-rm-select id="calendar-status" name="status" placeholder="All statuses" :value="request('status', '')"
                :options="collect([['value' => '', 'label' => 'All statuses']])->concat($statuses->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))->all()" />
        </div>
        <div class="rc-filter-actions">
            <button type="button" id="refreshCalendarButton" class="rc-filter-button rc-filter-refresh">
                <i class="ti ti-refresh" aria-hidden="true"></i>Refresh
            </button>
            <button type="button" id="clearFiltersButton" class="rc-filter-button rc-filter-clear" disabled>Clear</button>
        </div>
    </form>

    <div class="rc-calendar-card">
        <div id="calendarLoading" class="rc-loading" role="status"><i class="ti ti-loader-2" aria-hidden="true"></i>Loading reservations…</div>
        <div id="calendarEmpty" class="rc-empty" hidden>No reservations match your filters.</div>
        <div class="rc-calendar-legend" aria-label="Holiday types">
            <span><i class="rc-holiday-swatch poya"></i>Poya</span>
            <span><i class="rc-holiday-swatch mercantile"></i>Mercantile</span>
            <span><i class="rc-holiday-swatch public"></i>Government / public</span>
        </div>
        <div id="reservationCalendar" data-events-url="{{ $eventsUrl }}" data-availability-url="{{ $availabilityUrl }}" data-create-url="{{ $createUrl }}" data-can-create="{{ $canCreate ? '1' : '0' }}" data-slt-employee="{{ auth()->user()?->hasRole('slt_employee') ? '1' : '0' }}" aria-label="Reservation calendar"></div>
        <div id="calendar-slot-feedback" class="calendar-slot-feedback alert mt-3 mb-0" role="status" aria-live="polite" hidden></div>
    </div>
</div>

<div id="reservationModal" class="rc-modal" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle" aria-hidden="true">
    <div class="rc-modal-overlay"></div>
    <div class="rc-modal-box">
        <button type="button" id="closeReservationModal" class="rc-modal-close" aria-label="Close"><i class="ti ti-x"></i></button>
        <div class="rc-modal-header">
            <div class="rc-modal-icon"><i class="ti ti-calendar-event"></i></div>
            <div>
                <h2 id="reservationModalTitle">Reservation details</h2>
                <p>Booking information</p>
            </div>
        </div>
        <div class="rc-modal-body">
            <div class="rc-detail-row"><div class="rc-detail-label">Resource</div><div id="detailResource" class="rc-detail-value">—</div></div>
            <div class="rc-detail-row"><div class="rc-detail-label">Date</div><div id="detailDate" class="rc-detail-value">—</div></div>
            <div class="rc-detail-row"><div class="rc-detail-label">Booking time</div><div id="detailTime" class="rc-detail-value">—</div></div>
            <div class="rc-detail-row"><div class="rc-detail-label">Reserved by</div><div id="detailOwner" class="rc-detail-value">—</div></div>
            <div class="rc-detail-row"><div class="rc-detail-label">Location</div><div id="detailLocation" class="rc-detail-value">—</div></div>
            <div class="rc-detail-row"><div class="rc-detail-label">Purpose</div><div id="detailPurpose" class="rc-detail-value">—</div></div>
            <div class="rc-detail-row"><div class="rc-detail-label">Status</div><div id="detailStatus" class="rc-detail-value">—</div></div>
            <div id="detailConflict" class="alert alert-danger mt-2 mb-0" hidden>This booking overlaps another reservation for the same resource.</div>
            <a id="detailLink" class="btn btn-primary mt-2" hidden>View reservation</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>
<script>
    window.RSVCal = {
        calendarUrl: @json(route('reservations.calendar')),
        statsUrl: @json(route('reservations.calendar.stats')),
        holidayImageBase: @json(asset('images/holidays')),
        filterCatalog: @json($filterCatalog),
    };
</script>
<script src="{{ asset('js/resource-select.js') }}?v=3"></script>
<script src="{{ asset('js/reservation-calendar.js') }}?v=23"></script>
@endpush
