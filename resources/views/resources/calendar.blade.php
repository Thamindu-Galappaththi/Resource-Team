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
            <div class="rc-card-value">12</div>
        </div>
        <div class="rc-card">
            <div class="rc-card-label">Today's Bookings</div>
            <div class="rc-card-value">5</div>
        </div>
        <div class="rc-card">
            <div class="rc-card-label">Pending Approvals</div>
            <div class="rc-card-value">3</div>
        </div>
        <div class="rc-card">
            <div class="rc-card-label">This Month</div>
            <div class="rc-card-value">28</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rc-filters">
        <div class="rc-filter">
            <label>Resource</label>
            <select id="resourceFilter">
                <option value="all">All Resources</option>
            </select>
        </div>

        <div class="rc-filter">
            <label>Location</label>
            <select id="locationFilter">
                <option value="all">All Locations</option>
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
    </div>

    {{-- Calendar --}}
    <div class="rc-calendar-card">
        <div id="resourceCalendar"></div>
    </div>

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
                        <th>Reservation Owner</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Meeting Room A</td>
                        <td>28 Aug 2026</td>
                        <td>09:00 AM – 11:00 AM</td>
                        <td>Amal Perera</td>
                        <td><span class="rc-badge rc-badge-pending">Pending</span></td>
                    </tr>
                    <tr>
                        <td>Lab 02</td>
                        <td>28 Aug 2026</td>
                        <td>01:00 PM – 03:00 PM</td>
                        <td>Nimal Silva</td>
                        <td><span class="rc-badge rc-badge-approved">Approved</span></td>
                    </tr>
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
                <div class="rc-detail-label">Reservation Owner</div>
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
    padding: 24px;
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
    color: #111827;
    background: #f3f4f6;
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
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
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
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px 16px;
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
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

/* Calendar card */
.rc-calendar-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
#resourceCalendar {
    width: 100%;
}

/* Table card */
.rc-table-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
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
.rc-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(2px);
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
</style>

{{-- FullCalendar + logic --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarElement = document.getElementById('resourceCalendar');
    if (!calendarElement) return;

    const events = [
        {
            id: '1',
            title: 'Meeting Room A',
            start: '2026-08-25T09:00:00',
            end: '2026-08-25T11:00:00',
            extendedProps: {
                resource: 'Meeting Room A',
                owner: 'John Smith',
                location: 'Main Office',
                status: 'Approved',
            },
        },
        {
            id: '2',
            title: 'Laptop - Dell Latitude',
            start: '2026-08-26T10:00:00',
            end: '2026-08-26T13:00:00',
            extendedProps: {
                resource: 'Laptop - Dell Latitude',
                owner: 'Sarah Fernando',
                location: 'IT Department',
                status: 'Approved',
            },
        },
        {
            id: '3',
            title: 'Projector - Epson',
            start: '2026-08-27T14:00:00',
            end: '2026-08-27T16:00:00',
            extendedProps: {
                resource: 'Projector - Epson',
                owner: 'David Perera',
                location: 'Conference Room',
                status: 'Pending',
            },
        },
        {
            id: '4',
            title: 'Conference Room B',
            start: '2026-08-28T09:30:00',
            end: '2026-08-28T12:00:00',
            extendedProps: {
                resource: 'Conference Room B',
                owner: 'Nimal Silva',
                location: 'Main Office',
                status: 'Approved',
            },
        },
        {
            id: '5',
            title: 'MacBook Pro',
            start: '2026-08-29T13:00:00',
            end: '2026-08-29T15:00:00',
            extendedProps:
            {
                resource: 'MacBook Pro',
                owner: 'Amal Perera',
                location: 'IT Department',
                status: 'Approved',
            },
        },
    ];

    const calendar = new FullCalendar.Calendar(calendarElement, {
        initialView: 'timeGridWeek',
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
        events,
        eventClick: function (info) {
            const event = info.event;
            const details = event.extendedProps;

            document.getElementById('detailResource').textContent =
                details.resource || event.title;
            document.getElementById('detailDate').textContent =
                formatDate(event.start);
            document.getElementById('detailTime').textContent =
                formatTimeRange(event.start, event.end);
            document.getElementById('detailOwner').textContent =
                details.owner || '-';
            document.getElementById('detailLocation').textContent =
                details.location || '-';
            document.getElementById('detailStatus').textContent =
                details.status || '-';

            openReservationModal();
        },
    });

    calendar.render();

    // Filters
    const resourceFilter = document.getElementById('resourceFilter');
    const locationFilter = document.getElementById('locationFilter');
    const statusFilter = document.getElementById('statusFilter');

    const resources = [...new Set(events.map(e => e.extendedProps.resource))];
    const locations = [...new Set(events.map(e => e.extendedProps.location))];

    resources.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r;
        opt.textContent = r;
        resourceFilter.appendChild(opt);
    });

    locations.forEach(l => {
        const opt = document.createElement('option');
        opt.value = l;
        opt.textContent = l;
        locationFilter.appendChild(opt);
    });

    function applyFilters() {
        const selRes = resourceFilter.value;
        const selLoc = locationFilter.value;
        const selStat = statusFilter.value;

        calendar.removeAllEvents();

        const filtered = events.filter(ev => {
            const p = ev.extendedProps;
            const resOk = selRes === 'all' || p.resource === selRes;
            const locOk = selLoc === 'all' || p.location === selLoc;
            const statOk = selStat === 'all' || p.status.toLowerCase() === selStat.toLowerCase();
            return resOk && locOk && statOk;
        });

        calendar.addEventSource(filtered);
    }

    resourceFilter.addEventListener('change', applyFilters);
    locationFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);

    // Modal
    const modal = document.getElementById('reservationModal');
    const closeBtn = document.getElementById('closeReservationModal');
    const overlay = document.querySelector('.rc-modal-overlay');

    function openReservationModal() {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
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