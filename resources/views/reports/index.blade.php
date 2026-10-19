@extends('layouts.app')

@section('title', 'Reports & Analytics')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v={{ time() }}">
<style>
/* ── Extra scoped styles ─────────────────────────────────────── */
.rpt-kpi-grid   { display:grid; gap:1rem; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); margin-bottom:1.5rem; }
.rpt-kpi        { background:#fff; border:1px solid #e2e8f0; border-radius:.85rem; padding:1.25rem 1.5rem; display:flex; flex-direction:column; gap:.25rem; }
.rpt-kpi-label  { font-size:.78rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#65738a; }
.rpt-kpi-value  { font-size:2rem; font-weight:800; line-height:1.1; color:#152238; }
.rpt-kpi-sub    { font-size:.82rem; color:#65738a; }
.rpt-kpi-bar    { height:4px; border-radius:99px; background:#e2e8f0; margin-top:.5rem; overflow:hidden; }
.rpt-kpi-fill   { height:100%; border-radius:99px; background:var(--fill,#1769c2); }

/* Alert strip */
.rpt-alert-strip { display:flex; flex-direction:column; gap:.55rem; margin-bottom:1.5rem; }
.rpt-alert-item  { display:flex; align-items:flex-start; gap:.75rem; padding:.9rem 1.1rem; border-radius:.75rem; border-left:4px solid transparent; }
.rpt-alert-item.danger  { background:#fff5f5; border-color:#dc3545; }
.rpt-alert-item.warning { background:#fffbf0; border-color:#f59e0b; }
.rpt-alert-item.success { background:#f0fdf8; border-color:#16a77a; }
.rpt-alert-item.info    { background:#f0f7ff; border-color:#1769c2; }
.rpt-alert-icon { font-size:1.25rem; flex-shrink:0; margin-top:.1rem; }
.rpt-alert-item.danger .rpt-alert-icon  { color:#dc3545; }
.rpt-alert-item.warning .rpt-alert-icon { color:#f59e0b; }
.rpt-alert-item.success .rpt-alert-icon { color:#16a77a; }
.rpt-alert-item.info .rpt-alert-icon    { color:#1769c2; }

/* Tab bar */
.rpt-tabbar { display:flex; flex-wrap:wrap; gap:.35rem; margin-bottom:1.25rem; }
.rpt-tab    { padding:.5rem 1rem; border:1px solid #e2e8f0; border-radius:.6rem; font-size:.85rem; font-weight:600; background:#fff; color:#65738a; cursor:pointer; transition:all .18s; }
.rpt-tab.active  { background:#1769c2; color:#fff; border-color:#1769c2; }
.rpt-tab:hover:not(.active) { background:#f1f5fb; color:#1769c2; }
.rpt-tab-pane    { display:none; }
.rpt-tab-pane.active { display:block; }

/* Card */
.rpt-card       { background:#fff; border:1px solid #e2e8f0; border-radius:.9rem; margin-bottom:1.25rem; overflow:hidden; }
.rpt-card-head  { padding:1rem 1.5rem; border-bottom:1px solid #f0f4f9; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; }
.rpt-card-title { font-size:1rem; font-weight:700; color:#152238; margin:0; display:flex; align-items:center; gap:.4rem; }
.rpt-card-desc  { font-size:.82rem; color:#65738a; margin:.2rem 0 0; }
.rpt-card-body  { padding:1.25rem 1.5rem; }

/* Table */
.rpt-tbl        { width:100%; border-collapse:collapse; font-size:.875rem; }
.rpt-tbl th     { background:#f8fafc; color:#65738a; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:.65rem 1rem; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
.rpt-tbl td     { padding:.65rem 1rem; border-bottom:1px solid #f0f4f9; vertical-align:middle; color:#152238; }
.rpt-tbl tbody tr:hover td { background:#f8fafc; }
.rpt-tbl tbody tr:last-child td { border-bottom:none; }

/* Utilization bar */
.rpt-bar-wrap { width:90px; background:#f0f4f9; border-radius:99px; height:8px; overflow:hidden; display:inline-block; vertical-align:middle; }
.rpt-bar-fill { height:100%; border-radius:99px; }
.rpt-bar-fill.low  { background:#16a77a; }
.rpt-bar-fill.med  { background:#f59e0b; }
.rpt-bar-fill.high { background:#dc3545; }

/* Heatmap slot */
.rpt-slot-row  { display:grid; grid-template-columns:110px 1fr 50px 160px; align-items:center; gap:.75rem; padding:.55rem 0; border-bottom:1px solid #f0f4f9; }
.rpt-slot-row:last-child { border-bottom:none; }
.rpt-slot-track { background:#f0f4f9; border-radius:99px; height:10px; overflow:hidden; }
.rpt-slot-fill  { height:100%; border-radius:99px; }

/* Campus card grid */
.rpt-campus-grid { display:grid; gap:1rem; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); }
.rpt-campus-card { border:1px solid #e2e8f0; border-radius:.75rem; padding:1.1rem; background:#fff; display:flex; flex-direction:column; }
.rpt-campus-name { font-weight:700; font-size:.95rem; margin-bottom:.5rem; }
.rpt-campus-rec  { display:inline-block; white-space:normal !important; word-break:break-word; text-align:left; font-size:.76rem; line-height:1.35; padding:.3rem .6rem; border-radius:.4rem; }
.rpt-dec-badge   { display:inline-block; white-space:normal !important; word-break:break-word; font-size:.76rem; font-weight:600; line-height:1.35; padding:.3rem .65rem; border-radius:.4rem; }
.rpt-slot-label  { display:inline-block; white-space:normal !important; word-break:break-word; font-size:.76rem; font-weight:600; line-height:1.3; padding:.25rem .6rem; border-radius:.4rem; }

/* Lecturer table stat pill */
.rpt-pill { display:inline-block; padding:.2rem .6rem; border-radius:.4rem; font-size:.78rem; font-weight:600; }
</style>
@endpush

@section('content')
<div class="container-fluid mt-4 rpt-page">

    {{-- ── HEADER ─────────────────────────────────────────────────────────── --}}
    <div class="rpt-header mb-4">
        <div>
            <div class="rpt-kicker"><i class="ti ti-chart-dots me-1"></i> Reports & Decision Analytics</div>
            <h1 class="rpt-title">Operations Dashboard</h1>
        </div>
        <div class="rpt-header-actions">
            <button onclick="window.print()" class="rpt-btn rpt-btn-outline"><i class="ti ti-printer"></i> Print</button>
            <div class="dropdown">
                <button class="rpt-btn rpt-btn-white dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="ti ti-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item py-2" href="{{ route('reports.export', array_merge(request()->query(), ['export_type'=>'utilization'])) }}">
                        <i class="ti ti-chart-bar me-2 text-primary"></i> Facility Utilization CSV
                    </a></li>
                    <li><a class="dropdown-item py-2" href="{{ route('reports.export', array_merge(request()->query(), ['export_type'=>'reservations'])) }}">
                        <i class="ti ti-file-spreadsheet me-2 text-success"></i> Reservation Log CSV
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ── FILTERS ─────────────────────────────────────────────────────────── --}}
    <div class="rpt-filters-card mb-4">
        <div class="rpt-presets-row">
            <span class="rpt-preset-label"><i class="ti ti-filter me-1"></i> Quick:</span>
            <button class="rpt-preset-pill" data-preset="today">Today</button>
            <button class="rpt-preset-pill" data-preset="week">This Week</button>
            <button class="rpt-preset-pill" data-preset="month">This Month</button>
            <button class="rpt-preset-pill" data-preset="last30">Last 30 Days</button>
            <button class="rpt-preset-pill" data-preset="quarter">Quarter</button>
        </div>
        <form id="rpt-filter-form" method="GET" action="{{ route('reports.index') }}">
            <div class="rpt-filter-grid">
                <div class="rpt-filter-item">
                    <label><i class="ti ti-calendar me-1"></i>From</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $currentFilters['from_date'] }}">
                </div>
                <div class="rpt-filter-item">
                    <label><i class="ti ti-calendar me-1"></i>To</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $currentFilters['to_date'] }}">
                </div>
                <div class="rpt-filter-item">
                    <label><i class="ti ti-map-pin me-1"></i>Campus</label>
                    <select name="location_id" class="form-select">
                        <option value="">All Campuses</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" @selected((string)$loc->id===(string)$currentFilters['location_id'])>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rpt-filter-item">
                    <label><i class="ti ti-category me-1"></i>Category</label>
                    <select name="resource_category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string)$cat->id===(string)$currentFilters['resource_category_id'])>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rpt-filter-actions">
                    <button type="submit" class="btn btn-primary px-3"><i class="ti ti-filter me-1"></i>Apply</button>
                    <a href="{{ route('reports.index') }}" class="btn btn-light border px-3"><i class="ti ti-rotate-clockwise"></i></a>
                </div>
            </div>
        </form>
    </div>

    {{-- ── KPI STRIP ───────────────────────────────────────────────────────── --}}
    <div class="rpt-kpi-grid mb-4">
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Overall Utilization</div>
            <div class="rpt-kpi-value" style="color:#1769c2">{{ $data['summary']['utilization_rate'] }}%</div>
            <div class="rpt-kpi-sub">of total facility capacity</div>
            <div class="rpt-kpi-bar"><div class="rpt-kpi-fill" style="--fill:#1769c2;width:{{ $data['summary']['utilization_rate'] }}%"></div></div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Total Bookings</div>
            <div class="rpt-kpi-value">{{ number_format($data['summary']['total_reservations']) }}</div>
            <div class="rpt-kpi-sub">{{ $data['summary']['approved_count'] }} approved &bull; {{ $data['summary']['pending_count'] }} pending</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Approval Rate</div>
            <div class="rpt-kpi-value" style="color:#16a77a">{{ $data['summary']['approval_rate'] }}%</div>
            <div class="rpt-kpi-sub">{{ $data['summary']['cancelled_count'] }} cancelled / rejected</div>
            <div class="rpt-kpi-bar"><div class="rpt-kpi-fill" style="--fill:#16a77a;width:{{ $data['summary']['approval_rate'] }}%"></div></div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Hours Booked</div>
            <div class="rpt-kpi-value">{{ round($data['summary']['total_hours_booked']) }}</div>
            <div class="rpt-kpi-sub">across {{ $data['summary']['active_resources_count'] }} active facilities</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Canteen Orders</div>
            <div class="rpt-kpi-value" style="color:#d97706">{{ number_format($data['canteen']['total_orders']) }}</div>
            <div class="rpt-kpi-sub">{{ number_format($data['canteen']['total_meals']) }} meals &bull; avg {{ $data['canteen']['daily_avg'] }}/day</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Hostel Guests</div>
            <div class="rpt-kpi-value" style="color:#7c3aed">{{ $data['hostel']['total_guests'] }}</div>
            <div class="rpt-kpi-sub">{{ $data['hostel']['checked_in'] }} in · {{ $data['hostel']['upcoming'] }} upcoming</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Lecturer Fees Payable</div>
            <div class="rpt-kpi-value" style="color:#dc3545">LKR {{ number_format($data['financial']['total_unpaid_lkr'], 0) }}</div>
            <div class="rpt-kpi-sub">{{ $data['financial']['unpaid_count'] }} unpaid records</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Pending Approvals</div>
            <div class="rpt-kpi-value" style="color:{{ $data['approval_health']['stale_pending'] > 3 ? '#dc3545' : '#f59e0b' }}">
                {{ $data['approval_health']['pending_count'] }}
            </div>
            <div class="rpt-kpi-sub">{{ $data['approval_health']['stale_pending'] }} overdue &gt;24h &bull; avg {{ $data['approval_health']['avg_turnaround_hrs'] }}h turnaround</div>
        </div>
    </div>

    {{-- ── TABS ────────────────────────────────────────────────────────────── --}}
    <div class="rpt-tabbar" id="rptTabBar">
        <button class="rpt-tab active" data-tab="facility"><i class="ti ti-building-community me-1"></i> Facility Utilization</button>
        <button class="rpt-tab" data-tab="bookings"><i class="ti ti-calendar-check me-1"></i> Bookings & Approval Health</button>
        <button class="rpt-tab" data-tab="financial"><i class="ti ti-currency-dollar me-1"></i> Financial & Lecturer Fees</button>
        <button class="rpt-tab" data-tab="hostel"><i class="ti ti-home me-1"></i> Hostel</button>
        <button class="rpt-tab" data-tab="canteen"><i class="ti ti-chef-hat me-1"></i> Canteen</button>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         TAB 1 – FACILITY UTILIZATION
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="rpt-tab-pane active" id="pane-facility">

        {{-- Campus Cards --}}
        <div class="rpt-campus-grid mb-4">
            @foreach($data['campus_comparison'] as $campus)
            <div class="rpt-campus-card">
                <div class="rpt-campus-name"><i class="ti ti-map-pin me-1 text-primary"></i>{{ $campus['name'] }}</div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted">Load</span>
                    <strong class="small">{{ $campus['utilization_pct'] }}%</strong>
                </div>
                <div class="rpt-bar-wrap" style="width:100%">
                    <div class="rpt-bar-fill {{ $campus['utilization_pct'] >= 75 ? 'high' : ($campus['utilization_pct'] >= 40 ? 'med' : 'low') }}"
                         style="width:{{ max(4, $campus['utilization_pct']) }}%"></div>
                </div>
                <div class="d-flex justify-content-between mt-2 small text-muted">
                    <span>{{ $campus['resource_count'] }} facilities</span>
                    <span>{{ $campus['bookings_count'] }} bookings</span>
                </div>
                <div class="mt-2">
                    <span class="rpt-campus-rec bg-{{ $campus['tone'] }}-subtle text-{{ $campus['tone'] }} border border-{{ $campus['tone'] }}-subtle">
                        {{ $campus['recommendation'] }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Resource Utilization Table --}}
        <div class="rpt-card">
            <div class="rpt-card-head">
                <div>
                    <div class="rpt-card-title"><i class="ti ti-adjustments-check text-primary"></i> Resource Capacity & Action Matrix</div>
                    <div class="rpt-card-desc">Which facilities need procurement, reallocation, or are running optimally?</div>
                </div>
                <a href="{{ route('reports.export', array_merge(request()->query(), ['export_type'=>'utilization'])) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-download me-1"></i> Export CSV
                </a>
            </div>
            <div class="table-responsive">
                <table class="rpt-tbl">
                    <thead>
                        <tr>
                            <th>Facility / Asset</th>
                            <th>Campus</th>
                            <th>Category</th>
                            <th>Bookings</th>
                            <th>Hours Booked</th>
                            <th>Utilization</th>
                            <th>Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['resource_utilization'] as $res)
                        <tr>
                            <td>
                                <strong>{{ $res['name'] }}</strong>
                                @if($res['serial_number'])<span class="text-muted small ms-1">({{ $res['serial_number'] }})</span>@endif
                            </td>
                            <td><span class="small text-muted"><i class="ti ti-map-pin me-1"></i>{{ $res['location'] }}</span></td>
                            <td><span class="small">{{ $res['category'] }}</span></td>
                            <td><strong>{{ $res['total_bookings'] }}</strong></td>
                            <td>{{ $res['booked_hours'] }} <span class="text-muted small">/ {{ $res['capacity_hours'] }} h</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rpt-bar-wrap">
                                        <div class="rpt-bar-fill {{ $res['utilization_pct'] >= 70 ? 'high' : ($res['utilization_pct'] >= 35 ? 'med' : 'low') }}"
                                             style="width:{{ max(4, (int)$res['utilization_pct']) }}%"></div>
                                    </div>
                                    <strong class="small">{{ $res['utilization_pct'] }}%</strong>
                                </div>
                            </td>
                            <td><span class="rpt-dec-badge {{ $res['decision_badge'] }}">{{ $res['decision_recommendation'] }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4"><i class="ti ti-folder-x me-1"></i> No resources found for this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Peak Hours --}}
        <div class="rpt-card">
            <div class="rpt-card-head">
                <div>
                    <div class="rpt-card-title"><i class="ti ti-clock text-warning"></i> Hourly Congestion Heatmap</div>
                    <div class="rpt-card-desc">Identify which time slots are overloaded and which have spare capacity to absorb shifted sessions.</div>
                </div>
            </div>
            <div class="rpt-card-body">
                @foreach($data['peak_hours'] as $slot)
                <div class="rpt-slot-row">
                    <div class="small fw-semibold">{{ $slot['slot'] }}</div>
                    <div class="rpt-slot-track">
                        <div class="rpt-slot-fill" style="width:{{ $slot['pct'] }}%; background:var(--bs-{{ $slot['tone'] }},#1769c2);"></div>
                    </div>
                    <div class="small fw-bold text-end">{{ $slot['count'] }}</div>
                    <div><span class="rpt-slot-label bg-{{ $slot['tone'] }}-subtle text-{{ $slot['tone'] }}">{{ $slot['status'] }}</span></div>
                </div>
                @endforeach
            </div>
        </div>

    </div>{{-- end facility pane --}}

    {{-- ═══════════════════════════════════════════════════════════════════════
         TAB 2 – BOOKINGS & APPROVAL HEALTH
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="rpt-tab-pane" id="pane-bookings">

        {{-- Alerts --}}
        @if($data['approval_health']['policy_alert'])
        <div class="rpt-alert-strip">
            <div class="rpt-alert-item warning">
                <i class="ti ti-alert-triangle rpt-alert-icon"></i>
                <div>{{ $data['approval_health']['policy_alert'] }}</div>
            </div>
        </div>
        @endif

        <div class="row g-3 mb-3">
            {{-- Turnaround metrics --}}
            <div class="col-md-4">
                <div class="rpt-card h-100">
                    <div class="rpt-card-head"><div class="rpt-card-title"><i class="ti ti-hourglass-low text-warning"></i> Approval Metrics</div></div>
                    <div class="rpt-card-body">
                        <div class="row g-2 text-center">
                            <div class="col-6">
                                <div class="p-3 rounded bg-light border">
                                    <div class="text-muted small">Avg Turnaround</div>
                                    <div class="h3 fw-bold text-primary mb-0">{{ $data['approval_health']['avg_turnaround_hrs'] }}h</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded bg-light border">
                                    <div class="text-muted small">Pending</div>
                                    <div class="h3 fw-bold text-warning mb-0">{{ $data['approval_health']['pending_count'] }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded bg-light border">
                                    <div class="text-muted small">Overdue &gt;24h</div>
                                    <div class="h3 fw-bold text-danger mb-0">{{ $data['approval_health']['stale_pending'] }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded bg-light border">
                                    <div class="text-muted small">Approval Rate</div>
                                    <div class="h3 fw-bold text-success mb-0">{{ $data['summary']['approval_rate'] }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cancellation causes --}}
            <div class="col-md-4">
                <div class="rpt-card h-100">
                    <div class="rpt-card-head"><div class="rpt-card-title"><i class="ti ti-shield-alert text-danger"></i> Cancellation Causes</div></div>
                    <div class="rpt-card-body p-0">
                        <table class="rpt-tbl">
                            <thead><tr><th>Reason</th><th class="text-end">Count</th></tr></thead>
                            <tbody>
                                @foreach($data['approval_health']['cancellation_causes'] as $cause)
                                <tr>
                                    <td><i class="ti {{ $cause['icon'] }} me-1 text-{{ $cause['tone'] }}"></i>{{ $cause['label'] }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $cause['tone'] }}-subtle text-{{ $cause['tone'] }}">{{ $cause['count'] }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Category breakdown --}}
            <div class="col-md-4">
                <div class="rpt-card h-100">
                    <div class="rpt-card-head"><div class="rpt-card-title"><i class="ti ti-chart-donut text-purple"></i> Bookings by Category</div></div>
                    <div class="rpt-card-body">
                        <canvas id="catChart" height="180"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Booking trend --}}
        <div class="rpt-card">
            <div class="rpt-card-head">
                <div class="rpt-card-title"><i class="ti ti-chart-line text-primary"></i> Booking Volume Trend</div>
            </div>
            <div class="rpt-card-body">
                <canvas id="trendChart" height="90"></canvas>
            </div>
        </div>

    </div>{{-- end bookings pane --}}

    {{-- ═══════════════════════════════════════════════════════════════════════
         TAB 3 – FINANCIAL & LECTURER FEES
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="rpt-tab-pane" id="pane-financial">

        <div class="rpt-alert-strip mb-3">
            @foreach($data['financial']['alerts'] as $a)
            <div class="rpt-alert-item {{ $a['type'] }}">
                <i class="ti {{ $a['icon'] }} rpt-alert-icon"></i>
                <div>{{ $a['msg'] }}</div>
            </div>
            @endforeach
        </div>

        <div class="rpt-kpi-grid mb-3" style="grid-template-columns:repeat(auto-fill,minmax(170px,1fr))">
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Total Payable</div>
                <div class="rpt-kpi-value small" style="font-size:1.4rem;color:#152238">LKR {{ number_format($data['financial']['total_payable_lkr'],2) }}</div>
                <div class="rpt-kpi-sub">{{ $data['financial']['payable_count'] }} records</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Paid</div>
                <div class="rpt-kpi-value small" style="font-size:1.4rem;color:#16a77a">LKR {{ number_format($data['financial']['total_paid_lkr'],2) }}</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Unpaid / Pending</div>
                <div class="rpt-kpi-value small" style="font-size:1.4rem;color:#dc3545">LKR {{ number_format($data['financial']['total_unpaid_lkr'],2) }}</div>
                <div class="rpt-kpi-sub">{{ $data['financial']['unpaid_count'] }} payables</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Lecture Sessions</div>
                <div class="rpt-kpi-value" style="color:#7c3aed">{{ $data['financial']['total_sessions'] }}</div>
                <div class="rpt-kpi-sub">{{ $data['financial']['total_hours'] }} hrs delivered</div>
            </div>
        </div>

        <div class="rpt-card">
            <div class="rpt-card-head">
                <div class="rpt-card-title"><i class="ti ti-users text-primary"></i> Lecturer Fee Breakdown</div>
            </div>
            <div class="table-responsive">
                <table class="rpt-tbl">
                    <thead>
                        <tr>
                            <th>Lecturer</th>
                            <th>Sessions</th>
                            <th>Hours</th>
                            <th>Total (LKR)</th>
                            <th>Paid (LKR)</th>
                            <th>Unpaid (LKR)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['financial']['by_lecturer'] as $lec)
                        <tr>
                            <td><strong>{{ $lec['name'] }}</strong></td>
                            <td>{{ $lec['sessions'] }}</td>
                            <td>{{ $lec['total_hours'] }}</td>
                            <td>{{ number_format($lec['total_lkr'],2) }}</td>
                            <td class="text-success fw-semibold">{{ number_format($lec['paid_lkr'],2) }}</td>
                            <td class="{{ $lec['unpaid_lkr'] > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ number_format($lec['unpaid_lkr'],2) }}</td>
                            <td>
                                @if($lec['status'] === 'all_paid')
                                    <span class="badge bg-success-subtle text-success">All Settled</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Has Unpaid</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4"><i class="ti ti-folder-x me-1"></i> No lecturer payable records for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>{{-- end financial pane --}}

    {{-- ═══════════════════════════════════════════════════════════════════════
         TAB 4 – HOSTEL
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="rpt-tab-pane" id="pane-hostel">

        <div class="rpt-alert-strip mb-3">
            @foreach($data['hostel']['alerts'] as $a)
            <div class="rpt-alert-item {{ $a['type'] }}">
                <i class="ti {{ $a['icon'] }} rpt-alert-icon"></i>
                <div>{{ $a['msg'] }}</div>
            </div>
            @endforeach
        </div>

        <div class="rpt-kpi-grid mb-3" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr))">
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Currently Checked In</div>
                <div class="rpt-kpi-value" style="color:#1769c2">{{ $data['hostel']['checked_in'] }}</div>
                <div class="rpt-kpi-sub">guests in residence</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Checked Out</div>
                <div class="rpt-kpi-value" style="color:#16a77a">{{ $data['hostel']['checked_out'] }}</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Upcoming</div>
                <div class="rpt-kpi-value" style="color:#f59e0b">{{ $data['hostel']['upcoming'] }}</div>
                <div class="rpt-kpi-sub">pending check-in</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Total Guests</div>
                <div class="rpt-kpi-value">{{ $data['hostel']['total_guests'] }}</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Avg Stay</div>
                <div class="rpt-kpi-value" style="color:#7c3aed">{{ $data['hostel']['avg_stay_days'] }}</div>
                <div class="rpt-kpi-sub">days</div>
            </div>
        </div>

        <div class="rpt-card">
            <div class="rpt-card-head">
                <div class="rpt-card-title"><i class="ti ti-category text-primary"></i> Stays by Room Category</div>
            </div>
            <div class="table-responsive">
                <table class="rpt-tbl">
                    <thead>
                        <tr><th>Room Category</th><th>Total Stays</th><th>Total Guests</th></tr>
                    </thead>
                    <tbody>
                        @forelse($data['hostel']['by_category'] as $row)
                        <tr>
                            <td><strong>{{ $row['category'] }}</strong></td>
                            <td>{{ $row['total_stays'] }}</td>
                            <td>{{ $row['guests'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4"><i class="ti ti-folder-x me-1"></i> No hostel stay records for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>{{-- end hostel pane --}}

    {{-- ═══════════════════════════════════════════════════════════════════════
         TAB 5 – CANTEEN
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="rpt-tab-pane" id="pane-canteen">

        <div class="rpt-alert-strip mb-3">
            @foreach($data['canteen']['alerts'] as $a)
            <div class="rpt-alert-item {{ $a['type'] }}">
                <i class="ti {{ $a['icon'] }} rpt-alert-icon"></i>
                <div>{{ $a['msg'] }}</div>
            </div>
            @endforeach
        </div>

        <div class="rpt-kpi-grid mb-3" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr))">
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Total Orders</div>
                <div class="rpt-kpi-value" style="color:#d97706">{{ $data['canteen']['total_orders'] }}</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Total Meals</div>
                <div class="rpt-kpi-value">{{ number_format($data['canteen']['total_meals']) }}</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Confirmed</div>
                <div class="rpt-kpi-value" style="color:#16a77a">{{ $data['canteen']['confirmed'] }}</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Pending Review</div>
                <div class="rpt-kpi-value" style="color:#f59e0b">{{ $data['canteen']['pending'] }}</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Cancelled</div>
                <div class="rpt-kpi-value" style="color:#dc3545">{{ $data['canteen']['cancelled'] }}</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Daily Avg Meals</div>
                <div class="rpt-kpi-value">{{ $data['canteen']['daily_avg'] }}</div>
            </div>
            <div class="rpt-kpi">
                <div class="rpt-kpi-label">Large Group Orders</div>
                <div class="rpt-kpi-value" style="color:#7c3aed">{{ $data['canteen']['large_groups'] }}</div>
                <div class="rpt-kpi-sub">&ge;50 pax orders</div>
            </div>
        </div>

        <div class="rpt-card">
            <div class="rpt-card-head">
                <div class="rpt-card-title"><i class="ti ti-soup text-warning"></i> Orders by Meal Type</div>
            </div>
            <div class="table-responsive">
                <table class="rpt-tbl">
                    <thead>
                        <tr><th>Meal Type</th><th>Orders</th><th>Total Meals</th></tr>
                    </thead>
                    <tbody>
                        @forelse($data['canteen']['by_meal_type'] as $row)
                        <tr>
                            <td><strong>{{ $row['type'] }}</strong></td>
                            <td>{{ $row['orders'] }}</td>
                            <td>{{ number_format($row['meals']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4"><i class="ti ti-folder-x me-1"></i> No canteen orders for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>{{-- end canteen pane --}}

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Tab switching ────────────────────────────────────────────────────────────
document.querySelectorAll('.rpt-tab').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.rpt-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.rpt-tab-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('pane-' + this.dataset.tab).classList.add('active');
    });
});

// ── Date presets ─────────────────────────────────────────────────────────────
const fromInput = document.querySelector('[name="from_date"]');
const toInput   = document.querySelector('[name="to_date"]');
const fmt = d => d.toISOString().slice(0, 10);
document.querySelectorAll('.rpt-preset-pill').forEach(pill => {
    pill.addEventListener('click', function () {
        const today = new Date();
        let from = new Date();
        switch (this.dataset.preset) {
            case 'today':   from = new Date(); break;
            case 'week':    from = new Date(today); from.setDate(today.getDate() - today.getDay() + 1); break;
            case 'month':   from = new Date(today.getFullYear(), today.getMonth(), 1); break;
            case 'last30':  from = new Date(today); from.setDate(today.getDate() - 29); break;
            case 'quarter': const q = Math.floor(today.getMonth() / 3); from = new Date(today.getFullYear(), q * 3, 1); break;
        }
        fromInput.value = fmt(from);
        toInput.value   = fmt(today);
        document.getElementById('rpt-filter-form').submit();
    });
});

// ── Booking trend chart ──────────────────────────────────────────────────────
const trendCtx = document.getElementById('trendChart');
if (trendCtx) {
    new Chart(trendCtx.getContext('2d'), {
        type: 'line',
        data: {
            labels: @json($data['booking_trend']['labels']),
            datasets: [
                {
                    label: 'Approved',
                    data: @json($data['booking_trend']['approved']),
                    borderColor: '#16a77a',
                    backgroundColor: 'rgba(22,167,122,.1)',
                    fill: true,
                    tension: .35,
                    pointRadius: 3,
                },
                {
                    label: 'Cancelled / Rejected',
                    data: @json($data['booking_trend']['cancelled']),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,.08)',
                    fill: true,
                    tension: .35,
                    pointRadius: 3,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
}

// ── Category doughnut chart ──────────────────────────────────────────────────
const catCtx = document.getElementById('catChart');
if (catCtx) {
    new Chart(catCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: @json($data['category_breakdown']['labels']),
            datasets: [{
                data: @json($data['category_breakdown']['series']),
                backgroundColor: @json($data['category_breakdown']['colors']),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            cutout: '60%',
        }
    });
}
</script>
@endpush
