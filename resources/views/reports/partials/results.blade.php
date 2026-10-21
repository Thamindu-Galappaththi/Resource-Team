{{-- KPI STRIP --}}
<div class="rpt-kpi-grid">
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
            <div class="rpt-kpi-bar"><div class="rpt-kpi-fill is-empty"></div></div>
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
            <div class="rpt-kpi-bar"><div class="rpt-kpi-fill is-empty"></div></div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Pending Approvals</div>
            <div class="rpt-kpi-value" style="color:{{ $data['approval_health']['stale_pending'] > 3 ? '#dc3545' : '#f59e0b' }}">
                {{ $data['approval_health']['pending_count'] }}
            </div>
            <div class="rpt-kpi-sub">{{ $data['approval_health']['stale_pending'] }} overdue &gt;24h &bull; avg {{ $data['approval_health']['avg_turnaround_hrs'] }}h turnaround</div>
            <div class="rpt-kpi-bar"><div class="rpt-kpi-fill is-empty"></div></div>
        </div>
</div>

<div class="rpt-tabbar" id="rptTabBar">
    <button type="button" class="rpt-tab active" data-tab="facility"><i class="ti ti-building-community me-1"></i><span>Facility Utilization</span></button>
        <button type="button" class="rpt-tab" data-tab="bookings"><i class="ti ti-calendar-event me-1"></i><span>Bookings &amp; Approval Health</span></button>
    <button type="button" class="rpt-tab" data-tab="financial"><i class="ti ti-currency-dollar me-1"></i><span>Finance Summary</span></button>
    <button type="button" class="rpt-tab" data-tab="hostel"><i class="ti ti-home me-1"></i><span>Hostel Summary</span></button>
    <button type="button" class="rpt-tab" data-tab="canteen"><i class="ti ti-chef-hat me-1"></i><span>Canteen Summary</span></button>
</div>

