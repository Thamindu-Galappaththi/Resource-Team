@extends('layouts.app')

@section('title', 'Reservation Calendar')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-calendar.css') }}?v=4">
@endpush

@section('content')
@php
    $weekdays = [
        ['full' => 'Sunday', 'short' => 'Sun'],
        ['full' => 'Monday', 'short' => 'Mon'],
        ['full' => 'Tuesday', 'short' => 'Tue'],
        ['full' => 'Wednesday', 'short' => 'Wed'],
        ['full' => 'Thursday', 'short' => 'Thu'],
        ['full' => 'Friday', 'short' => 'Fri'],
        ['full' => 'Saturday', 'short' => 'Sat'],
    ];
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $navQuery = fn (int $year, int $month) => array_merge($calendarQuery, ['year' => $year, 'month' => $month]);
@endphp

<div class="container-fluid mt-4 rsv-page">
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

    <form method="GET" action="{{ route('reservations.calendar') }}" class="rsv-filters" id="rsv-filters">
        <input type="hidden" name="year" id="rsv-year" value="{{ $year }}">
        <input type="hidden" name="month" id="rsv-month" value="{{ $month }}">
        <div class="rsv-filter">
            <label for="locationFilter">Location</label>
            <x-rm-select id="locationFilter" name="location_id" placeholder="All locations" :value="request('location_id', '')"
                :options="collect([['value' => '', 'label' => 'All locations']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="categoryFilter">Category</label>
            <x-rm-select id="categoryFilter" name="resource_category_id" placeholder="All categories" :value="request('resource_category_id', '')"
                :options="collect([['value' => '', 'label' => 'All categories']])->concat($categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="resourceFilter">Resource</label>
            <x-rm-select id="resourceFilter" name="resource_id" placeholder="All resources" :value="request('resource_id', '')"
                :options="collect([['value' => '', 'label' => 'All resources']])->concat($resources->map(fn ($resource) => ['value' => (string) $resource->id, 'label' => $resource->name_model]))->all()" />
        </div>
        <div class="rsv-filter">
            <label for="statusFilter">Status</label>
            <x-rm-select id="statusFilter" name="status" placeholder="All statuses" :value="request('status', '')"
                :options="collect([['value' => '', 'label' => 'All statuses']])->concat($statuses->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))->all()" />
        </div>
        <div class="rsv-filter-actions">
            <button type="submit" class="rsv-filter-btn rsv-filter-apply">Apply</button>
            <a href="{{ route('reservations.calendar', ['year' => $year, 'month' => $month]) }}" class="rsv-filter-btn rsv-filter-clear" id="rsv-clear">Clear</a>
        </div>
    </form>

    <div class="rsv-board" id="rsv-board">
        <div class="rsv-loading" id="rsv-loading" hidden>Updating calendar…</div>
        <div class="rsv-nav">
            <a class="rsv-nav-btn" id="rsv-prev" href="{{ route('reservations.calendar', $navQuery($prev['year'], $prev['month'])) }}"><i class="ti ti-chevron-left"></i> Previous</a>
            <button type="button" class="rsv-month-btn" id="rsv-month-label" data-bs-toggle="modal" data-bs-target="#monthModal">{{ $currentDate->format('F Y') }}</button>
            <a class="rsv-nav-btn" id="rsv-next" href="{{ route('reservations.calendar', $navQuery($next['year'], $next['month'])) }}">Next <i class="ti ti-chevron-right"></i></a>
        </div>
        <div class="rsv-legend" aria-label="Holiday types">
            <span><i class="rsv-holiday-swatch poya"></i>Poya</span>
            <span><i class="rsv-holiday-swatch mercantile"></i>Mercantile</span>
            <span><i class="rsv-holiday-swatch bank"></i>Bank</span>
            <span><i class="rsv-holiday-swatch public"></i>Government / public</span>
        </div>
        <div class="rsv-weekdays">
            @foreach ($weekdays as $weekday)
                <div class="rsv-weekday">
                    <span class="rsv-dow-full">{{ $weekday['full'] }}</span>
                    <span class="rsv-dow-short">{{ $weekday['short'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="rsv-grid" id="rsv-grid">
            @foreach ($cells as $cell)
                @if ($cell['empty'])
                    <div class="rsv-cell is-empty" aria-hidden="true"></div>
                @else
                    <button type="button"
                        class="rsv-cell is-day{{ $cell['isToday'] ? ' is-today' : '' }}{{ $cell['isWeekend'] ? ' is-weekend' : '' }}{{ ! empty($cell['holiday']) ? ' is-holiday is-holiday-'.$cell['holiday']['kind'] : '' }}"
                        data-bs-toggle="modal"
                        data-bs-target="#eventModal"
                        data-date="{{ $cell['iso'] }}"
                        data-display-date="{{ $cell['display'] }}"
                        aria-label="{{ $cell['display'] }}{{ ! empty($cell['holiday']) ? ', '.$cell['holiday']['title'] : '' }}{{ $cell['count'] ? ', '.$cell['count'].' '.\Illuminate\Support\Str::plural('booking', $cell['count']) : '' }}">
                        <span class="rsv-daynum">{{ $cell['day'] }}</span>
                        <span class="rsv-events">
                            @if (! empty($cell['holiday']))
                                <span class="rsv-holiday-chip is-{{ $cell['holiday']['kind'] }}">{{ \Illuminate\Support\Str::limit($cell['holiday']['title'], 22) }}</span>
                            @endif
                            @foreach ($cell['preview'] as $booking)
                                <span class="rsv-chip is-{{ str_replace('_', '-', $booking['status']) }}">
                                    {{ $booking['start_time'] }} {{ \Illuminate\Support\Str::limit($booking['title'] ?: $booking['reference'], 18) }}
                                </span>
                            @endforeach
                            @if ($cell['count'] > 2)
                                <span class="rsv-more">+{{ $cell['count'] - 2 }} more</span>
                            @endif
                        </span>
                    </button>
                @endif
            @endforeach
        </div>
    </div>
</div>

<div class="modal fade rsv-modal" id="eventModal" tabindex="-1" aria-labelledby="eventModalTitle">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="eventModalTitle">Bookings</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="selectedDate" class="fw-semibold mb-3"></p>
                <div id="reservationList"></div>
            </div>
            @if($canCreate)
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <a id="createForDate" class="btn btn-primary" href="{{ route('reservations.create') }}">Create reservation</a>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade rsv-modal" id="monthModal" tabindex="-1" aria-labelledby="yearTitle">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="rsv-year-nav">
                    <button type="button" class="btn btn-sm btn-outline-light" id="previousYear" aria-label="Previous year"><i class="ti ti-chevron-left"></i></button>
                    <h2 class="modal-title h5 mb-0" id="yearTitle">{{ $year }}</h2>
                    <button type="button" class="btn btn-sm btn-outline-light" id="nextYear" aria-label="Next year"><i class="ti ti-chevron-right"></i></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="rsv-month-grid">
                    @foreach ($months as $index => $monthName)
                        <button type="button" class="rsv-month-pick{{ $index + 1 === $month ? ' is-current' : '' }}" data-month="{{ $index + 1 }}">{{ $monthName }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=1"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script>
window.RSVCal = {
    year: {{ $year }},
    month: {{ $month }},
    prev: @json($prev),
    next: @json($next),
    calendarUrl: @json(route('reservations.calendar')),
    createUrl: @json(route('reservations.create')),
    canCreate: @json((bool) $canCreate),
    query: @json($calendarQuery),
    reservationsByDate: @json($reservationsByDate),
    holidaysByDate: @json($holidaysByDate),
    holidayImageBase: @json(asset('images/holidays')),
};
</script>
<script src="{{ asset('js/reservation-calendar.js') }}?v=4"></script>
@endpush
