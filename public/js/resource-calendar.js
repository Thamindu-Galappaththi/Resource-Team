document.addEventListener('DOMContentLoaded', () => {
    const calendarElement = document.getElementById('resourceCalendar');
    const loading = document.getElementById('calendarLoading');
    const hideLoading = () => { if (loading) loading.hidden = true; };

    const config = window.RC || {};
    const events = (config.events || []).filter((event) => /^\d{4}-\d{2}-\d{2}/.test(String(event?.start || '')));
    let filteredEvents = events;
    const holidays = config.holidays || [];
    const holidayImageBase = (config.holidayImageBase || '/images/holidays').replace(/\/$/, '');
    const schedulerResources = config.schedulerResources || [];
    const canReserve = Boolean(config.canReserve);
    const createReservationUrl = config.createReservationUrl;
    const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
    const swalFire = (options) => window.RMSwal?.fire ? window.RMSwal.fire(options) : window.Swal?.fire(options);
    let lastCalendarView = null;
    let calendarReady = false;
    const upcomingPageSize = 10;
    let upcomingRows = [];
    let upcomingPage = 1;
    document.getElementById('upcomingPagination')?.addEventListener('click', onUpcomingPaginationClick);

    try {
        renderUpcoming(events);
        const empty = document.getElementById('calendarEmpty');
        if (empty) empty.hidden = events.length > 0;
    } catch (error) {
        console.error('Resource calendar upcoming list failed', error);
    }

    if (!calendarElement || typeof FullCalendar === 'undefined') {
        hideLoading();
        return;
    }

    const calendar = new FullCalendar.Calendar(calendarElement, {
        initialView: isMobile ? 'listWeek' : 'dayGridMonth',
        height: 'auto',
        contentHeight: 'auto',
        expandRows: false,
        views: {
            timeGridWeek: { contentHeight: 640, dayMaxEvents: 2, eventMaxStack: 3 },
            timeGridDay: { contentHeight: 640, dayMaxEvents: 2, eventMaxStack: 3 },
        },
        fixedWeekCount: false,
        firstDay: 0,
        navLinks: true,
        editable: false,
        selectable: false,
        nowIndicator: true,
        dayMaxEvents: 2,
        dateClick(info) {
            if (info.view.type !== 'dayGridMonth') return;
            const holiday = holidayOn(info.date);
            if (info.jsEvent?.target?.closest('[data-holiday]')) {
                if (holiday) showHolidayDialog(holiday);
                return;
            }
            const items = bookingsOn(info.date);
            if (!items.length && holiday) {
                showHolidayDialog(holiday);
                return;
            }
            if (!items.length) return;
            showDayEventList(info.date, holiday ? [holiday, ...items] : items);
        },
        datesSet() {
            if (calendarReady) syncTimeGridEvents();
            queueMicrotask(paintMonthCells);
        },
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
                return { html: `<span class="rc-event-title" data-full-label="${escapeHtml(fullLabel)}">${escapeHtml(fullLabel)}</span>` };
            }
            const start = details.actualStart || info.event.start;
            const end = details.actualEnd || info.event.end;
            const timeLabel = formatTimeRange(
                start instanceof Date ? start : new Date(start),
                end instanceof Date ? end : new Date(end),
            );
            const time = timeLabel && timeLabel !== '—' ? `<span class="rc-event-time">${escapeHtml(timeLabel)}</span>` : '';
            const chip = info.view.type.startsWith('dayGrid') ? ' is-chip' : '';
            return { html: `<div class="rc-event-inner${chip}">${time}<span class="rc-event-title">${escapeHtml(info.event.title)}</span></div>` };
        },
        events: [...events.map(withEventColors), ...holidays],
        eventClick(info) {
            if (info.event.extendedProps?.isHoliday) {
                if (info.event.display === 'background') return;
                showHolidayDialog(info.event);
                return;
            }
            showReservationDetails(info.event);
        },
    });

    try {
        calendar.render();
        calendarReady = true;
        paintMonthCells();
    } catch (error) {
        console.error('Resource calendar failed to render', error);
    } finally {
        hideLoading();
    }

    const resourceFilter = document.getElementById('resourceFilter');
    const locationFilter = document.getElementById('locationFilter');
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const clearFiltersButton = document.getElementById('clearFiltersButton');
    const schedulerPanel = document.getElementById('schedulerPanel');
    const schedulerGrid = document.getElementById('schedulerGrid');
    const schedulerDate = document.getElementById('schedulerDate');
    const calendarCard = document.querySelector('.rc-calendar-card:not(.rc-scheduler)');
    const calendarViewButton = document.getElementById('calendarViewButton');
    const schedulerViewButton = document.getElementById('schedulerViewButton');
    const schedulerHours = Array.from({ length: 12 }, (_, index) => index + 8);
    const schedulerPopover = document.getElementById('schedulerPopover');

    let appliedFilters = { resource: 'all', location: 'all', status: 'all', category: 'all' };
    let selectedSchedulerResources = new Set();
    let activeDrag = null;
    let suppressSlotClick = false;

    const calendarEmpty = document.getElementById('calendarEmpty');
    if (calendarEmpty) calendarEmpty.hidden = events.length > 0;

    function isTimeGrid() {
        return ['timeGridWeek', 'timeGridDay'].includes(calendar.view.type);
    }

    function forCalendarView(eventList) {
        if (!isTimeGrid()) {
            return [...eventList.map(withEventColors), ...holidays];
        }

        return [
            ...eventList.map((event) => {
                const start = new Date(event.start);
                return withEventColors({
                    ...event,
                    end: Number.isNaN(start.getTime())
                        ? event.end
                        : new Date(start.getTime() + 45 * 60 * 1000).toISOString(),
                    extendedProps: {
                        ...event.extendedProps,
                        actualStart: event.start,
                        actualEnd: event.end,
                    },
                });
            }),
            ...holidays.filter((event) => event.display !== 'background'),
        ];
    }

    function syncTimeGridEvents() {
        const view = calendar.view.type;
        if (lastCalendarView === view) return;
        const previous = lastCalendarView;
        lastCalendarView = view;
        if (previous === null && !isTimeGrid()) return;
        calendar.removeAllEvents();
        calendar.addEventSource(forCalendarView(filteredEvents));
    }

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
        calendar.addEventSource(forCalendarView(filtered));
        document.getElementById('calendarEmpty').hidden = filtered.length > 0;
        toggleClear();
        renderUpcoming(filtered);
        queueMicrotask(paintMonthCells);
        if (!schedulerPanel.hidden) renderScheduler();
    }

    function hasFilters() {
        return [resourceFilter, locationFilter, statusFilter, categoryFilter]
            .some((el) => String(el.value) !== 'all');
    }

    function toggleClear() {
        if (clearFiltersButton) clearFiltersButton.disabled = !hasFilters();
    }

    [resourceFilter, locationFilter, statusFilter, categoryFilter].forEach((el) => {
        el?.addEventListener('change', () => {
            selectedSchedulerResources.clear();
            updateSchedulerSelectionCount();
            applyFilters();
        });
    });
    clearFiltersButton?.addEventListener('click', () => {
        if (clearFiltersButton.disabled) return;
        window.RMSelect.setValue('resourceFilter', 'all', 'All resources');
        window.RMSelect.setValue('locationFilter', 'all', 'All locations');
        window.RMSelect.setValue('statusFilter', 'all', 'All statuses');
        window.RMSelect.setValue('categoryFilter', 'all', 'All categories');
        selectedSchedulerResources.clear();
        updateSchedulerSelectionCount();
        applyFilters();
    });
    toggleClear();

    function renderUpcoming(list) {
        const now = Date.now();
        upcomingRows = [...list]
            .filter((event) => Date.parse(event.end || event.start) >= now)
            .sort((a, b) => Date.parse(a.start) - Date.parse(b.start));
        upcomingPage = 1;
        paintUpcomingPage();
    }

    function paintUpcomingPage() {
        const body = document.getElementById('upcomingReservationsBody');
        const count = document.getElementById('upcomingResultCount');
        if (!body) return;
        body.replaceChildren();
        if (!upcomingRows.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No upcoming reservations match your filters.</td></tr>';
            if (count) count.textContent = '';
            renderUpcomingPagination(0);
            return;
        }

        const totalPages = Math.max(1, Math.ceil(upcomingRows.length / upcomingPageSize));
        if (upcomingPage > totalPages) upcomingPage = totalPages;
        const start = (upcomingPage - 1) * upcomingPageSize;
        const rows = upcomingRows.slice(start, start + upcomingPageSize);
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
        if (count) count.textContent = `Showing ${start + 1}–${start + rows.length} of ${upcomingRows.length}`;
        renderUpcomingPagination(totalPages);
    }

    function upcomingVisiblePages(total, current) {
        if (total <= 7) return Array.from({ length: total }, (_, index) => index + 1);
        const pages = new Set([1, total, current, current - 1, current + 1]);
        const ordered = [...pages].filter((page) => page >= 1 && page <= total).sort((a, b) => a - b);
        return ordered.reduce((list, page, index) => {
            if (index && page - ordered[index - 1] > 1) list.push('…');
            list.push(page);
            return list;
        }, []);
    }

    function renderUpcomingPagination(totalPages) {
        const nav = document.getElementById('upcomingPagination');
        if (!nav) return;
        nav.replaceChildren();
        if (totalPages <= 1) return;

        const addItem = (label, page, disabled, active) => {
            const li = document.createElement('li');
            li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
            li.innerHTML = `<button type="button" class="page-link" data-page="${page}">${label}</button>`;
            nav.appendChild(li);
        };

        addItem('Prev', upcomingPage - 1, upcomingPage === 1, false);
        upcomingVisiblePages(totalPages, upcomingPage).forEach((page) => {
            if (page === '…') {
                const li = document.createElement('li');
                li.className = 'page-item disabled';
                li.innerHTML = '<span class="page-link">…</span>';
                nav.appendChild(li);
                return;
            }
            addItem(String(page), page, false, page === upcomingPage);
        });
        addItem('Next', upcomingPage + 1, upcomingPage === totalPages, false);
    }

    function onUpcomingPaginationClick(event) {
        const button = event.target.closest('[data-page]');
        if (!button || button.parentElement.classList.contains('disabled')) return;
        upcomingPage = Number(button.dataset.page);
        paintUpcomingPage();
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

    function dateKey(value) {
        const match = String(value ?? '').match(/^(\d{4}-\d{2}-\d{2})/);
        if (match) return match[1];
        if (value instanceof Date && !Number.isNaN(value.getTime())) {
            const month = String(value.getMonth() + 1).padStart(2, '0');
            const day = String(value.getDate()).padStart(2, '0');
            return `${value.getFullYear()}-${month}-${day}`;
        }
        const parsed = new Date(value);
        if (!Number.isNaN(parsed.getTime())) {
            const month = String(parsed.getMonth() + 1).padStart(2, '0');
            const day = String(parsed.getDate()).padStart(2, '0');
            return `${parsed.getFullYear()}-${month}-${day}`;
        }
        return '';
    }

    function bookingsOn(date) {
        const key = dateKey(date);
        return filteredEvents.filter((event) => dateKey(event.start) === key);
    }

    function holidayOn(date) {
        const key = dateKey(date);
        return holidays.find((event) => event.extendedProps?.isHoliday && event.display !== 'background' && dateKey(event.start) === key)
            || holidays.find((event) => event.extendedProps?.isHoliday && dateKey(event.start) === key)
            || null;
    }

    function showHolidayDialog(holiday) {
        if (!holiday) return;
        const details = holiday.extendedProps || {};
        const image = details.holidayImage || 'public.svg';
        swalFire({
            html: `<div class="rc-holiday-dialog">
                <img class="rc-holiday-art" src="${holidayImageBase}/${encodeURIComponent(image)}?v=2" alt="">
                <div class="rc-holiday-copy">
                    <strong>${escapeHtml(holiday.title)}</strong>
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
    }

    function formatChipTime(value) {
        const date = value instanceof Date ? value : new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }).replace(' ', '').toLowerCase();
    }

    function paintMonthCells() {
        if (calendar.view.type !== 'dayGridMonth') return;
        calendarElement.querySelectorAll('.fc-daygrid-day').forEach((cell) => {
            const frame = cell.querySelector('.fc-daygrid-day-frame');
            if (!frame) return;
            const date = cell.getAttribute('data-date');
            const items = bookingsOn(date);
            const holiday = holidayOn(date);
            const kind = holiday?.extendedProps?.holidayKind || 'public';
            frame.classList.remove('is-holiday', 'is-holiday-poya', 'is-holiday-mercantile', 'is-holiday-public');
            if (holiday) {
                frame.classList.add('is-holiday', `is-holiday-${kind}`);
            }
            let summary = frame.querySelector('.rc-month-summary');
            if (!summary) {
                summary = document.createElement('div');
                summary.className = 'rc-month-summary';
                frame.appendChild(summary);
            }
            if (!items.length && !holiday) {
                summary.replaceChildren();
                summary.hidden = true;
                return;
            }
            const previewLimit = holiday ? 1 : 2;
            const preview = items.slice(0, previewLimit);
            const extra = items.length - preview.length;
            const holidayChip = holiday
                ? `<span class="rc-month-holiday rc-holiday-${escapeHtml(kind)}" data-holiday="1" title="${escapeHtml(holiday.title)}">${escapeHtml(limitText(holiday.title, 22))}</span>`
                : '';
            const chips = preview.map((event) => {
                const colors = eventColors(event.extendedProps?.statusValue);
                const label = `${formatChipTime(event.start)} ${event.extendedProps?.resource || event.title}`.trim();
                return `<span class="rc-month-chip" style="background:${colors.background};color:${colors.text}" title="${escapeHtml(label)}">${escapeHtml(label)}</span>`;
            }).join('');
            summary.hidden = false;
            summary.innerHTML = `${holidayChip}<div class="rc-month-chips">${chips}</div>${extra > 0 ? `<span class="rc-month-more">+${extra} more</span>` : ''}`;
        });
    }

    function uniqueEvents(eventList) {
        const seen = new Set();
        return eventList.filter((event) => {
            const key = String(event.id || `${event.title}-${event.start}`);
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
        });
    }

    async function showDayEventList(date, eventList) {
        const bookings = eventList
            .filter((event) => !event.extendedProps?.isHoliday)
            .sort((left, right) => {
                const leftStart = left.start instanceof Date ? left.start : new Date(left.start);
                const rightStart = right.start instanceof Date ? right.start : new Date(right.start);
                return leftStart - rightStart;
            });
        const holiday = eventList.find((event) => event.extendedProps?.isHoliday);
        const rows = bookings.map((event, index) => {
            const details = event.extendedProps || {};
            const colors = eventColors(details.statusValue);
            const start = event.start instanceof Date ? event.start : new Date(event.start);
            const end = event.end instanceof Date ? event.end : new Date(event.end);
            const meta = [details.location, details.status].filter(Boolean).join(' · ');
            return `<button type="button" class="rc-day-item" data-index="${index}" style="border-left-color:${colors.border};background:${colors.background};color:${colors.text}">
                <span class="rc-day-item-time">${escapeHtml(formatTimeRange(start, end))}</span>
                <span class="rc-day-item-title">${escapeHtml(details.resource || event.title)}</span>
                ${meta ? `<span class="rc-day-item-meta">${escapeHtml(meta)}</span>` : ''}
            </button>`;
        }).join('');
        const holidayRow = holiday
            ? `<div class="rc-day-holiday">${escapeHtml(holiday.title)}${holiday.extendedProps?.holidayTypeLabel ? ` · ${escapeHtml(holiday.extendedProps.holidayTypeLabel)}` : ''}</div>`
            : '';
        await swalFire({
            title: formatDate(date),
            html: `<div class="rc-day-list">${holidayRow}${rows || '<p class="rc-day-empty">No reservations on this day.</p>'}</div>`,
            confirmButtonText: 'Close',
            width: 560,
            customClass: {
                ...window.RMSwal.base.customClass,
                container: 'rm-swal-backdrop',
                htmlContainer: 'rc-day-list-wrap',
            },
            didOpen(popup) {
                popup.querySelectorAll('.rc-day-item').forEach((button) => {
                    button.addEventListener('click', () => {
                        const event = bookings[Number(button.dataset.index)];
                        if (window.Swal) window.Swal.close();
                        if (event) showReservationDetails(event);
                    });
                });
            },
        });
    }

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