<div class="rpt-tab-pane active" id="pane-facility">
    <div class="rpt-campus-grid mb-4">
        @forelse($data['campus_comparison'] as $campus)
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
                <div class="rpt-campus-rec-wrap">
                    <span class="rpt-campus-rec bg-{{ $campus['tone'] }}-subtle text-{{ $campus['tone'] }} border border-{{ $campus['tone'] }}-subtle">
                        {{ $campus['recommendation'] }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rpt-empty"><i class="ti ti-map-pin"></i><p class="mb-0">No location data for this filter.</p></div>
        @endforelse
    </div>

    <div class="rpt-card">
        <div class="rpt-card-head">
            <div>
                <div class="rpt-card-title"><i class="ti ti-adjustments text-primary"></i> Resource Capacity &amp; Action Matrix</div>
                <div class="rpt-card-desc">Which facilities need procurement, reallocation, or are running optimally?</div>
            </div>
            <a href="{{ $export['utilization'] ?? route('reports.export', ['export_type' => 'utilization']) }}" class="btn btn-sm btn-outline-primary rpt-export-link" data-export="utilization">
                <i class="ti ti-download me-1"></i> Export CSV
            </a>
        </div>
        <div class="table-responsive">
            <table class="rpt-tbl">
                <thead>
                    <tr>
                        <th>Facility / Asset</th>
                            <th>Location</th>
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
                            <td data-label="Facility / Asset">
                                <strong>{{ $res['name'] }}</strong>
                                @if($res['serial_number'])<span class="text-muted small ms-1">({{ $res['serial_number'] }})</span>@endif
                            </td>
                            <td data-label="Location"><span class="small text-muted"><i class="ti ti-map-pin me-1"></i>{{ $res['location'] }}</span></td>
                            <td data-label="Category"><span class="small">{{ $res['category'] }}</span></td>
                            <td data-label="Bookings"><strong>{{ $res['total_bookings'] }}</strong></td>
                            <td data-label="Hours Booked">{{ $res['booked_hours'] }} <span class="text-muted small">/ {{ $res['capacity_hours'] }} h</span></td>
                            <td data-label="Utilization">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rpt-bar-wrap">
                                        <div class="rpt-bar-fill {{ $res['utilization_pct'] >= 70 ? 'high' : ($res['utilization_pct'] >= 35 ? 'med' : 'low') }}"
                                             style="width:{{ max(4, (int) $res['utilization_pct']) }}%"></div>
                                    </div>
                                    <strong class="small">{{ $res['utilization_pct'] }}%</strong>
                                </div>
                            </td>
                            <td data-label="Decision"><span class="rpt-dec-badge {{ $res['decision_badge'] }}">{{ $res['decision_recommendation'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4"><i class="ti ti-folder-x me-1"></i> No resources found for this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="rpt-card-body pt-0">
            @include('reports.partials.pager', ['pager' => $data['utilization_pager'] ?? []])
        </div>
    </div>

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
</div>

<div class="rpt-tab-pane" id="pane-bookings">
    @if($data['approval_health']['policy_alert'])
        <div class="rpt-alert-strip">
            <div class="rpt-alert-item warning">
                <i class="ti ti-alert-triangle rpt-alert-icon"></i>
                <div>{{ $data['approval_health']['policy_alert'] }}</div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
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

        <div class="col-md-4">
            <div class="rpt-card h-100">
                <div class="rpt-card-head"><div class="rpt-card-title"><i class="ti ti-alert-triangle text-danger"></i> Cancellation Causes</div></div>
                <div class="rpt-card-body p-0">
                    <table class="rpt-tbl">
                        <thead><tr><th>Reason</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                            @foreach($data['approval_health']['cancellation_causes'] as $cause)
                                <tr>
                                    <td data-label="Reason"><i class="ti {{ $cause['icon'] }} me-1 text-{{ $cause['tone'] }}"></i>{{ $cause['label'] }}</td>
                                    <td data-label="Count" class="text-end">
                                        <span class="badge bg-{{ $cause['tone'] }}-subtle text-{{ $cause['tone'] }}">{{ $cause['count'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="rpt-card h-100">
                <div class="rpt-card-head"><div class="rpt-card-title"><i class="ti ti-chart-donut text-primary"></i> Bookings by Category</div></div>
                <div class="rpt-card-body">
                    <canvas id="catChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="rpt-card">
        <div class="rpt-card-head">
            <div class="rpt-card-title"><i class="ti ti-chart-line text-primary"></i> Booking Volume Trend</div>
        </div>
        <div class="rpt-card-body">
            <canvas id="trendChart" height="90"></canvas>
        </div>
    </div>

    <div class="rpt-card">
        <div class="rpt-card-head">
            <div>
                <div class="rpt-card-title"><i class="ti ti-list-check text-primary"></i> Reservation log</div>
                <div class="rpt-card-desc">Filtered reservations for the selected period.</div>
            </div>
            <a href="{{ $export['reservations'] ?? route('reports.export', ['export_type' => 'reservations']) }}" class="btn btn-sm btn-outline-primary rpt-export-link" data-export="reservations">
                <i class="ti ti-download me-1"></i> Export CSV
            </a>
        </div>
        <div class="table-responsive">
            <table class="rpt-tbl">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Purpose</th>
                        <th>Requester</th>
                        <th>Schedule</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['reservation_rows'] as $row)
                        <tr>
                            <td data-label="Reference"><strong>{{ $row['reference'] }}</strong></td>
                            <td data-label="Purpose">{{ $row['purpose'] }}</td>
                            <td data-label="Requester">{{ $row['requester'] }}</td>
                            <td data-label="Schedule">{{ $row['date'] }} <span class="text-muted small">{{ $row['time'] }}</span></td>
                            <td data-label="Location">{{ $row['location'] }}</td>
                            <td data-label="Status"><span class="badge {{ $row['status_badge'] }}">{{ $row['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4"><i class="ti ti-folder-x me-1"></i> No reservations for this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="rpt-card-body pt-0">
            @include('reports.partials.pager', ['pager' => $data['reservation_pager'] ?? []])
        </div>
    </div>
</div>

<div class="rpt-tab-pane" id="pane-financial">
    <div class="rpt-alert-strip mb-3">
        @foreach($data['financial']['alerts'] as $a)
            <div class="rpt-alert-item {{ $a['type'] }}">
                <i class="ti {{ $a['icon'] }} rpt-alert-icon"></i>
                <div>{{ $a['msg'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="rpt-kpi-grid rpt-kpi-grid-tight mb-3">
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Total Lecture Fees</div>
            <div class="rpt-kpi-value rpt-kpi-money">LKR {{ number_format($data['financial']['total_payable_lkr'], 2) }}</div>
            <div class="rpt-kpi-sub">{{ $data['financial']['payable_count'] }} records</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Paid</div>
            <div class="rpt-kpi-value rpt-kpi-money" style="color:#16a77a">LKR {{ number_format($data['financial']['total_paid_lkr'], 2) }}</div>
            <div class="rpt-kpi-sub">settled this period</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Unpaid / Pending</div>
            <div class="rpt-kpi-value rpt-kpi-money" style="color:#dc3545">LKR {{ number_format($data['financial']['total_unpaid_lkr'], 2) }}</div>
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
            <a href="{{ $export['lecturers'] ?? route('reports.export', ['export_type' => 'lecturers']) }}" class="btn btn-sm btn-outline-primary rpt-export-link" data-export="lecturers">
                <i class="ti ti-download me-1"></i> Export CSV
            </a>
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
                            <td data-label="Lecturer"><strong>{{ $lec['name'] }}</strong></td>
                            <td data-label="Sessions">{{ $lec['sessions'] }}</td>
                            <td data-label="Hours">{{ $lec['total_hours'] }}</td>
                            <td data-label="Total (LKR)">{{ number_format($lec['total_lkr'], 2) }}</td>
                            <td data-label="Paid (LKR)" class="text-success fw-semibold">{{ number_format($lec['paid_lkr'], 2) }}</td>
                            <td data-label="Unpaid (LKR)" class="{{ $lec['unpaid_lkr'] > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ number_format($lec['unpaid_lkr'], 2) }}</td>
                            <td data-label="Status">
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
        <div class="rpt-card-body pt-0">
            @include('reports.partials.pager', ['pager' => $data['financial']['lecturer_pager'] ?? []])
        </div>
    </div>
</div>

<div class="rpt-tab-pane" id="pane-hostel">
    <div class="rpt-alert-strip mb-3">
        @foreach($data['hostel']['alerts'] as $a)
            <div class="rpt-alert-item {{ $a['type'] }}">
                <i class="ti {{ $a['icon'] }} rpt-alert-icon"></i>
                <div>{{ $a['msg'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="rpt-kpi-grid rpt-kpi-grid-tight mb-3">
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Currently Checked In</div>
            <div class="rpt-kpi-value" style="color:#1769c2">{{ $data['hostel']['checked_in'] }}</div>
            <div class="rpt-kpi-sub">guests in residence</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Checked Out</div>
            <div class="rpt-kpi-value" style="color:#16a77a">{{ $data['hostel']['checked_out'] }}</div>
            <div class="rpt-kpi-sub">completed stays</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Upcoming</div>
            <div class="rpt-kpi-value" style="color:#f59e0b">{{ $data['hostel']['upcoming'] }}</div>
            <div class="rpt-kpi-sub">pending check-in</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Total Guests</div>
            <div class="rpt-kpi-value">{{ $data['hostel']['total_guests'] }}</div>
            <div class="rpt-kpi-sub">across all stays</div>
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
                            <td data-label="Room Category"><strong>{{ $row['category'] }}</strong></td>
                            <td data-label="Total Stays">{{ $row['total_stays'] }}</td>
                            <td data-label="Total Guests">{{ $row['guests'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4"><i class="ti ti-folder-x me-1"></i> No hostel stay records for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="rpt-tab-pane" id="pane-canteen">
    <div class="rpt-alert-strip mb-3">
        @foreach($data['canteen']['alerts'] as $a)
            <div class="rpt-alert-item {{ $a['type'] }}">
                <i class="ti {{ $a['icon'] }} rpt-alert-icon"></i>
                <div>{{ $a['msg'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="rpt-kpi-grid rpt-kpi-grid-tight mb-3">
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Total Orders</div>
            <div class="rpt-kpi-value" style="color:#d97706">{{ $data['canteen']['total_orders'] }}</div>
            <div class="rpt-kpi-sub">in this period</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Total Meals</div>
            <div class="rpt-kpi-value">{{ number_format($data['canteen']['total_meals']) }}</div>
            <div class="rpt-kpi-sub">meals requested</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Confirmed</div>
            <div class="rpt-kpi-value" style="color:#16a77a">{{ $data['canteen']['confirmed'] }}</div>
            <div class="rpt-kpi-sub">kitchen confirmed</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Pending Review</div>
            <div class="rpt-kpi-value" style="color:#f59e0b">{{ $data['canteen']['pending'] }}</div>
            <div class="rpt-kpi-sub">awaiting confirmation</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Cancelled</div>
            <div class="rpt-kpi-value" style="color:#dc3545">{{ $data['canteen']['cancelled'] }}</div>
            <div class="rpt-kpi-sub">cancelled or rejected</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Daily Avg Meals</div>
            <div class="rpt-kpi-value">{{ $data['canteen']['daily_avg'] }}</div>
            <div class="rpt-kpi-sub">per day</div>
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
                            <td data-label="Meal Type"><strong>{{ $row['type'] }}</strong></td>
                            <td data-label="Orders">{{ $row['orders'] }}</td>
                            <td data-label="Total Meals">{{ number_format($row['meals']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4"><i class="ti ti-folder-x me-1"></i> No canteen orders for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
