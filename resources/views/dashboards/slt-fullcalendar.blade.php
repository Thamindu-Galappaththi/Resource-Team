@extends('layouts.app')

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/resource-management.css') }}?v=11">
<link rel="stylesheet" href="{{ asset('css/resource-calendar.css') }}?v=39">
<link rel="stylesheet" href="{{ asset('css/reservation-calendar.css') }}?v=14">
@endpush

@section('content')
<div class="rc-page reservation-calendar-page is-slt-calendar">
    <div class="rc-header">
        <div>
            <h1 class="rc-title">{{ $title }}</h1>
            <p class="rc-subtitle">{{ $subtitle }}</p>
        </div>
        @if($canCreate)
            <a href="{{ $createUrl }}" class="btn rc-header-create"><i class="ti ti-plus me-2"></i>{{ $createLabel }}</a>
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

    <form method="GET" action="{{ $calendarUrl }}" class="rc-filters" id="reservation-calendar-filters" hidden>
        <input type="hidden" name="year" id="rsv-year" value="{{ $year }}">
        <input type="hidden" name="month" id="rsv-month" value="{{ $month }}">
    </form>

    <div class="rc-calendar-card">
        <div id="calendarLoading" class="rc-loading" role="status"><i class="ti ti-loader-2" aria-hidden="true"></i>Loading reservations…</div>
        <div id="calendarEmpty" class="rc-empty" hidden>No reservations this month.</div>
        <div class="rc-calendar-legend" aria-label="Holiday types">
            <span><i class="rc-holiday-swatch poya"></i>Poya</span>
            <span><i class="rc-holiday-swatch mercantile"></i>Mercantile</span>
            <span><i class="rc-holiday-swatch public"></i>Government / public</span>
        </div>
        <div id="reservationCalendar" data-events-url="{{ $eventsUrl }}" data-create-url="{{ $createUrl }}" data-can-create="{{ $canCreate ? '1' : '0' }}" data-slt-employee="1" data-simple="1" aria-label="{{ $title }}"></div>
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
        calendarUrl: @json($calendarUrl),
        statsUrl: @json($statsUrl),
        holidayImageBase: @json(asset('images/holidays')),
        filterCatalog: { categories: [], resources: [] },
    };
</script>
<script src="{{ asset('js/resource-select.js') }}?v=3"></script>
<script src="{{ asset('js/reservation-calendar.js') }}?v=28"></script>
@endpush
