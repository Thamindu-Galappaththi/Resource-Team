@extends('layouts.app')

@section('title', 'Reports & Analytics')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v=5">
@endpush

@section('content')
@php
    $filters = $currentFilters;
    $selectedLocation = $locations->firstWhere('id', (int) ($filters['location_id'] ?? 0));
    $selectedCategory = $categories->firstWhere('id', (int) ($filters['resource_category_id'] ?? 0));
    $selectedStatus = collect($statuses)->first(fn ($status) => $status->value === ($filters['status'] ?? null));
@endphp
<div class="container-fluid mt-4 rpt-page">
    <div class="rpt-header mb-4">
        <div>
            <div class="rpt-kicker"><i class="ti ti-chart-dots me-1"></i> Management Reports &amp; Analytics</div>
            <h1 class="rpt-title">Resource Utilization &amp; Reservations</h1>
            <p class="rpt-print-range" id="rpt-print-range">{{ $filters['from_date'] }} – {{ $filters['to_date'] }}</p>
        </div>
        <div class="rpt-header-actions">
            <button type="button" id="rpt-print" class="rpt-btn rpt-btn-outline"><i class="ti ti-printer"></i> Print</button>
            <div class="dropdown rpt-export">
                <button type="button" class="rpt-btn rpt-btn-white dropdown-toggle" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="ti ti-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item py-2 rpt-export-link" data-export="utilization" href="{{ $payload['export']['utilization'] }}">
                        <i class="ti ti-chart-bar me-2 text-primary"></i> Facility Utilization CSV
                    </a></li>
                    <li><a class="dropdown-item py-2 rpt-export-link" data-export="reservations" href="{{ $payload['export']['reservations'] }}">
                        <i class="ti ti-file-spreadsheet me-2 text-success"></i> Reservation Log CSV
                    </a></li>
                    <li><a class="dropdown-item py-2 rpt-export-link" data-export="lecturers" href="{{ $payload['export']['lecturers'] }}">
                        <i class="ti ti-users me-2 text-warning"></i> Lecturer Fees CSV
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="rpt-filters-card mb-4">
        <div class="rpt-presets-row">
            <span class="rpt-preset-label"><i class="ti ti-filter me-1"></i> Quick:</span>
            <button type="button" class="rpt-preset-pill" data-preset="today">Today</button>
            <button type="button" class="rpt-preset-pill" data-preset="week">This Week</button>
            <button type="button" class="rpt-preset-pill" data-preset="month">This Month</button>
            <button type="button" class="rpt-preset-pill" data-preset="last30">Last 30 Days</button>
            <button type="button" class="rpt-preset-pill" data-preset="quarter">Quarter</button>
        </div>
        <form id="rpt-filter-form">
            <div class="rpt-filter-grid">
                <div class="rpt-filter-item">
                    <label for="from_date"><i class="ti ti-calendar me-1"></i>From</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="{{ $filters['from_date'] }}">
                </div>
                <div class="rpt-filter-item">
                    <label for="to_date"><i class="ti ti-calendar me-1"></i>To</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ $filters['to_date'] }}">
                </div>
                <div class="rpt-filter-item">
                    <label for="location_id"><i class="ti ti-map-pin me-1"></i>Location</label>
                    <x-rm-select id="location_id" name="location_id" placeholder="All locations" :value="(string) ($filters['location_id'] ?? '')" :selected-label="$selectedLocation->name ?? 'All locations'"
                        :options="collect([['value' => '', 'label' => 'All locations']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
                </div>
                <div class="rpt-filter-item">
                    <label for="resource_category_id"><i class="ti ti-category me-1"></i>Category</label>
                    <x-rm-select id="resource_category_id" name="resource_category_id" placeholder="All Categories" :value="(string) ($filters['resource_category_id'] ?? '')" :selected-label="$selectedCategory->name ?? 'All Categories'"
                        :options="collect([['value' => '', 'label' => 'All Categories']])->concat($categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name]))->all()" />
                </div>
                <div class="rpt-filter-item">
                    <label for="status"><i class="ti ti-list-check me-1"></i>Reservation status</label>
                    <x-rm-select id="status" name="status" placeholder="All statuses" :value="(string) ($filters['status'] ?? '')" :selected-label="$selectedStatus?->label() ?? 'All statuses'"
                        :options="collect([['value' => '', 'label' => 'All statuses']])->concat(collect($statuses)->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))->all()" />
                </div>
                <div class="rpt-filter-item rpt-filter-search">
                    <label for="search"><i class="ti ti-search me-1"></i>Search reservations</label>
                    <div class="input-group rpt-search-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="search" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="ID, purpose, requester" autocomplete="off">
                    </div>
                </div>
                <div class="rpt-filter-actions">
                    <button type="submit" class="btn btn-primary px-3"><i class="ti ti-filter me-1"></i>Apply</button>
                    <button type="button" id="rpt-clear" class="btn btn-light border px-3" @disabled(! $payload['has_filters']) title="Clear filters"><i class="ti ti-rotate-clockwise"></i></button>
                </div>
            </div>
            <div class="small text-muted mt-2">Status and search filter reservation reports. Finance, hostel, and canteen summaries use the selected date range and location.</div>
        </form>
    </div>

    <div class="rpt-calc-wrapper">
        <button class="rpt-note-pill-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#calcMethodologyCollapse" aria-expanded="false" aria-controls="calcMethodologyCollapse">
            <i class="ti ti-info-circle"></i>
            <span>Note</span>
        </button>
        <div class="collapse mt-2" id="calcMethodologyCollapse">
            <div class="rpt-calc-note">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <span class="fw-bold text-dark fs-6"><i class="ti ti-calculator me-1 text-primary"></i> Calculation Methodology &amp; Decision Logic</span>
                    <button type="button" class="btn-close btn-sm" data-bs-toggle="collapse" data-bs-target="#calcMethodologyCollapse" aria-label="Close"></button>
                </div>
                <div class="rpt-calc-grid">
                    <div class="rpt-calc-box">
                        <div class="rpt-calc-title"><i class="ti ti-chart-donut text-primary"></i> Facility Utilization Rate</div>
                        <code class="rpt-calc-formula">(Booked Hours / Total Capacity Hours) × 100</code>
                        <p class="rpt-calc-desc">
                            <strong>Booked Hours:</strong> Total minutes from active reservation slots divided by 60.<br>
                            <strong>Total Capacity:</strong> Active Facilities × Days in Period × 8 standard operational hours/day.
                        </p>
                    </div>
                    <div class="rpt-calc-box">
                        <div class="rpt-calc-title"><i class="ti ti-check text-success"></i> Approval Rate</div>
                        <code class="rpt-calc-formula">(Approved Bookings / Total Reservations) × 100</code>
                        <p class="rpt-calc-desc">Includes approved, confirmed, and completed bookings against total submissions. Cancelled, rejected, and drafts lower this rate.</p>
                    </div>
                    <div class="rpt-calc-box">
                        <div class="rpt-calc-title"><i class="ti ti-hourglass-low text-warning"></i> Approval Turnaround &amp; Overdue</div>
                        <code class="rpt-calc-formula">Average (Approved At − Submitted At) in Hours</code>
                        <p class="rpt-calc-desc">Measures processing speed. Overdue indicates pending submissions waiting longer than <strong>24 hours</strong> without a decision.</p>
                    </div>
                    <div class="rpt-calc-box">
                        <div class="rpt-calc-title"><i class="ti ti-building-community text-info"></i> Location &amp; Resource Decision Rules</div>
                        <code class="rpt-calc-formula">Critical ≥80% | High ≥55% | Balanced ≥30% | Idle = 0</code>
                        <p class="rpt-calc-desc">Facilities exceeding 80% load recommend procurement/expansion; below 30% or 0 bookings recommend timetable shifting or space reallocation.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="position-relative">
        <div id="rpt-loading" class="rpt-loading" hidden>Updating…</div>
        <div id="rpt-results">{!! $payload['html'] !!}</div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/resource-select.js') }}?v=2"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script>
window.RPTIndex = {
    url: @json(route('reports.index')),
    feed: @json(route('reports.feed')),
    charts: @json($payload['charts']),
    export: @json($payload['export']),
    filters: @json($payload['filters']),
};
</script>
<script src="{{ asset('js/reports.js') }}?v=3"></script>
@endpush
