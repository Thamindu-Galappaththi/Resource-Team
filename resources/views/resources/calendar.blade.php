{{--
    Resource Calendar – BRD aligned UI
    Route: /resources/calendar
--}}
@extends('layouts.app')

@section('content')
<div class="rc-page">

    {{-- Header --}}
    <div class="rc-header">
        <div class="rc-header-left">
            <h1 class="rc-title">Resource Calendar</h1>
            <p class="rc-subtitle">
                View and manage resource reservations and schedules
            </p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="rc-cards">
        <div class="rc-card">
            <div class="rc-card-label">My Resources</div>
            <div class="rc-card-value">{{ $stats['myResources'] }}</div>
        </div>
        <div class="rc-card">
            <div class="rc-card-label">Today's Bookings</div>
            <div class="rc-card-value">{{ $stats['todayBookings'] }}</div>
        </div>
        <div class="rc-card">
            <div class="rc-card-label">Pending Approvals</div>
            <div class="rc-card-value">{{ $stats['pendingApprovals'] }}</div>
        </div>
        <div class="rc-card">
            <div class="rc-card-label">This Month</div>
            <div class="rc-card-value">{{ $stats['thisMonth'] }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rc-filters">
        <div class="rc-filter">
            <label>Resource</label>
            <select id="resourceFilter">
                <option value="all">All Resources</option>
                @foreach($resources as $resource)
                    <option value="{{ $resource->id }}">{{ $resource->name_model }}</option>
                @endforeach
            </select>
        </div>

        <div class="rc-filter">
            <label>Location</label>
            <select id="locationFilter">
                <option value="all">All Locations</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="rc-filter">
            <label>Status</label>
            <select id="statusFilter">
                <option value="all">All Statuses</option>
                <option value="approved">Approved</option>
                <option value="pending">Pending</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <div class="rc-filter">
            <label>Category</label>
            <select id="categoryFilter">
                <option value="all">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="rc-filter-actions">
            <button type="button" id="applyFiltersButton" class="rc-filter-button rc-filter-apply">Apply Filter</button>
            <button type="button" id="clearFiltersButton" class="rc-filter-button rc-filter-clear">Clear Filter</button>
        </div>
    </div>

    <div class="rc-view-switch" role="tablist" aria-label="Calendar layout">
        <button type="button" class="active" id="calendarViewButton" role="tab" aria-selected="true">Calendar</button>
        <button type="button" id="schedulerViewButton" role="tab" aria-selected="false">Timeline Scheduler</button>
    </div>

    {{-- Calendar --}}
    <div class="rc-calendar-card">
        <div id="calendarLoading" class="rc-loading" role="status">Loading reservations...</div>
        <div id="calendarEmpty" class="rc-empty" hidden>No reservations match your filters.</div>
        <div id="resourceCalendar"></div>
    </div>

    <section id="schedulerPanel" class="rc-calendar-card rc-scheduler" hidden>
        <div class="rc-scheduler-heading">
            <div>
                <h2>Resource Timeline</h2>
                <p>Select resources, then drag across open hours to prepare a reservation. Availability is checked again before submission.</p>
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
        <div class="rc-scheduler-note">Selecting an open slot opens the reservation form; final booking and conflict checks follow the existing approval process.</div>
    </section>

    {{-- Upcoming Reservations Table --}}
    <div class="rc-table-card">
        <h3 class="rc-table-title">Upcoming Reservations</h3>
        <div class="rc-table-wrap">
            <table class="rc-table">
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reserved By</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($calendarEvents as $event)
                        <tr>
                            <td>{{ $event['title'] }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($event['start'])->format('d M Y') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($event['start'])->format('h:i A') }} - {{ \Illuminate\Support\Carbon::parse($event['end'])->format('h:i A') }}</td>
                            <td>{{ $event['extendedProps']['owner'] ?: '—' }}</td>
                            <td>{{ $event['extendedProps']['status'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No reservations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Reservation Details Modal --}}
<div id="reservationModal" class="rc-modal" aria-hidden="true">
    <div class="rc-modal-overlay"></div>
    <div class="rc-modal-box">
        <button type="button" id="closeReservationModal" class="rc-modal-close">
            &times;
        </button>

        <div class="rc-modal-header">
            <div class="rc-modal-icon">
                <i class="ti ti-calendar-event"></i>
            </div>
            <div>
                <h2>Reservation Details</h2>
                <p>Booking information</p>
            </div>
        </div>

        <div class="rc-modal-body">
            <div class="rc-detail-row">
                <div class="rc-detail-label">Resource</div>
                <div id="detailResource" class="rc-detail-value">-</div>
            </div>

            <div class="rc-detail-row">
                <div class="rc-detail-label">Date</div>
                <div id="detailDate" class="rc-detail-value">-</div>
            </div>

            <div class="rc-detail-row">
                <div class="rc-detail-label">Booking Time</div>
                <div id="detailTime" class="rc-detail-value">-</div>
            </div>

            <div class="rc-detail-row">
                <div class="rc-detail-label">Reserved By</div>
                <div id="detailOwner" class="rc-detail-value">-</div>
            </div>

            <div class="rc-detail-row">
                <div class="rc-detail-label">Location</div>
                <div id="detailLocation" class="rc-detail-value">-</div>
            </div>

            <div class="rc-detail-row">
                <div class="rc-detail-label">Status</div>
                <div id="detailStatus" class="rc-detail-value">-</div>
            </div>
        </div>
    </div>
</div>

{{-- Inline CSS (no external files needed) --}}
<style>
.rc-page {
    padding: 35px 25px 50px;
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
    color: #111827;
    background: transparent;
    min-height: 100vh;
}

/* Header */
.rc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}
.rc-header-left {
    max-width: 720px;
}
.rc-title {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    animation: rc-calendar-in .25s ease both;
}
.rc-subtitle {
    margin: 6px 0 0;
    font-size: 14px;
    color: #6b7280;
}

