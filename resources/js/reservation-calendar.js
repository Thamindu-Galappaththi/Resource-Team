import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import '../css/reservation-calendar.css';

document.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('reservationCalendar');
    if (!element) return;

    const filters = document.getElementById('reservation-calendar-filters');
    const feedback = document.getElementById('calendar-slot-feedback');
    const refreshStatus = document.getElementById('calendar-refresh-status');
    const canCreate = element.dataset.canCreate === '1';
    const modalElement = document.getElementById('reservation-calendar-detail');
    const modal = window.bootstrap?.Modal.getOrCreateInstance(modalElement);
    const dateFormat = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' });
    const timeFormat = new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit', hour12: true });
    const inputTimeFormat = new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
    const partsFormat = new Intl.DateTimeFormat('en', { year: 'numeric', month: '2-digit', day: '2-digit' });

    function localDateKey(date) {
        const parts = Object.fromEntries(partsFormat.formatToParts(date).map(({ type, value }) => [type, value]));
        return `${parts.year}-${parts.month}-${parts.day}`;
    }

    function localTimeKey(date) {
        const parts = Object.fromEntries(inputTimeFormat.formatToParts(date).map(({ type, value }) => [type, value]));
        return `${parts.hour}:${parts.minute}`;
    }

    function setText(id, value) {
        document.getElementById(id).textContent = value || '—';
    }

    function showFeedback(message, type = 'info', href = null) {
        feedback.replaceChildren();
        feedback.hidden = false;
        feedback.className = `calendar-slot-feedback alert alert-${type} mt-3 mb-0`;
        const text = document.createElement('span');
        text.textContent = message;
        feedback.appendChild(text);
        if (href) {
            const link = document.createElement('a');
            link.href = href;
            link.className = 'btn btn-sm btn-primary ms-3';
            link.textContent = 'Continue to reservation';
            feedback.appendChild(link);
        }
    }

    function statusAccent(status) {
        if (['approved', 'confirmed', 'in_progress'].includes(status)) return '#16845b';
        if (['cancelled', 'rejected', 'expired'].includes(status)) return '#7b8493';
        if (status === 'completed') return '#4778d0';
        return '#f4c542';
    }

    async function checkAvailability(selection) {
        if (!canCreate) return;
        const resourceId = filters.elements.resource_id.value;
        if (!resourceId) {
            showFeedback('Choose a resource filter before selecting a time slot.', 'warning');
            return;
        }

        const date = localDateKey(selection.start);
        const endMinute = new Date(selection.end.getTime() - 60_000);
        if (localDateKey(endMinute) !== date) {
            showFeedback('Choose a slot within one day. Use the reservation form for overnight bookings.', 'warning');
            return;
        }
        const startTime = localTimeKey(selection.start);
        const endTime = localTimeKey(selection.end);
        if (endTime <= startTime) {
            showFeedback('Choose a valid start and end time.', 'warning');
            return;
        }

        const params = new URLSearchParams();
        params.append('resource_ids[]', resourceId);
        params.set('reservation_date', date);
        params.set('start_time', startTime);
        params.set('end_time', endTime);
        showFeedback('Checking availability…', 'info');

        try {
            const response = await fetch(`${element.dataset.availabilityUrl}?${params}`, {
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || !result.available) {
                showFeedback(result.message || 'That time is unavailable. Select another slot.', 'danger');
                return;
            }
            const createUrl = new URL(element.dataset.createUrl, window.location.origin);
            createUrl.searchParams.set('date', date);
            createUrl.searchParams.set('start_time', startTime);
            createUrl.searchParams.set('end_time', endTime);
            createUrl.searchParams.set('resource_id', resourceId);
            showFeedback('This resource is available for the selected time.', 'success', createUrl.toString());
        } catch {
            showFeedback('Availability could not be checked. Please try again.', 'warning');
        }
    }

    const calendar = new Calendar(element, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        timeZone: 'local',
        height: 'auto',
        expandRows: true,
        firstDay: 0,
        nowIndicator: true,
        navLinks: true,
        selectable: canCreate,
        selectMirror: true,
        selectOverlap: (event) => event.extendedProps.isHoliday || ['cancelled', 'rejected', 'expired', 'completed'].includes(event.extendedProps.statusKey),
        eventOverlap: false,
        dayMaxEvents: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
        },
        buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day', list: 'Agenda' },
        events: async (fetchInfo, success, failure) => {
            const params = new URLSearchParams({ start: fetchInfo.startStr, end: fetchInfo.endStr });
            new FormData(filters).forEach((value, key) => { if (value) params.set(key, value); });
            try {
                const response = await fetch(`${element.dataset.eventsUrl}?${params}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error('Reservations could not be loaded.');
                const events = await response.json();
                const compactTimeView = ['timeGridWeek', 'timeGridDay'].includes(calendar.view.type);
                success(compactTimeView
                    ? events.map((event) => {
                        if (event.extendedProps?.isHoliday || !event.start || !event.end) return event;
                        const markerEnd = new Date(new Date(event.start).getTime() + 15 * 60 * 1000);

                        return { ...event, end: markerEnd.toISOString() };
                    })
                    : events);
                refreshStatus.textContent = `Updated ${timeFormat.format(new Date())} · refreshes automatically`;
            } catch (error) {
                refreshStatus.textContent = error.message || 'Could not refresh reservations.';
                failure(error);
            }
        },
        eventContent: (info) => {
            if (info.view.type === 'dayGridMonth' && !info.event.extendedProps.isHoliday) {
                const badge = document.createElement('div');
                badge.className = 'reservation-month-event';
                const dot = document.createElement('span');
                dot.className = 'reservation-month-event-dot';
                dot.style.backgroundColor = statusAccent(info.event.extendedProps.statusKey);
                badge.appendChild(dot);

                const label = document.createElement('span');
                label.className = 'reservation-month-event-label';
                label.textContent = [info.timeText, info.event.title].filter(Boolean).join(' ');
                badge.appendChild(label);
                return { domNodes: [badge] };
            }
            if (info.view.type !== 'timeGridWeek' && info.view.type !== 'timeGridDay') return true;
            const line = document.createElement('div');
            line.className = 'reservation-week-event-line';
            if (info.event.start) {
                const time = document.createElement('span');
                time.className = 'reservation-week-event-time';
                time.textContent = timeFormat.format(info.event.start).replace(/\s/g, '').toLowerCase();
                line.appendChild(time);
            }
            const title = document.createElement('span');
            title.className = 'reservation-week-event-title';
            title.textContent = info.event.extendedProps.resource || info.event.title;
            line.appendChild(title);
            return { domNodes: [line] };
        },
        eventClick: (info) => {
            info.jsEvent.preventDefault();
            const { event } = info;
            const details = event.extendedProps;
            if (details.isHoliday) {
                setText('reservation-calendar-detail-title', event.title);
                setText('reservation-calendar-detail-resource', '—');
                setText('reservation-calendar-detail-owner', details.holidayTypeLabel || 'Sri Lankan holiday');
                setText('reservation-calendar-detail-location', '—');
                setText('reservation-calendar-detail-time', event.start ? dateFormat.format(event.start) : '—');
                setText('reservation-calendar-detail-duration', 'All day');
                setText('reservation-calendar-detail-purpose', details.holidayTypeLabel || 'Holiday');
                const badge = document.getElementById('reservation-calendar-detail-status');
                badge.textContent = details.holidayTypeLabel || 'Holiday';
                badge.className = 'badge rounded-pill text-bg-info';
                document.getElementById('reservation-calendar-conflict').hidden = true;
                document.getElementById('reservation-calendar-detail-link').hidden = true;
                modal?.show();
                return;
            }
            document.querySelectorAll('#reservationCalendar .reservation-time-event.is-selected')
                .forEach((element) => element.classList.remove('is-selected'));
            if (info.view.type === 'timeGridWeek' || info.view.type === 'timeGridDay') {
                info.el.classList.add('is-selected');
            }
            setText('reservation-calendar-detail-title', details.reference || event.title);
            setText('reservation-calendar-detail-resource', details.resource);
            setText('reservation-calendar-detail-owner', details.owner);
            setText('reservation-calendar-detail-location', details.location);
            setText('reservation-calendar-detail-purpose', details.purpose);
            const reservationStart = details.reservationStart ? new Date(details.reservationStart) : event.start;
            const reservationEnd = details.reservationEnd ? new Date(details.reservationEnd) : event.end;
            setText('reservation-calendar-detail-time', reservationStart
                ? `${dateFormat.format(reservationStart)} · ${timeFormat.format(reservationStart)} – ${timeFormat.format(reservationEnd)}`
                : '—');
            setText('reservation-calendar-detail-duration', details.duration);
            const badge = document.getElementById('reservation-calendar-detail-status');
            badge.textContent = details.status || 'Unknown';
            badge.className = `badge rounded-pill ${['approved', 'confirmed', 'in_progress'].includes(details.statusKey) ? 'text-bg-success' : ['cancelled', 'rejected', 'expired'].includes(details.statusKey) ? 'text-bg-secondary' : 'text-bg-warning'}`;
            document.getElementById('reservation-calendar-conflict').hidden = !details.conflict;
            const link = document.getElementById('reservation-calendar-detail-link');
            link.hidden = !details.url;
            if (details.url) link.href = details.url;
            modal?.show();
        },
        dateClick: (info) => {
            if (info.view.type === 'dayGridMonth') calendar.changeView('timeGridDay', info.date);
        },
        select: (selection) => {
            checkAvailability(selection);
            calendar.unselect();
        },
        eventDidMount: (info) => {
            const details = info.event.extendedProps;
            if (details.isHoliday) {
                info.el.title = `${info.event.title}${details.holidayTypeLabel ? ` · ${details.holidayTypeLabel}` : ''}`;
                return;
            }
            const accent = statusAccent(details.statusKey);
            if (info.view.type === 'dayGridMonth') {
                const tint = accent.match(/^#([\da-f]{2})([\da-f]{2})([\da-f]{2})$/i);
                const translucent = tint
                    ? `rgba(${parseInt(tint[1], 16)}, ${parseInt(tint[2], 16)}, ${parseInt(tint[3], 16)}, 0.12)`
                    : 'rgba(37, 99, 235, 0.12)';
                info.el.classList.add('reservation-month-event-wrapper');
                info.el.style.setProperty('--reservation-status-color', accent);
                info.el.style.setProperty('--reservation-status-tint', translucent);
                info.el.style.setProperty('--reservation-hover-text', accent === '#f4c542' ? '#1f2937' : '#fff');
            } else if (info.view.type === 'timeGridWeek' || info.view.type === 'timeGridDay') {
                const tint = accent.match(/^#([\da-f]{2})([\da-f]{2})([\da-f]{2})$/i);
                const translucent = tint
                    ? `rgba(${parseInt(tint[1], 16)}, ${parseInt(tint[2], 16)}, ${parseInt(tint[3], 16)}, 0.12)`
                    : 'rgba(37, 99, 235, 0.12)';
                info.el.classList.add('reservation-time-event');
                info.el.style.setProperty('--reservation-status-color', accent);
                info.el.style.setProperty('--reservation-status-tint', translucent);
                info.el.style.setProperty('--reservation-hover-text', accent === '#f4c542' ? '#1f2937' : '#fff');
            }
            info.el.title = `${details.resource} · ${details.owner} · ${details.status} · ${details.duration} · ${details.location}${details.conflict ? ' · RESOURCE CONFLICT' : ''}`;
        },
        datesSet: (info) => {
            const date = info.view.currentStart;
            const params = new URLSearchParams({ year: String(date.getFullYear()), month: String(date.getMonth() + 1) });
            new FormData(filters).forEach((value, key) => {
                if (value && key !== 'year' && key !== 'month') params.set(key, value);
            });
            fetch(`${window.RSVCal?.calendarUrl || '/reservations/calendar'}?${params}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            }).then((response) => response.ok ? response.json() : null).then((payload) => {
                if (!payload?.stats) return;
                document.getElementById('rsv-stat-month').textContent = payload.stats.month;
                document.getElementById('rsv-stat-today').textContent = payload.stats.today;
                document.getElementById('rsv-stat-pending').textContent = payload.stats.pending;
                document.getElementById('rsv-stat-approved').textContent = payload.stats.approved;
            }).catch(() => {});
        },
    });

    calendar.render();
    filters.addEventListener('change', () => calendar.refetchEvents());
    const refreshTimer = window.setInterval(() => {
        if (!document.hidden) calendar.refetchEvents();
    }, 10_000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) calendar.refetchEvents();
    });
    window.addEventListener('pagehide', () => window.clearInterval(refreshTimer), { once: true });
});
