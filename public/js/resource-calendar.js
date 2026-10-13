document.addEventListener('DOMContentLoaded', () => {
    const calendarElement = document.getElementById('resourceCalendar');
    if (!calendarElement || typeof FullCalendar === 'undefined') return;

    const config = window.RC || {};
    const events = config.events || [];
    const holidays = config.holidays || [];
    const holidayImageBase = (config.holidayImageBase || '/images/holidays').replace(/\/$/, '');
    const schedulerResources = config.schedulerResources || [];
    const canReserve = Boolean(config.canReserve);
    const createReservationUrl = config.createReservationUrl;
    const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
    const swalFire = (options) => window.RMSwal.fire(options);

    const calendar = new FullCalendar.Calendar(calendarElement, {
        initialView: isMobile ? 'listWeek' : 'dayGridMonth',
        height: 'auto',
        contentHeight: 'auto',
        expandRows: false,
        views: {
            timeGridWeek: { contentHeight: 640 },
            timeGridDay: { contentHeight: 640 },
        },
        fixedWeekCount: false,
        firstDay: 0,
        navLinks: true,
        editable: false,
        selectable: false,
        nowIndicator: true,
        dayMaxEvents: true,
        moreLinkClick: 'popover',
        moreLinkText: (n) => `+${n} more`,
        headerToolbar: isMobile
            ? { left: 'prev,next', center: 'title', right: 'today' }
            : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day', list: 'Agenda' },
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true },
        slotLabelFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true },
        displayEventTime: true,
        eventDidMount(info) {
            const details = info.event.extendedProps || {};
            if (details.isHoliday) {
                info.el.title = `${info.event.title}${details.holidayTypeLabel ? ` · ${details.holidayTypeLabel}` : ''}`;
                return;
            }
            const colors = eventColors(details.statusValue);
            info.el.style.backgroundColor = colors.background;
            info.el.style.borderColor = colors.border;
            info.el.style.color = colors.text;
            const inPopover = Boolean(info.el.closest('.fc-more-popover'));
            if (info.view.type !== 'dayGridMonth' || inPopover) {
                info.el.style.borderLeft = `3px solid ${colors.border}`;
            }
            info.el.querySelectorAll('.fc-event-main, .fc-event-main-frame, .fc-event-time, .fc-event-title, .fc-sticky, td').forEach((node) => {
                node.style.color = colors.text;
                if (node.tagName === 'TD') node.style.backgroundColor = colors.background;
            });
            if (inPopover) {
                const label = info.el.querySelector('[data-full-label]');
                if (label) label.textContent = label.getAttribute('data-full-label') || info.event.title;
            }
            info.el.title = `${info.event.title}${details.status ? ` · ${details.status}` : ''}`.trim();
        },
        eventContent(info) {
            const details = info.event.extendedProps || {};
            if (details.isHoliday) {
                if (info.event.display === 'background') return { html: '' };
                const holidayTitle = info.view.type === 'dayGridMonth' ? limitText(info.event.title, 22) : info.event.title;
                return { html: `<span class="rc-holiday-label" data-full-label="${escapeHtml(info.event.title)}">${escapeHtml(holidayTitle)}</span>` };
            }
            if (info.view.type === 'dayGridMonth') {
                const fullLabel = `${info.timeText ? `${info.timeText} ` : ''}${info.event.title}`.trim();
                const label = `${info.timeText ? `${info.timeText} ` : ''}${limitText(info.event.title, 18)}`.trim();
                return { html: `<span class="rc-event-title" data-full-label="${escapeHtml(fullLabel)}">${escapeHtml(label)}</span>` };
            }
            const time = info.timeText ? `<span class="rc-event-time">${escapeHtml(info.timeText)}</span>` : '';
            const chip = info.view.type.startsWith('dayGrid') ? ' is-chip' : '';
            return { html: `<div class="rc-event-inner${chip}">${time}<span class="rc-event-title">${escapeHtml(info.event.title)}</span></div>` };
        },
        events: [...events.map(withEventColors), ...holidays],
        eventClick(info) {
            if (info.event.extendedProps?.isHoliday) {
                if (info.event.display === 'background') return;
                const details = info.event.extendedProps || {};
                const image = details.holidayImage || 'public.svg';
                swalFire({
                    html: `<div class="rc-holiday-dialog">
                        <img class="rc-holiday-art" src="${holidayImageBase}/${encodeURIComponent(image)}?v=2" alt="">
                        <div class="rc-holiday-copy">
                            <strong>${escapeHtml(info.event.title)}</strong>
                            <p>${escapeHtml(details.holidayTypeLabel || 'Sri Lankan holiday')}</p>
                        </div>
                    </div>`,
                    confirmButtonText: 'Close',
                    customClass: {
                        ...window.RMSwal.base.customClass,
                        popup: 'rm-swal rc-holiday-swal',
                        container: 'rc-holiday-backdrop',
                    },
                });
                return;
            }
            showReservationDetails(info.event);
        },
    });

    calendar.render();
    document.getElementById('calendarLoading').hidden = true;

    const resourceFilter = document.getElementById('resourceFilter');
    const locationFilter = document.getElementById('locationFilter');
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const applyFiltersButton = document.getElementById('applyFiltersButton');
    const clearFiltersButton = document.getElementById('clearFiltersButton');
    const schedulerPanel = document.getElementById('schedulerPanel');
    const schedulerGrid = document.getElementById('schedulerGrid');
    const schedulerDate = document.getElementById('schedulerDate');
    const calendarCard = document.querySelector('.rc-calendar-card:not(.rc-scheduler)');
    const calendarViewButton = document.getElementById('calendarViewButton');
    const schedulerViewButton = document.getElementById('schedulerViewButton');
    const schedulerHours = Array.from({ length: 12 }, (_, index) => index + 8);
    const schedulerPopover = document.getElementById('schedulerPopover');

    let filteredEvents = events;
    let appliedFilters = { resource: 'all', location: 'all', status: 'all', category: 'all' };
    let selectedSchedulerResources = new Set();
    let activeDrag = null;
    let suppressSlotClick = false;

    document.getElementById('calendarEmpty').hidden = events.length > 0;
    renderUpcoming(events);

    function applyFilters() {
        const selRes = String(resourceFilter.value);
        const selLoc = String(locationFilter.value);
        const selStat = String(statusFilter.value);
        const selCat = String(categoryFilter.value);
        appliedFilters = { resource: selRes, location: selLoc, status: selStat, category: selCat };

        const filtered = events.filter((event) => {
            const props = event.extendedProps || {};
            const resOk = selRes === 'all' || String(props.resourceId) === selRes;
            const locOk = selLoc === 'all' || String(props.locationId) === selLoc;
            const categoryOk = selCat === 'all' || String(props.categoryId) === selCat;
            const statOk = selStat === 'all'
                || (selStat === 'approved' && ['approved', 'confirmed', 'in_progress', 'completed'].includes(props.statusValue))
                || (selStat === 'pending' && ['pending_approval', 'changes_requested'].includes(props.statusValue))
                || String(props.statusValue) === selStat;
            return resOk && locOk && categoryOk && statOk;
        });

        filteredEvents = filtered;
        calendar.removeAllEvents();
        calendar.addEventSource([...filtered.map(withEventColors), ...holidays]);
        document.getElementById('calendarEmpty').hidden = filtered.length > 0;
        applyFiltersButton.classList.remove('has-pending-filters');
        renderUpcoming(filtered);
        if (!schedulerPanel.hidden) renderScheduler();
    }

    [resourceFilter, locationFilter, statusFilter, categoryFilter].forEach((el) => {
        el.addEventListener('change', () => applyFiltersButton.classList.add('has-pending-filters'));
    });
    applyFiltersButton.addEventListener('click', () => {
        selectedSchedulerResources.clear();
        updateSchedulerSelectionCount();
        applyFilters();
    });
    clearFiltersButton.addEventListener('click', () => {
        window.RMSelect.setValue('resourceFilter', 'all', 'All resources');
        window.RMSelect.setValue('locationFilter', 'all', 'All locations');
        window.RMSelect.setValue('statusFilter', 'all', 'All statuses');
        window.RMSelect.setValue('categoryFilter', 'all', 'All categories');
        selectedSchedulerResources.clear();
        updateSchedulerSelectionCount();
        applyFilters();
    });

    function renderUpcoming(list) {
        const body = document.getElementById('upcomingReservationsBody');
        if (!body) return;
        const now = Date.now();
        const rows = [...list]
            .filter((event) => Date.parse(event.end || event.start) >= now)
            .sort((a, b) => Date.parse(a.start) - Date.parse(b.start))
            .slice(0, 12);

        body.replaceChildren();
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No upcoming reservations match your filters.</td></tr>';
            return;
        }
        rows.forEach((event) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(event.title)}</td>
                <td>${formatDate(new Date(event.start))}</td>
                <td>${formatTimeRange(new Date(event.start), new Date(event.end))}</td>
                <td>${escapeHtml(event.extendedProps?.owner || '—')}</td>
                <td>${statusBadge(event.extendedProps?.statusValue, event.extendedProps?.status)}</td>
            `;
            body.appendChild(tr);
        });
    }

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
        const rows = schedulerResources.filter((resource) =>
            (appliedFilters.category === 'all' || String(resource.categoryId) === appliedFilters.category)
            && (appliedFilters.resource === 'all' || String(resource.id) === appliedFilters.resource)
            && (appliedFilters.location === 'all' || String(resource.locationId) === appliedFilters.location)
        );
        const date = schedulerDate.value;
        schedulerGrid.replaceChildren();
        document.getElementById('schedulerEmpty').hidden = rows.length > 0;
        if (!rows.length) return;

        const header = document.createElement('div');
        header.className = 'rc-scheduler-track rc-scheduler-header';
        const resourceHeading = document.createElement('div');
        resourceHeading.className = 'rc-scheduler-times rc-scheduler-corner';
        resourceHeading.textContent = 'Resource / Load';
        header.appendChild(resourceHeading);
        schedulerHours.forEach((hour) => {
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
            const categoryTrack = document.createElement('div');
            categoryTrack.className = 'rc-scheduler-track';
            const categoryHeading = document.createElement('div');
            categoryHeading.className = 'rc-scheduler-category';
            categoryHeading.textContent = category;
            categoryTrack.appendChild(categoryHeading);
            schedulerGrid.appendChild(categoryTrack);
            const groupedTypes = categoryRows.reduce((types, resource) => {
                (types[resource.type || 'Other'] ||= []).push(resource);
                return types;
            }, {});
            Object.entries(groupedTypes).forEach(([type, typeRows]) => {
                const typeTrack = document.createElement('div');
                typeTrack.className = 'rc-scheduler-track';
                const typeHeading = document.createElement('div');
                typeHeading.className = 'rc-scheduler-type';
                typeHeading.textContent = type;
                typeTrack.appendChild(typeHeading);
                schedulerGrid.appendChild(typeTrack);
                typeRows.forEach((resource) => renderResourceRow(resource, date));
            });
        });
    }

    function renderResourceRow(resource, date) {
        const allResourceEvents = events.filter((event) => String(event.extendedProps.resourceId) === String(resource.id) && event.start.slice(0, 10) === date);
        const resourceEvents = filteredEvents.filter((event) => String(event.extendedProps.resourceId) === String(resource.id) && event.start.slice(0, 10) === date);
        const blockingEvents = allResourceEvents.filter((event) => event.extendedProps.blocking);
        const bookedMinutes = blockingEvents.reduce((total, event) => total + Math.max(0, Math.min(1200, timeMinutes(event.end)) - Math.max(480, timeMinutes(event.start))), 0);
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
            resourceCheckbox.addEventListener('click', (event) => event.stopPropagation());
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

        schedulerHours.forEach((hour) => {
            const slot = document.createElement('button');
            slot.type = 'button';
            slot.className = `rc-scheduler-slot ${isMaintenance ? 'rc-maintenance-slot' : ''}`;
            slot.dataset.resourceId = resource.id;
            slot.dataset.hour = String(hour);
            slot.dataset.date = date;
            slot.setAttribute('aria-label', `${resource.name}, ${date}, ${hour}:00`);
            const collisions = blockingEvents.filter((event) => overlapsHour(event, hour));
            const selectedResourceConflict = resourcesForSlot(resource.id).some((resourceId) => events.some((event) =>
                String(event.extendedProps.resourceId) === String(resourceId)
                && event.extendedProps.blocking
                && event.start.slice(0, 10) === date
                && overlapsHour(event, hour)
            ));
            if (collisions.length || selectedResourceConflict) slot.disabled = true;
            if (!canReserve || !resource.bookable || isMaintenance || new Date(`${date}T${String(hour).padStart(2, '0')}:00:00`) < new Date()) {
                slot.disabled = true;
            }
            slot.addEventListener('pointerdown', (event) => {
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
            resourceEvents.forEach((event) => {
                const start = timeMinutes(event.start);
                const end = timeMinutes(event.end);
                const clippedStart = Math.max(480, start);
                const clippedEnd = Math.min(1200, end);
                if (clippedEnd <= clippedStart) return;
                const block = document.createElement('button');
                block.type = 'button';
                block.className = `rc-scheduler-booking ${bookingState(event)}`;
                if (allResourceEvents.some((other) => other !== event && other.extendedProps.blocking
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
                block.addEventListener('pointerenter', (pointer) => showSchedulerPopover(block.dataset.tooltip, pointer.clientX, pointer.clientY));
                block.addEventListener('pointermove', (pointer) => moveSchedulerPopover(pointer.clientX, pointer.clientY));
                block.addEventListener('pointerleave', hideSchedulerPopover);
                track.appendChild(block);
            });
        });
        schedulerGrid.appendChild(track);
    }

    function updateDragSelection() {
        schedulerGrid.querySelectorAll('.rc-scheduler-slot.selected').forEach((slot) => slot.classList.remove('selected'));
        if (!activeDrag) return;
        const first = Math.min(activeDrag.start, activeDrag.end);
        const last = Math.max(activeDrag.start, activeDrag.end);
        for (let hour = first; hour <= last; hour += 1) {
            schedulerGrid.querySelector(`.rc-scheduler-slot[data-resource-id="${activeDrag.resource.id}"][data-hour="${hour}"]`)?.classList.add('selected');
        }
    }

    document.addEventListener('pointerup', async () => {
        if (!activeDrag) return;
        const selection = activeDrag;
        activeDrag = null;
        suppressSlotClick = true;
        setTimeout(() => { suppressSlotClick = false; }, 0);
        const start = Math.min(selection.start, selection.end);
        const end = Math.max(selection.start, selection.end) + 1;
        const blocked = selection.resourceIds.some((resourceId) => events.some((event) =>
            String(event.extendedProps.resourceId) === String(resourceId)
            && event.extendedProps.blocking
            && event.start.slice(0, 10) === selection.date
            && timeMinutes(event.start) < end * 60
            && timeMinutes(event.end) > start * 60
        ));
        if (blocked) {
            schedulerGrid.querySelectorAll('.rc-scheduler-slot.selected').forEach((slot) => slot.classList.remove('selected'));
            suppressSlotClick = false;
            await swalFire({ icon: 'warning', title: 'Time already booked', text: 'That range overlaps a booking. Choose an open time range.' });
            return;
        }
        openReservationForm(selection.resourceIds, selection.date, start, end);
    });

    function openReservationForm(resourceIds, date, startHour, endHour) {
        const [primaryResourceId, ...additionalResourceIds] = resourceIds;
        const params = new URLSearchParams({
            resource_id: primaryResourceId,
            date,
            start_time: `${String(startHour).padStart(2, '0')}:00`,
            end_time: `${String(endHour).padStart(2, '0')}:00`,
        });
        additionalResourceIds.forEach((resourceId) => params.append('resource_ids[]', resourceId));
        window.location.href = `${createReservationUrl}?${params.toString()}`;
    }

    schedulerDate.addEventListener('change', renderScheduler);
    schedulerViewButton.addEventListener('click', () => {
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
        calendar.updateSize();
    });
    window.addEventListener('resize', () => { if (!schedulerPanel.hidden) renderScheduler(); });

    const modal = document.getElementById('reservationModal');
    document.getElementById('closeReservationModal').addEventListener('click', closeReservationModal);
    document.querySelector('.rc-modal-overlay').addEventListener('click', closeReservationModal);
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeReservationModal(); });

    function showReservationDetails(event) {
        const details = event.extendedProps || {};
        const start = event.start instanceof Date ? event.start : new Date(event.start);
        const end = event.end instanceof Date ? event.end : new Date(event.end);
        document.getElementById('detailResource').textContent = details.resource || event.title;
        document.getElementById('detailDate').textContent = formatDate(start);
        document.getElementById('detailTime').textContent = formatTimeRange(start, end);
        document.getElementById('detailOwner').textContent = details.owner || '—';
        document.getElementById('detailLocation').textContent = details.location || '—';
        document.getElementById('detailStatus').innerHTML = statusBadge(details.statusValue, details.status);
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeReservationModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }

    function showSchedulerPopover(content, x, y) {
        schedulerPopover.textContent = content;
        schedulerPopover.hidden = false;
        moveSchedulerPopover(x, y);
    }
    function moveSchedulerPopover(x, y) {
        schedulerPopover.style.left = `${Math.min(x + 14, window.innerWidth - schedulerPopover.offsetWidth - 12)}px`;
        schedulerPopover.style.top = `${Math.min(y + 14, window.innerHeight - schedulerPopover.offsetHeight - 12)}px`;
    }
    function hideSchedulerPopover() { schedulerPopover.hidden = true; }

    function timeMinutes(value) {
        const time = String(value).slice(11, 16).split(':').map(Number);
        return (time[0] || 0) * 60 + (time[1] || 0);
    }
    function formatClock(minutes) {
        return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
    }
    function overlapsHour(event, hour) {
        return timeMinutes(event.start) < (hour + 1) * 60 && timeMinutes(event.end) > hour * 60;
    }
    function bookingState(event) {
        if (['pending_approval', 'changes_requested', 'draft'].includes(event.extendedProps.statusValue)) return 'tentative';
        if (['rejected', 'cancelled', 'expired'].includes(event.extendedProps.statusValue)) return 'cancelled';
        return 'confirmed';
    }
    function eventColors(status) {
        if (['approved', 'confirmed', 'in_progress', 'completed'].includes(status)) return { background: '#bbf7d0', border: '#059669', text: '#14532d' };
        if (['pending_approval', 'changes_requested', 'draft'].includes(status)) return { background: '#fde68a', border: '#d97706', text: '#78350f' };
        if (['rejected', 'cancelled', 'expired'].includes(status)) return { background: '#fecaca', border: '#dc2626', text: '#7f1d1d' };
        return { background: '#bfdbfe', border: '#2563eb', text: '#1e3a8a' };
    }
    function withEventColors(event) {
        const colors = eventColors(event.extendedProps?.statusValue);
        return { ...event, backgroundColor: colors.background, borderColor: colors.border, textColor: colors.text };
    }
    function statusBadge(statusValue, label) {
        const cls = ['approved', 'confirmed', 'in_progress', 'completed'].includes(statusValue)
            ? 'rc-badge-approved'
            : ['pending_approval', 'changes_requested', 'draft'].includes(statusValue)
                ? 'rc-badge-pending'
                : 'rc-badge-cancelled';
        return `<span class="rc-badge ${cls}">${escapeHtml(label || statusValue || '—')}</span>`;
    }
    function formatDate(date) {
        if (!date || Number.isNaN(date.getTime())) return '—';
        return date.toLocaleDateString('en-GB', { year: 'numeric', month: 'short', day: 'numeric' });
    }
    function formatTimeRange(start, end) {
        if (!start || Number.isNaN(start.getTime())) return '—';
        const options = { hour: 'numeric', minute: '2-digit' };
        const startText = start.toLocaleTimeString('en-GB', options);
        if (!end || Number.isNaN(end.getTime())) return startText;
        return `${startText} – ${end.toLocaleTimeString('en-GB', options)}`;
    }
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[character]));
    }
    function limitText(value, max) {
        const text = String(value ?? '').trim();
        return text.length > max ? `${text.slice(0, max - 1).trimEnd()}…` : text;
    }

    updateSchedulerSelectionCount();
});
