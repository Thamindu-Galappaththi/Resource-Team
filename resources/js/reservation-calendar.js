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
    const timeFormat = new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
    const partsFormat = new Intl.DateTimeFormat('en', { year: 'numeric', month: '2-digit', day: '2-digit' });

    function localDateKey(date) {
        const parts = Object.fromEntries(partsFormat.formatToParts(date).map(({ type, value }) => [type, value]));
        return `${parts.year}-${parts.month}-${parts.day}`;
    }

    function localTimeKey(date) {
        const parts = Object.fromEntries(timeFormat.formatToParts(date).map(({ type, value }) => [type, value]));
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
        firstDay: 1,
        nowIndicator: true,
        navLinks: true,
        selectable: canCreate,
        selectMirror: true,
        selectOverlap: (event) => ['cancelled', 'rejected', 'expired', 'completed'].includes(event.extendedProps.statusKey),
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
                success(await response.json());
                refreshStatus.textContent = `Updated ${timeFormat.format(new Date())} · refreshes automatically`;
            } catch (error) {
                refreshStatus.textContent = error.message || 'Could not refresh reservations.';
                failure(error);
            }
        },
        eventContent: (info) => {
            if (info.view.type !== 'timeGridWeek') return true;
            const line = document.createElement('div');
            line.className = 'reservation-week-event-line';
            if (info.timeText) {
                const time = document.createElement('span');
                time.className = 'reservation-week-event-time';
                time.textContent = info.timeText;
                line.appendChild(time);
            }
            const title = document.createElement('span');
            title.className = 'reservation-week-event-title';
            title.textContent = info.event.title;
            line.appendChild(title);
            return { domNodes: [line] };
        },
        eventClick: (info) => {
            info.jsEvent.preventDefault();
            const { event } = info;
            const details = event.extendedProps;
            setText('reservation-calendar-detail-title', details.reference || event.title);
            setText('reservation-calendar-detail-resource', details.resource);
            setText('reservation-calendar-detail-owner', details.owner);
            setText('reservation-calendar-detail-location', details.location);
            setText('reservation-calendar-detail-purpose', details.purpose);
            setText('reservation-calendar-detail-time', event.start
                ? `${dateFormat.format(event.start)} · ${timeFormat.format(event.start)}–${timeFormat.format(event.end)}`
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
            info.el.title = `${details.resource} · ${details.owner} · ${details.status} · ${details.duration} · ${details.location}${details.conflict ? ' · RESOURCE CONFLICT' : ''}`;
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
