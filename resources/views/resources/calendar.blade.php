@extends('layouts.app')

@section('title', 'Resource Calendar')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/resource-management.css') }}?v=11">
<link rel="stylesheet" href="{{ asset('css/resource-calendar.css') }}?v=39">
@endpush

@section('content')
<div class="rc-page">
    <div class="rc-header">
        <h1 class="rc-title">Resource calendar</h1>
        <p class="rc-subtitle">View schedules, check availability, and open reservation details.</p>
    </div>

    <div class="rc-cards">
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-packages"></i></span>
            <div class="rc-card-label">Active resources</div>
            <div class="rc-card-value">{{ $stats['myResources'] }}</div>
        </div>
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-calendar-event"></i></span>
            <div class="rc-card-label">Today's bookings</div>
            <div class="rc-card-value">{{ $stats['todayBookings'] }}</div>
        </div>
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-clock-hour-4"></i></span>
            <div class="rc-card-label">Pending approvals</div>
            <div class="rc-card-value">{{ $stats['pendingApprovals'] }}</div>
        </div>
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-chart-bar"></i></span>
            <div class="rc-card-label">This month</div>
            <div class="rc-card-value">{{ $stats['thisMonth'] }}</div>
        </div>
    </div>

    <div class="rc-filters" id="resource-calendar-filters">
        <div class="rc-filter">
            <label for="locationFilter">Location</label>
            <x-rm-select id="locationFilter" value="all" selected-label="All locations" placeholder="All locations"
                :options="collect([['value' => 'all', 'label' => 'All locations']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
        </div>
        <div class="rc-filter">
            <label for="categoryFilter">Category</label>
            <x-rm-select id="categoryFilter" value="all" selected-label="All categories" placeholder="All categories"
                :options="collect([['value' => 'all', 'label' => 'All categories']])->concat($categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name]))->all()" />
        </div>
        <div class="rc-filter">
            <label for="resourceFilter">Resource</label>
            <x-rm-select id="resourceFilter" value="all" selected-label="All resources" placeholder="All resources"
                :options="collect([['value' => 'all', 'label' => 'All resources']])->concat($resources->map(fn ($resource) => ['value' => (string) $resource->id, 'label' => $resource->name_model]))->all()" />
        </div>
        <div class="rc-filter">
            <label for="statusFilter">Status</label>
            <x-rm-select id="statusFilter" value="all" selected-label="All statuses" placeholder="All statuses" :options="[
                ['value' => 'all', 'label' => 'All statuses'],
                ['value' => 'approved', 'label' => 'Approved'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'rejected', 'label' => 'Rejected'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ]" />
        </div>
        <div class="rc-filter-actions">
            <button type="button" id="refreshCalendarButton" class="rc-filter-button rc-filter-refresh">
                <i class="ti ti-refresh" aria-hidden="true"></i>Refresh
            </button>
            <button type="button" id="clearFiltersButton" class="rc-filter-button rc-filter-clear" disabled>Clear</button>
        </div>
    </div>

    <div class="rc-view-switch" role="tablist" aria-label="Calendar layout">
        <button type="button" class="active" id="calendarViewButton" role="tab" aria-selected="true">Calendar</button>
        <button type="button" id="schedulerViewButton" role="tab" aria-selected="false">Timeline</button>
    </div>

    <div class="rc-calendar-card">
        <div id="calendarLoading" class="rc-loading" role="status"><i class="ti ti-loader-2" aria-hidden="true"></i>Loading reservations…</div>
        <div id="calendarEmpty" class="rc-empty" hidden>No reservations match your filters.</div>
        <div class="rc-calendar-legend" aria-label="Holiday types">
            <span><i class="rc-holiday-swatch poya"></i>Poya</span>
            <span><i class="rc-holiday-swatch mercantile"></i>Mercantile</span>
            <span><i class="rc-holiday-swatch public"></i>Government / public</span>
        </div>
        <div id="resourceCalendar"></div>
    </div>

    <section id="schedulerPanel" class="rc-calendar-card rc-scheduler" hidden>
        <div class="rc-scheduler-heading">
            <div>
                <h2>Resource timeline</h2>
                <p>Select resources, then drag across open hours to prepare a reservation.</p>
                <div id="schedulerSelectionCount" class="rc-selection-count">No extra resources selected</div>
            </div>
            <label class="rc-scheduler-date">Schedule date
                <input type="date" id="schedulerDate" value="{{ now(config('reservations.display_timezone', 'Asia/Colombo'))->toDateString() }}">
            </label>
        </div>
        <div class="rc-scheduler-legend">
            <span><i class="rc-legend-dot confirmed"></i>Confirmed</span>
            <span><i class="rc-legend-dot tentative"></i>Tentative</span>
            <span><i class="rc-legend-dot maintenance"></i>Maintenance</span>
            <span><i class="rc-legend-dot overbooked"></i>Overbooked</span>
        </div>
        <div id="schedulerEmpty" class="rc-empty" hidden>No resources match this category.</div>
        <div id="schedulerGrid" class="rc-scheduler-scroll"></div>
        <div id="schedulerPopover" class="rc-scheduler-popover" hidden></div>
        <div class="rc-scheduler-note">Selecting an open slot opens the reservation form. Final booking still follows the approval process.</div>
    </section>

    <div class="rc-table-card">
        <h3 class="rc-table-title"><i class="ti ti-list-check"></i> Upcoming reservations</h3>
        <div class="rc-table-wrap">
            <table class="rc-table">
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reserved by</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="upcomingReservationsBody">
                    <tr><td colspan="5" class="text-center text-muted">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div class="rc-table-footer">
            <p class="rc-table-count" id="upcomingResultCount" aria-live="polite"></p>
            <ul class="pagination rc-pagination mb-0" id="upcomingPagination"></ul>
        </div>
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
    window.RC = {
        eventsUrl: @json(route('resources.calendar.events')),
        holidays: @json($holidayEvents),
        holidayImageBase: @json(asset('images/holidays')),
        schedulerResources: @json($schedulerResources),
        canReserve: @json($canReserve),
        createReservationUrl: @json(route('reservations.create')),
        filterCatalog: {
            categories: @json($categories->map(fn ($category) => ['id' => (string) $category->id, 'name' => $category->name])->values()),
        },
    };
</script>
<script src="{{ asset('js/resource-select.js') }}?v=3"></script>
<script src="{{ asset('js/resource-calendar.js') }}?v=38"></script>
@endpush