/* Cards */
.rc-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}
.rc-card {
    background: rgba(255,255,255,.82);
    border: 1px solid rgba(235,238,244,.8);
    border-radius: 10px;
    padding: 16px;
    box-shadow: 0 8px 25px rgba(0,0,0,.08);
    transition: transform .22s ease, box-shadow .22s ease;
}
.rc-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(23,105,232,.12); }
.rc-card-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.rc-card-value {
    margin-top: 8px;
    font-size: 26px;
    font-weight: 900;
    color: #0f172a;
}

/* Filters */
.rc-filters {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 18px;
    background: rgba(255,255,255,.78);
    border: 1px solid rgba(235,238,244,.8);
    border-radius: 10px;
    padding: 14px 16px;
    box-shadow: 0 8px 25px rgba(0,0,0,.06);
}
.rc-filter {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.rc-filter label {
    font-size: 12px;
    color: #374151;
    font-weight: 700;
}
.rc-filter select {
    min-width: 170px;
    height: 38px;
    padding: 0 32px 0 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #fff;
    color: #111827;
    font-size: 13px;
    outline: none;
}
.rc-filter select:focus {
    border-color: #1769e8;
    box-shadow: 0 0 0 3px rgba(23,105,232,.14);
}
.rc-view-switch { display: inline-flex; gap: 4px; padding: 4px; margin: 0 0 12px; border: 1px solid #dbe5f6; border-radius: 9px; background: rgba(255,255,255,.78); }
.rc-view-switch button { border: 0; border-radius: 7px; padding: 8px 14px; color: #1769d1; background: transparent; font-weight: 600; transition: color .2s ease, background .2s ease, transform .2s ease; }
.rc-view-switch button:hover { transform: translateY(-1px); }
.rc-view-switch button.active { color: #fff; background: #1769d1; box-shadow: 0 3px 9px rgba(23,105,232,.2); }

/* Calendar card */
.rc-calendar-card {
    background: rgba(255,255,255,.78);
    border: 0;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 18px;
    box-shadow: 0 8px 25px rgba(0,0,0,.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    overflow: hidden;
}
.rc-loading, .rc-empty { padding: 24px; text-align: center; color: #6b7280; }
.rc-loading { animation: rc-pulse 1s ease-in-out infinite alternate; }
.rc-empty { border: 1px dashed #cbd5e1; border-radius: 8px; margin-bottom: 12px; }
.fc .fc-toolbar { padding: 5px 4px 18px; }
.fc .fc-toolbar-title { color: #1769e8; font-size: 19px; font-weight: 600; }
.fc .fc-button { min-height: 38px; border-radius: 8px !important; font-size: 13px !important; transition: background .2s ease, color .2s ease, transform .2s ease; }
.fc .fc-button-primary { color: #1769d1 !important; background: rgba(255,255,255,.35) !important; border: 1px solid #1769e8 !important; box-shadow: none !important; }
.fc .fc-button-primary:hover, .fc .fc-button-primary:not(:disabled).fc-button-active { color: #fff !important; background: #1769d1 !important; }
.fc .fc-col-header-cell { background: rgba(238,243,250,.8); }
.fc .fc-col-header-cell-cushion { padding: 12px 5px; color: #374151; text-decoration: none; }
.fc .fc-daygrid-day, .fc .fc-timegrid-col { background: rgba(255,255,255,.65); }
.fc .fc-daygrid-day-number { color: #374151; text-decoration: none; }
.fc .fc-day-today { background: rgba(23,105,232,.09) !important; }
.fc .fc-day-today .fc-daygrid-day-number { display: inline-flex; width: 30px; height: 30px; align-items: center; justify-content: center; margin: 4px; border-radius: 50%; background: #1769e8; color: #fff; font-weight: 700; }
.fc .fc-event { border: 0; border-radius: 6px; padding: 3px 5px; cursor: pointer; animation: rc-event-in .25s ease both; transition: transform .2s ease, box-shadow .2s ease; }
.fc .fc-event:hover { transform: translateY(-2px) scale(1.01); box-shadow: 0 4px 10px rgba(15,23,42,.18); }
.fc-view-transition { animation: rc-calendar-in .25s ease both; }
@keyframes rc-event-in { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: scale(1); } }
@keyframes rc-calendar-in { from { opacity: .65; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
@keyframes rc-pulse { to { opacity: .45; } }
#resourceCalendar {
    width: 100%;
}

/* Table card */
.rc-table-card {
    background: rgba(255,255,255,.82);
    border: 0;
    border-radius: 10px;
    padding: 16px;
    box-shadow: 0 8px 25px rgba(0,0,0,.08);
}
.rc-scheduler[hidden] { display: none; }
.rc-scheduler-heading { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin: 0 0 16px; }
.rc-scheduler-heading h2 { margin: 0; color: #1769d1; font-size: 20px; }
.rc-scheduler-heading p { margin: 5px 0 0; color: #6b7280; font-size: 13px; }
.rc-scheduler-date { display: grid; gap: 5px; color: #475569; font-size: 12px; font-weight: 700; }
.rc-scheduler-date input { min-height: 38px; padding: 6px 10px; border: 1px solid #dbe2ec; border-radius: 8px; }
.rc-scheduler-legend { display: flex; flex-wrap: wrap; gap: 14px; margin: 0 0 14px; color: #596275; font-size: 12px; }
.rc-scheduler-legend span { display: inline-flex; align-items: center; gap: 6px; }
.rc-legend-dot { width: 9px; height: 9px; border-radius: 50%; background: #16a34a; }
.rc-legend-dot.tentative { background: #d97706; }
.rc-legend-dot.maintenance { background: #64748b; }
.rc-legend-dot.overbooked { background: #dc2626; }
.rc-scheduler-scroll { overflow: auto; border: 1px solid #e5eaf2; border-radius: 9px; }
.rc-scheduler-track { display: grid; grid-template-columns: 220px repeat(12, minmax(58px, 1fr)); min-width: 920px; position: relative; }
.rc-scheduler-times, .rc-scheduler-resource, .rc-scheduler-slot { min-height: 62px; border-bottom: 1px solid #e8edf4; border-right: 1px solid #edf0f5; }
.rc-scheduler-times { display: flex; align-items: center; justify-content: center; background: #eef3fa; color: #526174; font-size: 11px; font-weight: 700; }
.rc-scheduler-resource { position: sticky; left: 0; z-index: 2; padding: 9px 11px; background: rgba(255,255,255,.97); }
.rc-scheduler-resource strong { display: block; color: #1e293b; font-size: 12px; }
.rc-resource-select { margin-right: 5px; accent-color: #1769e8; vertical-align: -1px; }
.rc-selection-count { margin-top: 6px; color: #1769d1; font-size: 11px; font-weight: 700; }
.rc-scheduler-resource small { display: block; overflow: hidden; color: #718096; font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
.rc-capacity { display: flex; align-items: center; gap: 6px; margin-top: 5px; }
.rc-capacity-track { height: 5px; flex: 1; overflow: hidden; border-radius: 9px; background: #e8edf4; }
.rc-capacity-track i { display: block; height: 100%; border-radius: inherit; background: #16a34a; transition: width .25s ease; }
.rc-capacity.near-capacity .rc-capacity-track i { background: #d97706; }
.rc-capacity.overloaded .rc-capacity-track i { background: #dc2626; }
.rc-capacity em { color: #64748b; font-size: 9px; font-style: normal; }
.rc-scheduler-slot { position: relative; cursor: crosshair; background: rgba(255,255,255,.62); transition: background .15s ease; }
.rc-scheduler-slot:hover, .rc-scheduler-slot.selected { background: rgba(23,105,232,.12); }
.rc-scheduler-slot:disabled { cursor: not-allowed; background: #f1f3f6; }
.rc-scheduler-slot.rc-maintenance-slot:disabled { background: repeating-linear-gradient(135deg, #f1f3f6, #f1f3f6 7px, #e5e9ef 7px, #e5e9ef 14px); }
.rc-scheduler-category { padding: 8px 11px; background: #f4f7fc; color: #1769d1; font-size: 12px; font-weight: 800; }
.rc-scheduler-type { grid-column: 1 / -1; padding: 6px 11px; background: rgba(238,243,250,.55); color: #64748b; font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.rc-scheduler-booking { position: absolute; z-index: 1; top: 9px; height: 42px; overflow: hidden; padding: 5px 7px; border: 0; border-radius: 6px; color: #fff; text-align: left; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; animation: rc-event-in .22s ease both; }
.rc-scheduler-booking.confirmed { background: #16a34a; }
.rc-scheduler-booking.tentative { background: #d97706; }
.rc-scheduler-booking.cancelled { background: #dc2626; }
.rc-scheduler-booking:hover { z-index: 4; filter: brightness(.94); transform: translateY(-2px); }
.rc-scheduler-booking.conflicted { outline: 2px solid #dc2626; outline-offset: 1px; background: #dc2626 !important; }
.rc-scheduler-popover { position: fixed; z-index: 100000; max-width: 280px; padding: 10px 12px; border: 1px solid rgba(255,255,255,.12); border-radius: 9px; background: #172b4d; box-shadow: 0 10px 28px rgba(15,23,42,.25); color: #fff; font-size: 11px; line-height: 1.5; white-space: pre-line; pointer-events: none; animation: rc-modal-in .18s ease both; }
.rc-scheduler-popover[hidden] { display: none; }
.rc-conflict-flag { position: absolute; z-index: 3; top: 2px; right: 3px; color: #dc2626; animation: rc-warning-pulse 1s ease-in-out infinite; }
.rc-overbooked-slot { background: rgba(220,38,38,.08); }
.rc-maintenance-label { position: absolute; top: 0; right: 0; z-index: 3; padding: 2px 7px; border-radius: 0 0 0 6px; background: #64748b; color: white; font-size: 9px; font-weight: 700; }
.rc-scheduler-note { margin-top: 12px; color: #718096; font-size: 12px; }
@keyframes rc-warning-pulse { 50% { opacity: .35; transform: scale(.85); } }
.rc-table-title {
    margin: 0 0 14px;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
}
.rc-table-wrap {
    overflow-x: auto;
}
.rc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.rc-table th,
.rc-table td {
    text-align: left;
    padding: 10px 12px;
    border-bottom: 1px solid #e5e7eb;
}
.rc-table th {
    font-weight: 800;
    color: #374151;
    background: #f9fafb;
}
.rc-table tbody tr:hover {
    background: #f9fafb;
}

/* Badges */
.rc-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}
.rc-badge-approved {
    background: #dcfce7;
    color: #166534;
}
.rc-badge-pending {
    background: #fef9c3;
    color: #854d0e;
}
.rc-badge-rejected,
.rc-badge-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

/* Modal */
.rc-modal {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
}
.rc-modal.show {
    display: flex;
}
.rc-modal-box { animation: rc-modal-in .22s ease both; }
@keyframes rc-modal-in { from { opacity: 0; transform: scale(.96) translateY(8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
@keyframes rc-overlay-in { from { opacity: 0; } to { opacity: 1; } }
.rc-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(2px);
    animation: rc-overlay-in .22s ease both;
}
.rc-modal-box {
    position: relative;
    z-index: 2;
    width: 520px;
    max-width: calc(100% - 30px);
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}
.rc-modal-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 22px 22px 18px;
    border-bottom: 1px solid #e5e7eb;
}
.rc-modal-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(37, 99, 235, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2563eb;
    font-size: 20px;
}
.rc-modal-header h2 {
    margin: 0;
    color: #111827;
    font-size: 19px;
}
.rc-modal-header p {
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 13px;
}
.rc-modal-close {
    position: absolute;
    right: 14px;
    top: 12px;
    z-index: 5;
    border: none;
    background: transparent;
    color: #9ca3af;
    font-size: 26px;
    line-height: 1;
    cursor: pointer;
}
.rc-modal-close:hover {
    color: #111827;
}
.rc-modal-body {
    padding: 10px 22px 22px;
}
.rc-detail-row {
    display: grid;
    grid-template-columns: 160px 1fr;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}
.rc-detail-row:last-child {
    border-bottom: none;
}
.rc-detail-label {
    color: #6b7280;
    font-size: 13px;
    font-weight: 700;
}
.rc-detail-value {
    color: #111827;
    font-size: 14px;
    font-weight: 700;
}

/* Responsive */
@media (max-width: 900px) {
    .rc-page { padding: 16px; }
    .rc-filters {
        flex-direction: column;
        align-items: stretch;
    }
    .rc-filter select {
        width: 100%;
    }
    .rc-detail-row {
        grid-template-columns: 1fr;
        gap: 4px;
    }
}
@media (max-width: 600px) {
    .rc-scheduler-heading { align-items: stretch; flex-direction: column; }
    .rc-calendar-card { padding: 8px; }
    .fc .fc-toolbar { flex-wrap: wrap; gap: 8px; }
    .fc .fc-toolbar-title { font-size: 17px; }
    .fc .fc-button { min-height: 34px; padding: 5px 8px !important; font-size: 12px !important; }
}

/* Soft calendar theme matching the reservation and schedule references */
.rc-page {
    color: #252b36;
    background: #f4f5f7;
    padding: 28px clamp(14px, 3vw, 34px) 40px;
}
.rc-title { color: #202633; font-size: 28px; letter-spacing: -.035em; }
.rc-subtitle { color: #858b97; }
.rc-card, .rc-filters, .rc-calendar-card, .rc-table-card {
    border: 1px solid #eceef2;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 4px 18px rgba(30, 41, 59, .045);
}
.rc-card { padding: 18px; }
.rc-card:nth-child(1) { border-top: 3px solid #bdd9fc; }
.rc-card:nth-child(2) { border-top: 3px solid #c7eed5; }
.rc-card:nth-child(3) { border-top: 3px solid #ffe0ae; }
.rc-card:nth-child(4) { border-top: 3px solid #e3d8fb; }
.rc-card:nth-child(1) { background: #e7f1ff; }
.rc-card:nth-child(2) { background: #e8f7ed; }
.rc-card:nth-child(3) { background: #fff2dc; }
.rc-card:nth-child(4) { background: #f1eaff; }
.rc-card-label { color: #858b97; font-size: 11px; }
.rc-card-value { color: #252b36; font-size: 28px; }
.rc-filters { gap: 16px; padding: 15px 18px; }
.rc-filter-actions { display: flex; align-items: flex-end; gap: 8px; }
.rc-filter-button { min-height: 38px; padding: 0 15px; border: 1px solid transparent; border-radius: 9px; font-size: 12px; font-weight: 700; transition: transform .18s ease, box-shadow .18s ease, background .18s ease; }
.rc-filter-button:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(31,41,55,.1); }
.rc-filter-apply { color: #fff; background: #4c86d9; }
.rc-filter-apply:hover { background: #3976cc; }
.rc-filter-apply.has-pending-filters { box-shadow: 0 0 0 3px rgba(76,134,217,.15); }
.rc-filter-clear { border-color: #e6e9ee; color: #5f6876; background: #f7f8fa; }
.rc-filter-clear:hover { background: #eef0f4; }
.rc-filter label { color: #777e8b; font-size: 11px; }
.rc-filter select, .rc-scheduler-date input {
    border-color: #e7e9ee;
    border-radius: 9px;
    color: #343b48;
    background-color: #fff;
}
.rc-filter select:focus, .rc-scheduler-date input:focus {
    border-color: #a9cafa;
    box-shadow: 0 0 0 3px rgba(111, 161, 230, .12);
}
.rc-view-switch { gap: 3px; border-color: #eceef2; border-radius: 11px; background: #fff; }
.rc-view-switch button { min-width: 90px; color: #737a87; font-size: 12px; }
.rc-view-switch button.active { color: #303847; background: #f0f2f5; box-shadow: none; }
.rc-calendar-card { padding: 18px; }
.fc .fc-toolbar { padding: 2px 2px 16px; }
.fc .fc-toolbar-title { color: #252b36; font-size: 20px; font-weight: 650; }
.fc .fc-button { min-height: 34px; border-radius: 8px !important; font-size: 12px !important; }
.fc .fc-button-primary {
    border-color: #e8eaf0 !important;
    color: #596170 !important;
    background: #fff !important;
}
.fc .fc-button-primary:hover, .fc .fc-button-primary:not(:disabled).fc-button-active {
    border-color: #e7e9ee !important;
    color: #252b36 !important;
    background: #f0f2f5 !important;
}
.fc .fc-button-primary:not(:disabled).fc-button-active { box-shadow: inset 0 0 0 1px #e2e5eb !important; }
.fc .fc-col-header-cell { background: #fff; }
.fc .fc-col-header-cell-cushion { padding: 11px 5px; color: #777e8b; font-size: 11px; font-weight: 600; }
.fc-theme-standard td, .fc-theme-standard th { border-color: #edf0f3; }
.fc .fc-daygrid-day, .fc .fc-timegrid-col { background: #fff; }
.fc .fc-daygrid-day-number { color: #555d69; font-size: 12px; }
.fc .fc-day-today { background: #fff8f6 !important; }
.fc .fc-day-today .fc-daygrid-day-number {
    width: 27px; height: 27px; margin: 4px; border-radius: 50%;
    color: #fff; background: #ef5a55; font-weight: 700;
}
.fc .fc-timegrid-slot-label { color: #858b97; font-size: 10px; }
.fc .fc-event { border-radius: 8px; padding: 4px 6px; font-size: 11px; }
.fc .fc-event-title, .fc .fc-event-time { color: inherit; font-weight: 600; }
.fc .fc-list { border-color: #edf0f3; border-radius: 10px; overflow: hidden; }
.fc .fc-list-day-cushion { background: #f7f8fa; }
.rc-scheduler-heading h2 { color: #252b36; font-size: 19px; }
.rc-scheduler-heading p, .rc-scheduler-note { color: #858b97; }
.rc-scheduler-scroll { border-color: #e9ecf1; border-radius: 11px; }
.rc-scheduler-track { background: #fff; }
.rc-scheduler-times { color: #808693; background: #fafbfc; font-weight: 600; }
.rc-scheduler-resource { background: #fff; }
.rc-scheduler-resource strong { color: #303744; }
.rc-scheduler-slot { background: #fff; }
.rc-scheduler-slot:hover, .rc-scheduler-slot.selected { background: #edf5ff; }
.rc-scheduler-slot:disabled { background-color: #fafbfc; }
.rc-scheduler-category { background: #f7f8fa; color: #505866; }
.rc-scheduler-type { background: #fcfcfd; color: #9096a1; }
.rc-scheduler-booking { border-radius: 8px; color: #29313d; font-size: 10px; font-weight: 650; box-shadow: 0 2px 7px rgba(40,50,70,.06); }
.rc-scheduler-booking.confirmed { border-left: 3px solid #55b978; background: #e2f5e8; }
.rc-scheduler-booking.tentative { border-left: 3px solid #e6b34f; background: #fff0d4; }
.rc-scheduler-booking.cancelled { border-left: 3px solid #e77770; background: #ffe5e2; }
.rc-scheduler-booking.conflicted { border-left-color: #dc4d48; background: #ffd9d6 !important; }
.rc-scheduler-popover { border-color: #e8eaf0; border-radius: 10px; background: #fff; box-shadow: 0 12px 34px rgba(31,41,55,.16); color: #343b48; }
.rc-maintenance-label { border-radius: 0 0 0 8px; background: #e9eaf0; color: #626977; }
.rc-overbooked-slot { background: #fff0ee; }
.rc-table-card { padding: 18px; }
.rc-table-title { color: #303744; font-size: 16px; }
.rc-table th { color: #858b97; background: #fafbfc; font-size: 10px; letter-spacing: .04em; text-transform: uppercase; }
.rc-table td { color: #4c5563; }
.rc-modal-box { border: 1px solid #eceef2; border-radius: 16px; }
@media (prefers-reduced-motion: reduce) {
    .rc-page *, .rc-page *::before, .rc-modal-box, .rc-modal-overlay { animation-duration: .01ms !important; transition-duration: .01ms !important; }
}
@media (max-width: 900px) {
    .rc-filter-actions { margin-left: 0; }
}
@media (max-width: 600px) {
    .rc-filter-actions { width: 100%; }
    .rc-filter-button { flex: 1; }
}
</style>

{{-- FullCalendar + logic --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarElement = document.getElementById('resourceCalendar');
    if (!calendarElement) return;

    const events = @json($calendarEvents);

    const calendar = new FullCalendar.Calendar(calendarElement, {
        initialView: 'dayGridMonth',
        height: 'auto',
        contentHeight: 650,
        expandRows: true,
        firstDay: 1,
        navLinks: true,
        editable: false,
        selectable: false,
        nowIndicator: true,
        dayMaxEvents: 3,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
        },
        buttonText: {
            today: 'Today',
            month: 'Month',
            week: 'Week',
            day: 'Day',
            list: 'Agenda',
        },
        eventDidMount: function (info) {
            const status = info.event.extendedProps.statusValue;
            const colors = ['approved', 'confirmed', 'in_progress', 'completed'].includes(status)
                ? { background: '#e2f5e8', border: '#55b978', text: '#285d3a' }
                : ['pending_approval', 'changes_requested', 'draft'].includes(status)
                    ? { background: '#fff0d4', border: '#e6b34f', text: '#715518' }
                    : ['rejected', 'cancelled', 'expired'].includes(status)
                        ? { background: '#ffe5e2', border: '#e77770', text: '#853d39' }
                        : { background: '#e8f1ff', border: '#8bb8f0', text: '#34577f' };
            info.el.style.backgroundColor = colors.background;
            info.el.style.borderLeft = `3px solid ${colors.border}`;
            info.el.style.color = colors.text;
            info.el.title = `${info.event.title} · ${info.event.extendedProps.status}`;
        },
        datesSet: function (info) {
            const view = info.view.calendar.el.querySelector('.fc-view-harness');
            if (!view) return;
            view.classList.remove('fc-view-transition');
            void view.offsetWidth;
            view.classList.add('fc-view-transition');
        },
        events,
        eventClick: function (info) {
            showReservationDetails(info.event);
        },
    });

    calendar.render();
    document.getElementById('calendarLoading').hidden = true;

    // Filters
    const resourceFilter = document.getElementById('resourceFilter');
    const locationFilter = document.getElementById('locationFilter');
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const applyFiltersButton = document.getElementById('applyFiltersButton');
    const clearFiltersButton = document.getElementById('clearFiltersButton');

    document.getElementById('calendarEmpty').hidden = events.length > 0;
    let filteredEvents = events;
    let appliedFilters = { resource: 'all', location: 'all', status: 'all', category: 'all' };

    function applyFilters() {
        const selRes = resourceFilter.value;
        const selLoc = locationFilter.value;
        const selStat = statusFilter.value;
        appliedFilters = { resource: selRes, location: selLoc, status: selStat, category: categoryFilter.value };

        calendar.removeAllEvents();

        const filtered = events.filter(ev => {
            const p = ev.extendedProps;
            const resOk = selRes === 'all' || p.resourceId === selRes;
            const locOk = selLoc === 'all' || p.locationId === selLoc;
            const categoryOk = categoryFilter.value === 'all' || p.categoryId === categoryFilter.value;
            const statOk = selStat === 'all'
                || (selStat === 'approved' && ['approved', 'confirmed', 'in_progress', 'completed'].includes(p.statusValue))
                || (selStat === 'pending' && ['pending_approval', 'changes_requested'].includes(p.statusValue))
                || p.statusValue === selStat;
            return resOk && locOk && categoryOk && statOk;
        });

        filteredEvents = filtered;
        calendar.addEventSource(filtered);
        document.getElementById('calendarEmpty').hidden = filtered.length > 0;
        applyFiltersButton.classList.remove('has-pending-filters');
        renderScheduler();
    }

    function handleFilterChange() {
        applyFiltersButton.classList.add('has-pending-filters');
    }

    resourceFilter.addEventListener('change', handleFilterChange);
    locationFilter.addEventListener('change', handleFilterChange);
    statusFilter.addEventListener('change', handleFilterChange);
    categoryFilter.addEventListener('change', handleFilterChange);
    applyFiltersButton.addEventListener('click', () => {
        selectedSchedulerResources.clear();
        updateSchedulerSelectionCount();
        applyFilters();
    });
    clearFiltersButton.addEventListener('click', () => {
        resourceFilter.value = 'all';
        locationFilter.value = 'all';
        statusFilter.value = 'all';
        categoryFilter.value = 'all';
        selectedSchedulerResources.clear();
        updateSchedulerSelectionCount();
        applyFilters();
    });

    const schedulerResources = @json($schedulerResources);
    const canReserve = @json($canReserve);
    const createReservationUrl = @json(route('reservations.create'));
    const schedulerPanel = document.getElementById('schedulerPanel');
    const schedulerGrid = document.getElementById('schedulerGrid');
    const schedulerDate = document.getElementById('schedulerDate');
    const calendarCard = document.querySelector('.rc-calendar-card:not(.rc-scheduler)');
    const calendarViewButton = document.getElementById('calendarViewButton');
    const schedulerViewButton = document.getElementById('schedulerViewButton');
    const schedulerHours = Array.from({ length: 12 }, (_, index) => index + 8);
    let activeDrag = null;
    let suppressSlotClick = false;
    let selectedSchedulerResources = new Set();

    function updateSchedulerSelectionCount() {
        const count = selectedSchedulerResources.size;
        document.getElementById('schedulerSelectionCount').textContent = count
            ? `${count} resource${count === 1 ? '' : 's'} selected for booking`
            : 'Select resources for a multi-resource booking';
    }

    function resourcesForSlot(resourceId) {
        const selected = [...selectedSchedulerResources];
        if (!selected.length) return [resourceId];
        if (!selected.includes(resourceId)) selected.push(resourceId);
        return [...new Set(selected)];
    }

    function renderScheduler() {
        const selectedCategory = appliedFilters.category;
        const rows = schedulerResources.filter(resource =>
            (selectedCategory === 'all' || resource.categoryId === selectedCategory)
            && (appliedFilters.resource === 'all' || resource.id === appliedFilters.resource)
            && (appliedFilters.location === 'all' || resource.locationId === appliedFilters.location)
        );
        const date = schedulerDate.value;
        schedulerGrid.replaceChildren();
        document.getElementById('schedulerEmpty').hidden = rows.length > 0;
        if (!rows.length) return;

        const header = document.createElement('div');
        header.className = 'rc-scheduler-track';
        const resourceHeading = document.createElement('div');
        resourceHeading.className = 'rc-scheduler-times';
        resourceHeading.textContent = 'Resource / Load';
        header.appendChild(resourceHeading);
        schedulerHours.forEach(hour => {
            const cell = document.createElement('div');
            cell.className = 'rc-scheduler-times';
            cell.textContent = `${String(hour).padStart(2, '0')}:00`;
            header.appendChild(cell);
        });
        schedulerGrid.appendChild(header);

        const grouped = rows.reduce((groups, resource) => {
            (groups[resource.category] ||= []).push(resource);
            return groups;
        }, {});
        Object.entries(grouped).forEach(([category, categoryRows]) => {
            const categoryHeading = document.createElement('div');
            categoryHeading.className = 'rc-scheduler-category';
            categoryHeading.textContent = category;
            schedulerGrid.appendChild(categoryHeading);
            const groupedTypes = categoryRows.reduce((types, resource) => {
                (types[resource.type || 'Other'] ||= []).push(resource);
                return types;
            }, {});
            Object.entries(groupedTypes).forEach(([type, typeRows]) => {
                const typeHeading = document.createElement('div');
                typeHeading.className = 'rc-scheduler-type';
                typeHeading.textContent = type;
                schedulerGrid.appendChild(typeHeading);
                typeRows.forEach(resource => renderResourceRow(resource, date));
            });
        });
    }

    function renderResourceRow(resource, date) {
        const allResourceEvents = events.filter(event => event.extendedProps.resourceId === resource.id
            && event.start.slice(0, 10) === date);
        const resourceEvents = filteredEvents.filter(event => event.extendedProps.resourceId === resource.id
            && event.start.slice(0, 10) === date);
        const blockingEvents = allResourceEvents.filter(event => event.extendedProps.blocking);
        const bookedMinutes = blockingEvents.reduce((total, event) => total + Math.max(0,
            Math.min(1200, timeMinutes(event.end)) - Math.max(480, timeMinutes(event.start))), 0);
        const load = Math.round((bookedMinutes / (12 * 60)) * 100);
        const isMaintenance = resource.status === 'under_maintenance';
        const track = document.createElement('div');
        track.className = 'rc-scheduler-track';
        const label = document.createElement('div');
        label.className = 'rc-scheduler-resource';
        const name = document.createElement('strong');
        const resourceName = document.createElement('span');
        resourceName.textContent = resource.name;
        if (canReserve && resource.bookable) {
            const resourceCheckbox = document.createElement('input');
            resourceCheckbox.type = 'checkbox';
            resourceCheckbox.className = 'rc-resource-select';
            resourceCheckbox.checked = selectedSchedulerResources.has(resource.id);
            resourceCheckbox.setAttribute('aria-label', `Add ${resource.name} to multi-resource booking`);
            resourceCheckbox.addEventListener('click', event => event.stopPropagation());
            resourceCheckbox.addEventListener('change', () => {
                if (resourceCheckbox.checked) selectedSchedulerResources.add(resource.id);
                else selectedSchedulerResources.delete(resource.id);
                updateSchedulerSelectionCount();
                renderScheduler();
            });
            name.append(resourceCheckbox, resourceName);
        } else {
            name.appendChild(resourceName);
        }
        const meta = document.createElement('small');
        meta.textContent = [resource.type, resource.location].filter(Boolean).join(' · ') || resource.status;
        label.append(name, meta);
        const capacity = document.createElement('span');
        capacity.className = `rc-capacity ${load > 100 ? 'overloaded' : load >= 80 ? 'near-capacity' : ''}`;
        const bar = document.createElement('span');
        bar.className = 'rc-capacity-track';
        const fill = document.createElement('i');
        fill.style.width = `${Math.min(load, 100)}%`;
        bar.appendChild(fill);
        const percent = document.createElement('em');
        percent.textContent = `${load}%`;
        capacity.append(bar, percent);
        label.appendChild(capacity);
        track.appendChild(label);

        schedulerHours.forEach(hour => {
            const slot = document.createElement('button');
            slot.type = 'button';
            slot.className = `rc-scheduler-slot ${isMaintenance ? 'rc-maintenance-slot' : ''}`;
            slot.dataset.resourceId = resource.id;
            slot.dataset.hour = String(hour);
            slot.dataset.date = date;
            slot.setAttribute('aria-label', `${resource.name}, ${date}, ${hour}:00`);
            const collisions = blockingEvents.filter(event => overlapsHour(event, hour));
            const selectedResourceIds = resourcesForSlot(resource.id);
            const selectedResourceConflict = selectedResourceIds.some(resourceId => events.some(event =>
                event.extendedProps.resourceId === resourceId
                && event.extendedProps.blocking
                && event.start.slice(0, 10) === date
                && overlapsHour(event, hour)
            ));
            if (collisions.length || selectedResourceConflict) slot.disabled = true;
            if (!canReserve || !resource.bookable || isMaintenance || new Date(`${date}T${String(hour).padStart(2, '0')}:00:00`) < new Date()) {
                slot.disabled = true;
            }
            slot.addEventListener('pointerdown', event => {
                if (slot.disabled) return;
                event.preventDefault();
                activeDrag = { resource, resourceIds: resourcesForSlot(resource.id), date, start: hour, end: hour };
                updateDragSelection();
            });
            slot.addEventListener('pointerenter', () => {
                if (!activeDrag || activeDrag.resource.id !== resource.id) return;
                activeDrag.end = hour;
                updateDragSelection();
            });
            slot.addEventListener('click', () => {
                if (suppressSlotClick) { suppressSlotClick = false; return; }
                if (!slot.disabled) openReservationForm(resourcesForSlot(resource.id), date, hour, hour + 1);
            });
            if (collisions.length > 1) {
                slot.classList.add('rc-overbooked-slot');
                const warning = document.createElement('span');
                warning.className = 'rc-conflict-flag';
                warning.innerHTML = '<i class="ti ti-alert-triangle" aria-hidden="true"></i>';
                warning.title = 'Overlapping bookings';
                slot.appendChild(warning);
            }
            track.appendChild(slot);
        });

        if (isMaintenance) {
            const badge = document.createElement('span');
            badge.className = 'rc-maintenance-label';
            badge.textContent = 'Maintenance';
            track.appendChild(badge);
        }

        requestAnimationFrame(() => {
            const cellWidth = (track.clientWidth - 220) / 12;
            resourceEvents.forEach(event => {
                const start = timeMinutes(event.start);
                const end = timeMinutes(event.end);
                const clippedStart = Math.max(480, start);
                const clippedEnd = Math.min(1200, end);
                if (clippedEnd <= clippedStart) return;
                const block = document.createElement('button');
                block.type = 'button';
                block.className = `rc-scheduler-booking ${bookingState(event)}`;
                if (allResourceEvents.some(other => other !== event && other.extendedProps.blocking
                    && event.extendedProps.blocking && Date.parse(other.start) < Date.parse(event.end)
                    && Date.parse(other.end) > Date.parse(event.start))) {
                    block.classList.add('conflicted');
                }
                block.style.left = `${220 + ((clippedStart - 480) / 60) * cellWidth + 3}px`;
                block.style.width = `${Math.max(34, ((clippedEnd - clippedStart) / 60) * cellWidth - 6)}px`;
                block.textContent = `${formatClock(start)} ${event.title}`;
                block.dataset.tooltip = `${event.title}\n${schedulerDate.value} · ${formatClock(start)}–${formatClock(end)}\n${event.extendedProps.location || 'No location'} · ${event.extendedProps.status}\nReserved by ${event.extendedProps.owner || '—'}`;
                block.title = block.dataset.tooltip.replaceAll('\n', ' · ');
                block.addEventListener('click', () => showReservationDetails(event));
                block.addEventListener('pointerenter', pointer => showSchedulerPopover(block.dataset.tooltip, pointer.clientX, pointer.clientY));
                block.addEventListener('pointermove', pointer => moveSchedulerPopover(pointer.clientX, pointer.clientY));
                block.addEventListener('pointerleave', hideSchedulerPopover);
                track.appendChild(block);
            });
        });
        schedulerGrid.appendChild(track);
    }

    function updateDragSelection() {
        schedulerGrid.querySelectorAll('.rc-scheduler-slot.selected').forEach(slot => slot.classList.remove('selected'));
        if (!activeDrag) return;
        const first = Math.min(activeDrag.start, activeDrag.end);
        const last = Math.max(activeDrag.start, activeDrag.end);
        for (let hour = first; hour <= last; hour++) {
            schedulerGrid.querySelector(`.rc-scheduler-slot[data-resource-id="${activeDrag.resource.id}"][data-hour="${hour}"]`)?.classList.add('selected');
        }
    }

    document.addEventListener('pointerup', () => {
        if (!activeDrag) return;
        const selection = activeDrag;
        activeDrag = null;
        suppressSlotClick = true;
        setTimeout(() => { suppressSlotClick = false; }, 0);
        const start = Math.min(selection.start, selection.end);
        const end = Math.max(selection.start, selection.end) + 1;
        const blocked = selection.resourceIds.some(resourceId => events.some(event =>
            event.extendedProps.resourceId === resourceId
            && event.extendedProps.blocking
            && event.start.slice(0, 10) === selection.date
            && timeMinutes(event.start) < end * 60
            && timeMinutes(event.end) > start * 60
        ));
        if (blocked) {
            window.alert('That range overlaps a booking. Choose an open time range.');
            schedulerGrid.querySelectorAll('.rc-scheduler-slot.selected').forEach(slot => slot.classList.remove('selected'));
            suppressSlotClick = false;
            return;
        }
        openReservationForm(selection.resourceIds, selection.date, start, end);
    });

    function openReservationForm(resourceIds, date, startHour, endHour) {
        const [primaryResourceId, ...additionalResourceIds] = resourceIds;
        const params = new URLSearchParams({ resource_id: primaryResourceId, date, start_time: `${String(startHour).padStart(2, '0')}:00`, end_time: `${String(endHour).padStart(2, '0')}:00` });
        additionalResourceIds.forEach(resourceId => params.append('resource_ids[]', resourceId));
        window.location.href = `${createReservationUrl}?${params.toString()}`;
    }

    function timeMinutes(value) {
        const time = value.slice(11, 16).split(':').map(Number);
        return (time[0] || 0) * 60 + (time[1] || 0);
    }

    function formatClock(minutes) {
        const hour = Math.floor(minutes / 60);
        const minute = minutes % 60;
        return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
    }

    function overlapsHour(event, hour) {
        return timeMinutes(event.start) < (hour + 1) * 60 && timeMinutes(event.end) > hour * 60;
    }

    function bookingState(event) {
        if (['pending_approval', 'changes_requested', 'draft'].includes(event.extendedProps.statusValue)) return 'tentative';
        if (['rejected', 'cancelled', 'expired'].includes(event.extendedProps.statusValue)) return 'cancelled';
        return 'confirmed';
    }

    const schedulerPopover = document.getElementById('schedulerPopover');
    function showSchedulerPopover(content, x, y) {
        schedulerPopover.textContent = content;
        schedulerPopover.hidden = false;
        moveSchedulerPopover(x, y);
    }
    function moveSchedulerPopover(x, y) {
        schedulerPopover.style.left = `${Math.min(x + 14, window.innerWidth - schedulerPopover.offsetWidth - 12)}px`;
        schedulerPopover.style.top = `${Math.min(y + 14, window.innerHeight - schedulerPopover.offsetHeight - 12)}px`;
    }
    function hideSchedulerPopover() {
        schedulerPopover.hidden = true;
    }

    schedulerDate.addEventListener('change', renderScheduler);
    document.getElementById('schedulerViewButton').addEventListener('click', () => {
        calendarCard.hidden = true;
        schedulerPanel.hidden = false;
        schedulerViewButton.classList.add('active');
        schedulerViewButton.setAttribute('aria-selected', 'true');
        calendarViewButton.classList.remove('active');
        calendarViewButton.setAttribute('aria-selected', 'false');
        renderScheduler();
    });
    calendarViewButton.addEventListener('click', () => {
        schedulerPanel.hidden = true;
        calendarCard.hidden = false;
        calendarViewButton.classList.add('active');
        calendarViewButton.setAttribute('aria-selected', 'true');
        schedulerViewButton.classList.remove('active');
        schedulerViewButton.setAttribute('aria-selected', 'false');
    });
    window.addEventListener('resize', () => { if (!schedulerPanel.hidden) renderScheduler(); });

    // Modal
    const modal = document.getElementById('reservationModal');
    const closeBtn = document.getElementById('closeReservationModal');
    const overlay = document.querySelector('.rc-modal-overlay');

    function openReservationModal() {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    }

    function showReservationDetails(event) {
        const details = event.extendedProps;
        const start = event.start instanceof Date ? event.start : new Date(event.start);
        const end = event.end instanceof Date ? event.end : new Date(event.end);
        document.getElementById('detailResource').textContent = details.resource || event.title;
        document.getElementById('detailDate').textContent = formatDate(start);
        document.getElementById('detailTime').textContent = formatTimeRange(start, end);
        document.getElementById('detailOwner').textContent = details.owner || '-';
        document.getElementById('detailLocation').textContent = details.location || '-';
        document.getElementById('detailStatus').textContent = details.status || '-';
        openReservationModal();
    }

    function closeReservationModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }

    closeBtn.addEventListener('click', closeReservationModal);
    overlay.addEventListener('click', closeReservationModal);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeReservationModal();
    });

    function formatDate(date) {
        if (!date) return '-';
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }

    function formatTimeRange(start, end) {
        if (!start) return '-';
        const st = start.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
        });
        if (!end) return st;
        const en = end.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
        });
        return `${st} – ${en}`;
    }
});
</script>
@endsection
