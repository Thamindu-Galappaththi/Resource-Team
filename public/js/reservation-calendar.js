document.addEventListener('DOMContentLoaded', () => {
    const calendarElement = document.getElementById('reservationCalendar');
    if (!calendarElement || typeof FullCalendar === 'undefined') return;

    const filters = document.getElementById('reservation-calendar-filters');
    const feedback = document.getElementById('calendar-slot-feedback');
    const loading = document.getElementById('calendarLoading');
    const empty = document.getElementById('calendarEmpty');
    const clearFiltersButton = document.getElementById('clearFiltersButton');
    const refreshButton = document.getElementById('refreshCalendarButton');
    const modal = document.getElementById('reservationModal');
    const canCreate = calendarElement.dataset.canCreate === '1';
    const isSltEmployee = calendarElement.dataset.sltEmployee === '1';
    if (isSltEmployee) {
        calendarElement.classList.add('is-slt-employee');
        calendarElement.closest('.reservation-calendar-page')?.classList.add('is-slt-calendar');
    }
    const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
    const swalFire = (options) => window.RMSwal.fire(options);
    const holidayImageBase = (window.RSVCal?.holidayImageBase || '/images/holidays').replace(/\/$/, '');
    const filterIds = ['calendar-location', 'calendar-category', 'calendar-resource', 'calendar-status'];
    const filterLabels = {
        'calendar-location': 'All locations',
        'calendar-category': 'All categories',
        'calendar-resource': 'All resources',
        'calendar-status': 'All statuses',
    };
    let suppressFilterChange = false;

    function syncDependentFilters(changedId) {
        if (!window.RMSelect?.cascadeResourceFilters) return;
        window.RMSelect.cascadeResourceFilters({
            resources: window.RSVCal?.filterCatalog?.resources || [],
            categories: window.RSVCal?.filterCatalog?.categories || [],
            locationSelectId: 'calendar-location',
            categorySelectId: 'calendar-category',
            resourceSelectId: 'calendar-resource',
            emptyValue: '',
            updateCategories: changedId !== 'calendar-category' && changedId !== 'calendar-resource',
        });
    }

    syncDependentFilters();

    let bookings = [];
    let holidays = [];
    let loadedRangeStart = null;
    let loadedRangeEnd = null;
    let lastViewType = null;
    let lastStatsKey = '';
    let eventsRequest = 0;
    let eventsAbort = null;
    let sltMorePop = null;

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

    function showFeedback(message, type = 'info', href = null) {
        if (!feedback) return;
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
        if (!canCreate || !filters) return;
        const resourceId = filters.elements.resource_id?.value;
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
            const response = await fetch(`${calendarElement.dataset.availabilityUrl}?${params}`, {
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || !result.available) {
                showFeedback(result.message || 'That time is unavailable. Select another slot.', 'danger');
                return;
            }
            const createUrl = new URL(calendarElement.dataset.createUrl, window.location.origin);
            createUrl.searchParams.set('date', date);
            createUrl.searchParams.set('start_time', startTime);
            createUrl.searchParams.set('end_time', endTime);
            createUrl.searchParams.set('resource_id', resourceId);
            showFeedback('This resource is available for the selected time.', 'success', createUrl.toString());
        } catch {
            showFeedback('Availability could not be checked. Please try again.', 'warning');
        }
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
        selectable: canCreate && calendarElement.dataset.simple !== '1',
        selectMirror: true,
        selectAllow: (info) => info.view.type !== 'dayGridMonth',
        nowIndicator: true,
        dayMaxEvents: 2,
        lazyFetching: false,
        moreLinkClick(info) {
            info.jsEvent?.preventDefault();
            info.jsEvent?.stopPropagation();
            const date = info.date;
            const items = eventsFromMoreLink(info);
            const holiday = holidayOn(date);
            if (isSltEmployee) {
                showSltMorePopover(date, items, info.jsEvent?.currentTarget || info.jsEvent?.target);
                return;
            }
            showDayEventList(date, holiday ? [holiday, ...items] : items);
        },
        headerToolbar: isMobile
            ? { left: 'prev,next', center: 'title', right: isSltEmployee ? 'refreshCalendar today' : 'today' }
            : { left: 'prev,next today', center: 'title', right: isSltEmployee ? 'refreshCalendar dayGridMonth,timeGridWeek,timeGridDay,listWeek' : 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        customButtons: isSltEmployee
            ? {
                refreshCalendar: {
                    text: 'Refresh',
                    hint: 'Refresh calendar',
                    click() {
                        refreshCalendar();
                    },
                },
            }
            : {},
        buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day', list: 'Agenda' },
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true },
        slotLabelFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true },
        displayEventTime: !isSltEmployee,
        events(fetchInfo, success, failure) {
            const viewType = fetchInfo.view?.type || lastViewType;
            const start = Date.parse(fetchInfo.startStr);
            const end = Date.parse(fetchInfo.endStr);
            const covered = loadedRangeStart != null && loadedRangeStart <= start && loadedRangeEnd >= end;
            if (covered) {
                success(forCalendarView(bookings, holidays, viewType));
                queueMicrotask(paintMonthCells);
                return;
            }
            loadCalendarEvents(fetchInfo, success, failure);
        },
        eventDidMount(info) {
            const details = info.event.extendedProps || {};
            if (details.isHoliday) {
                info.el.title = `${info.event.title}${details.holidayTypeLabel ? ` · ${details.holidayTypeLabel}` : ''}`;
                return;
            }
            const colors = eventColors(details.statusValue || details.statusKey);
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
            info.el.title = `${details.resource || info.event.title}${details.status ? ` · ${details.status}` : ''}`.trim();
            if (details.mine) {
                info.el.classList.add('is-mine');
            }
        },
        eventContent(info) {
            const details = info.event.extendedProps || {};
            if (details.isHoliday) {
                if (info.event.display === 'background') return { html: '' };
                const holidayTitle = info.view.type === 'dayGridMonth' ? limitText(info.event.title, 22) : info.event.title;
                return { html: `<span class="rc-holiday-label" data-full-label="${escapeHtml(info.event.title)}">${escapeHtml(holidayTitle)}</span>` };
            }
            if (info.view.type === 'dayGridMonth') {
                const fullLabel = isSltEmployee
                    ? sltEventLabel(info.event)
                    : `${info.timeText ? `${info.timeText} ` : ''}${details.resource || info.event.title}`.trim();
                return { html: `<span class="rc-event-title" data-full-label="${escapeHtml(fullLabel)}">${escapeHtml(fullLabel)}</span>` };
            }
            const start = details.reservationStart || details.actualStart || info.event.start;
            const end = details.reservationEnd || details.actualEnd || info.event.end;
            const timeLabel = formatTimeRange(
                start instanceof Date ? start : new Date(start),
                end instanceof Date ? end : new Date(end),
            );
            const time = !isSltEmployee && timeLabel && timeLabel !== '—'
                ? `<span class="rc-event-time">${escapeHtml(timeLabel)}</span>`
                : '';
            const chip = info.view.type.startsWith('dayGrid') ? ' is-chip' : '';
            const title = isSltEmployee ? sltEventLabel(info.event) : (details.resource || info.event.title);
            return { html: `<div class="rc-event-inner${chip}">${time}<span class="rc-event-title">${escapeHtml(title)}</span></div>` };
        },
        navLinkDayClick(date, jsEvent) {
            if (!isSltEmployee || !canCreate) {
                return;
            }
            jsEvent.preventDefault();
            goCreate(date);
        },
        dateClick(info) {
            if (info.view.type !== 'dayGridMonth') return;
            if (isSltEmployee && info.jsEvent?.target?.closest('.rc-month-chip, .rc-month-more, .rc-more-pop')) {
                return;
            }
            const holiday = holidayOn(info.date);
            if (info.jsEvent?.target?.closest('[data-holiday]')) {
                if (holiday) showHolidayDialog(holiday);
                return;
            }
            if (isSltEmployee) {
                if (canCreate) goCreate(info.date);
                return;
            }
            const items = bookingsOn(info.date);
            if (!items.length && holiday) {
                showHolidayDialog(holiday);
                return;
            }
            if (!items.length) {
                if (canCreate) goCreate(info.date);
                return;
            }
            showDayEventList(info.date, holiday ? [holiday, ...items] : items);
        },
        eventClick(info) {
            if (info.event.extendedProps?.isHoliday) {
                if (info.event.display === 'background') return;
                showHolidayDialog(info.event);
                return;
            }
            if (isSltEmployee) {
                info.jsEvent?.preventDefault();
                info.jsEvent?.stopPropagation();
                openSltBooking(info.event);
                return;
            }
            showReservationDetails(info.event);
        },
        select(selection) {
            checkAvailability(selection);
            calendar.unselect();
        },
        datesSet(info) {
            lastViewType = info.view.type;
            const date = info.view.currentStart;
            const year = String(date.getFullYear());
            const month = String(date.getMonth() + 1);
            const yearInput = document.getElementById('rsv-year');
            const monthInput = document.getElementById('rsv-month');
            if (yearInput) yearInput.value = year;
            if (monthInput) monthInput.value = month;
            refreshStats(date, { force: false });
            closeSltMorePop();
            queueMicrotask(() => {
                decorateRefreshButton();
                paintMonthCells();
            });
        },
        eventsSet() {
            queueMicrotask(paintMonthCells);
        },
    });

    calendar.render();
    decorateRefreshButton();

    function hasFilters() {
        return filterIds.some((id) => Boolean(document.getElementById(id)?.value));
    }

    function toggleClear() {
        if (clearFiltersButton) clearFiltersButton.disabled = !hasFilters();
    }

    filters?.addEventListener('change', (event) => {
        if (suppressFilterChange) return;
        const changedId = event.target?.id;
        if (changedId === 'calendar-location' || changedId === 'calendar-category') {
            suppressFilterChange = true;
            syncDependentFilters(changedId);
            suppressFilterChange = false;
        }
        toggleClear();
        resetLoadedRange();
        calendar.refetchEvents();
    });
    filters?.addEventListener('submit', (event) => {
        event.preventDefault();
        resetLoadedRange();
        calendar.refetchEvents();
    });
    clearFiltersButton?.addEventListener('click', () => {
        if (clearFiltersButton.disabled) return;
        suppressFilterChange = true;
        filterIds.forEach((id) => {
            if (!document.getElementById(id)) return;
            window.RMSelect.setValue(id, '', filterLabels[id]);
        });
        syncDependentFilters();
        suppressFilterChange = false;
        toggleClear();
        resetLoadedRange();
        calendar.refetchEvents();
    });
    toggleClear();

    document.getElementById('closeReservationModal')?.addEventListener('click', closeReservationModal);
    document.querySelector('.rc-modal-overlay')?.addEventListener('click', closeReservationModal);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeReservationModal();
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshCalendar();
    });
    refreshButton?.addEventListener('click', () => refreshCalendar());

    function toolbarRefreshButton() {
        return calendarElement.querySelector('.fc-refreshCalendar-button');
    }

    function decorateRefreshButton() {
        const button = toolbarRefreshButton();
        if (!button) return;
        button.setAttribute('aria-label', 'Refresh calendar');
        button.setAttribute('title', 'Refresh calendar');
        button.type = 'button';
    }

    function setCalendarLoading(isLoading) {
        if (loading) loading.hidden = !isLoading;
        [refreshButton, toolbarRefreshButton()].forEach((button) => {
            if (!button) return;
            button.disabled = isLoading;
            button.classList.toggle('is-loading', isLoading);
            button.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        });
    }

    function refreshCalendar() {
        resetLoadedRange();
        lastStatsKey = '';
        refreshStats(calendar.view?.currentStart || new Date(), { force: true });
        calendar.refetchEvents();
    }

    function refreshStats(date, { force = false } = {}) {
        const year = String(date.getFullYear());
        const month = String(date.getMonth() + 1);
        const statsKey = `${year}-${month}`;
        if (!force && statsKey === lastStatsKey) return;
        lastStatsKey = statsKey;
        const params = new URLSearchParams({ year, month });
        if (filters) {
            new FormData(filters).forEach((value, key) => {
                if (value && key !== 'year' && key !== 'month') params.set(key, value);
            });
        }
        fetch(`${window.RSVCal?.statsUrl || '/reservations/calendar/stats'}?${params}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then((response) => (response.ok ? response.json() : null)).then((payload) => {
            if (!payload?.stats) return;
            const monthStat = document.getElementById('rsv-stat-month');
            const todayStat = document.getElementById('rsv-stat-today');
            const pendingStat = document.getElementById('rsv-stat-pending');
            const approvedStat = document.getElementById('rsv-stat-approved');
            if (monthStat) monthStat.textContent = payload.stats.month;
            if (todayStat) todayStat.textContent = payload.stats.today;
            if (pendingStat) pendingStat.textContent = payload.stats.pending;
            if (approvedStat) approvedStat.textContent = payload.stats.approved;
        }).catch(() => {});
    }

    function resetLoadedRange() {
        loadedRangeStart = null;
        loadedRangeEnd = null;
    }

    function loadCalendarEvents(fetchInfo, success, failure) {
        const requestId = ++eventsRequest;
        eventsAbort?.abort();
        eventsAbort = new AbortController();
        setCalendarLoading(true);
        const params = new URLSearchParams({
            start: fetchInfo.startStr,
            end: fetchInfo.endStr,
            _: String(Date.now()),
        });
        if (filters) {
            new FormData(filters).forEach((value, key) => {
                if (value && key !== 'year' && key !== 'month') params.set(key, value);
            });
        }
        fetch(`${calendarElement.dataset.eventsUrl}?${params}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: eventsAbort.signal,
        }).then((response) => {
            if (!response.ok) throw new Error('Reservations could not be loaded.');
            return response.json();
        }).then((events) => {
            if (requestId !== eventsRequest) return;
            bookings = events.filter((event) => !event.extendedProps?.isHoliday);
            holidays = events.filter((event) => event.extendedProps?.isHoliday);
            loadedRangeStart = Date.parse(fetchInfo.startStr);
            loadedRangeEnd = Date.parse(fetchInfo.endStr);
            if (empty) empty.hidden = bookings.length > 0;
            setCalendarLoading(false);
            success(forCalendarView(bookings, holidays, fetchInfo.view?.type || lastViewType));
            queueMicrotask(paintMonthCells);
        }).catch((error) => {
            if (error?.name === 'AbortError' || requestId !== eventsRequest) return;
            setCalendarLoading(false);
            if (bookings.length || holidays.length) {
                success(forCalendarView(bookings, holidays, fetchInfo.view?.type || lastViewType));
                return;
            }
            failure(error);
        });
    }

    function dateKey(value) {
        if (value instanceof Date && !Number.isNaN(value.getTime())) {
            return localDateKey(value);
        }
        return String(value || '').slice(0, 10);
    }

    function uniqueDayBookings(list) {
        const seen = new Set();
        return list.filter((event) => {
            const props = event.extendedProps || {};
            const key = String(props.reservationId || event.id || `${event.title}-${event.start}`);
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
        });
    }

    function eventsFromMoreLink(info) {
        let items = uniqueDayBookings(bookingsOn(info.date));
        if (items.length) return items;
        const segs = [...(info.allSegs || []), ...(info.hiddenSegs || [])];
        return uniqueDayBookings(segs.map((seg) => {
            const event = seg.event || {};
            return {
                id: event.id,
                start: event.start,
                end: event.end,
                title: event.title,
                extendedProps: event.extendedProps || {},
            };
        }).filter((event) => event.title || event.extendedProps?.resource));
    }

    function sltCalendarVisible(event) {
        const status = event?.extendedProps?.statusValue || event?.extendedProps?.statusKey;
        return ['approved', 'confirmed', 'in_progress', 'pending_approval', 'changes_requested', 'pending'].includes(status);
    }

    function bookingsOn(date) {
        const key = dateKey(date);
        return uniqueDayBookings(bookings.filter((event) => {
            if (dateKey(event.start) !== key) return false;
            return !isSltEmployee || sltCalendarVisible(event);
        }));
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

    function sltStatusWord(status) {
        if (['approved', 'confirmed', 'in_progress'].includes(status)) return 'Reserved';
        if (['pending_approval', 'changes_requested', 'draft'].includes(status)) return 'Pending';
        if (['cancelled', 'rejected', 'expired'].includes(status)) return 'Cancelled';
        if (status === 'completed') return 'Completed';
        return 'Reserved';
    }

    function sltEventLabel(event) {
        const details = event?.extendedProps || {};
        const status = sltStatusWord(details.statusValue || details.statusKey);
        const resource = String(details.resource || event?.title || '').trim();
        return resource ? `${status}(${resource})` : status;
    }

    function sltChipColors(event) {
        const status = event?.extendedProps?.statusValue || event?.extendedProps?.statusKey;
        if (['approved', 'confirmed', 'in_progress'].includes(status)) return { background: '#16845b', text: '#fff' };
        if (['pending_approval', 'changes_requested', 'draft'].includes(status)) return { background: '#e0a100', text: '#fff' };
        if (['cancelled', 'rejected', 'expired'].includes(status)) return { background: '#7b8493', text: '#fff' };
        if (status === 'completed') return { background: '#4778d0', text: '#fff' };
        return { background: '#16845b', text: '#fff' };
    }

    function monthChipLabel(event) {
        return isSltEmployee
            ? sltEventLabel(event)
            : `${formatChipTime(event.start)} ${event.extendedProps?.resource || event.title}`.trim();
    }

    function monthChipColors(event) {
        return isSltEmployee
            ? sltChipColors(event)
            : eventColors(event.extendedProps?.statusValue || event.extendedProps?.statusKey);
    }

    function openSltBooking(event) {
        const details = event?.extendedProps || {};
        if (details.mine) {
            if (details.url) {
                window.location.href = details.url;
                return;
            }
            showReservationDetails(event);
            return;
        }
        showPrivilegeNotice();
    }

    function showPrivilegeNotice() {
        closeSltMorePop();
        document.getElementById('rc-privilege-overlay')?.remove();
        const overlay = document.createElement('div');
        overlay.id = 'rc-privilege-overlay';
        overlay.className = 'rc-privilege-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-label', 'Not enough privilege to view');
        overlay.innerHTML = '<div class="rc-privilege-card"><p class="rc-privilege-card__title">Not enough privilege to view</p><div class="rc-privilege-card__line"></div></div>';
        const close = (event) => {
            event.preventDefault();
            event.stopPropagation();
            overlay.remove();
            document.removeEventListener('keydown', onKey);
        };
        const onKey = (event) => {
            if (event.key === 'Escape') close(event);
        };
        overlay.addEventListener('click', close);
        document.addEventListener('keydown', onKey);
        document.body.appendChild(overlay);
    }

    function parseLocalDate(value) {
        if (value instanceof Date && !Number.isNaN(value.getTime())) return value;
        const [year, month, day] = String(value || '').slice(0, 10).split('-').map(Number);
        return new Date(year || 1970, (month || 1) - 1, day || 1);
    }

    function formatMorePopoverDate(value) {
        return parseLocalDate(value).toLocaleDateString('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
        });
    }

    function closeSltMorePop() {
        sltMorePop?.remove();
        sltMorePop = null;
        document.removeEventListener('mousedown', onSltMoreOutside);
        document.removeEventListener('keydown', onSltMoreKey);
        window.removeEventListener('resize', closeSltMorePop);
        window.removeEventListener('scroll', closeSltMorePop, true);
    }

    function onSltMoreOutside(event) {
        if (sltMorePop && !sltMorePop.contains(event.target) && !event.target.closest('.rc-month-more')) {
            closeSltMorePop();
        }
    }

    function onSltMoreKey(event) {
        if (event.key === 'Escape') closeSltMorePop();
    }

    function positionSltMorePop(pop, anchor) {
        const rect = (anchor instanceof Element ? anchor : calendarElement).getBoundingClientRect();
        const width = pop.offsetWidth;
        const height = pop.offsetHeight;
        let left = rect.left;
        let top = rect.bottom + 6;
        if (left + width > window.innerWidth - 8) left = window.innerWidth - width - 8;
        if (left < 8) left = 8;
        if (top + height > window.innerHeight - 8) top = Math.max(8, rect.top - height - 6);
        pop.style.left = `${left}px`;
        pop.style.top = `${top}px`;
    }

    function showSltMorePopover(date, items, anchor) {
        closeSltMorePop();
        const bookings = uniqueDayBookings(items.filter((event) => !event.extendedProps?.isHoliday));
        if (!bookings.length) return;
        const pop = document.createElement('div');
        pop.className = 'rc-more-pop';
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-label', formatMorePopoverDate(date));
        pop.innerHTML = `<div class="rc-more-pop__head">
            <span class="rc-more-pop__title">${escapeHtml(formatMorePopoverDate(date))}</span>
            <button type="button" class="rc-more-pop__close" aria-label="Close">&times;</button>
        </div>
        <div class="rc-more-pop__body">${bookings.map((event, index) => {
            const colors = sltChipColors(event);
            const label = sltEventLabel(event);
            return `<button type="button" class="rc-more-pop__item${event.extendedProps?.mine ? ' is-mine' : ''}" data-index="${index}" style="background:${colors.background};color:${colors.text}" title="${escapeHtml(label)}">${escapeHtml(label)}</button>`;
        }).join('')}</div>`;
        document.body.appendChild(pop);
        sltMorePop = pop;
        pop.querySelector('.rc-more-pop__close')?.addEventListener('click', closeSltMorePop);
        pop.querySelectorAll('.rc-more-pop__item').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const booking = bookings[Number(button.dataset.index)];
                if (booking) openSltBooking(booking);
            });
        });
        positionSltMorePop(pop, anchor);
        document.addEventListener('mousedown', onSltMoreOutside);
        document.addEventListener('keydown', onSltMoreKey);
        window.addEventListener('resize', closeSltMorePop);
        window.addEventListener('scroll', closeSltMorePop, true);
    }

    function bindSltMonthActions(summary, date, preview, items) {
        summary.querySelectorAll('.rc-month-chip[data-booking]').forEach((chip) => {
            const stop = (event) => event.stopPropagation();
            chip.addEventListener('pointerdown', stop);
            chip.addEventListener('mousedown', stop);
            chip.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const booking = preview[Number(chip.dataset.booking)];
                if (booking) openSltBooking(booking);
            });
        });
        const more = summary.querySelector('.rc-month-more');
        if (!more) return;
        const stop = (event) => event.stopPropagation();
        more.addEventListener('pointerdown', stop);
        more.addEventListener('mousedown', stop);
        more.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            showSltMorePopover(date, items, more);
        });
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
            frame.classList.remove('is-holiday', 'is-holiday-poya', 'is-holiday-mercantile', 'is-holiday-bank', 'is-holiday-public');
            if (holiday) frame.classList.add('is-holiday', `is-holiday-${kind}`);
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
            const chips = preview.map((event, index) => {
                const colors = monthChipColors(event);
                const label = monthChipLabel(event);
                const mine = event.extendedProps?.mine ? ' is-mine' : '';
                const bookingAttr = isSltEmployee ? ` data-booking="${index}"` : '';
                return `<span class="rc-month-chip${mine}"${bookingAttr} style="background:${colors.background};color:${colors.text}" title="${escapeHtml(label)}">${escapeHtml(label)}</span>`;
            }).join('');
            summary.hidden = false;
            summary.innerHTML = `${holidayChip}<div class="rc-month-chips">${chips}${extra > 0 ? `<span class="rc-month-more" role="button" tabindex="0">+${extra} more</span>` : ''}</div>`;
            if (isSltEmployee) bindSltMonthActions(summary, date, preview, items);
        });
    }

    async function showDayEventList(date, eventList) {
        const dayBookings = uniqueDayBookings(eventList
            .filter((event) => !event.extendedProps?.isHoliday)
            .sort((left, right) => new Date(left.start) - new Date(right.start)));
        const holiday = eventList.find((event) => event.extendedProps?.isHoliday);
        const rows = dayBookings.map((event, index) => {
            const details = event.extendedProps || {};
            const colors = eventColors(details.statusValue || details.statusKey);
            const start = details.reservationStart ? new Date(details.reservationStart)
                : (event.start instanceof Date ? event.start : new Date(event.start));
            const end = details.reservationEnd ? new Date(details.reservationEnd)
                : (event.end instanceof Date ? event.end : new Date(event.end));
            const time = formatStayTimes(start, end, details.duration, details.reservationType);
            const meta = [details.location, details.reference].filter(Boolean).join(' · ');
            const badge = !isSltEmployee && details.status
                ? statusBadge(details.statusValue || details.statusKey, details.status)
                : '';
            const canOpen = !isSltEmployee || Boolean(details.mine && details.url);
            const tag = canOpen ? 'button' : 'div';
            const extra = canOpen ? ` type="button" data-index="${index}"` : '';
            return `<${tag} class="rc-day-item${details.mine ? ' is-mine' : ''}${canOpen ? '' : ' is-locked'}"${extra} style="border-left-color:${colors.border};background:${colors.background};color:${colors.text}">
                <span class="rc-day-item-title">${escapeHtml(details.resource || event.title)}</span>
                <span class="rc-day-item-time">${escapeHtml(time)}</span>
                ${badge || meta ? `<span class="rc-day-item-meta">${badge}${meta ? `<span>${escapeHtml(meta)}</span>` : ''}</span>` : ''}
            </${tag}>`;
        }).join('');
        const holidayRow = holiday
            ? `<div class="rc-day-holiday">${escapeHtml(holiday.title)}${holiday.extendedProps?.holidayTypeLabel ? ` · ${escapeHtml(holiday.extendedProps.holidayTypeLabel)}` : ''}</div>`
            : '';
        await swalFire({
            title: formatDate(date instanceof Date ? date : new Date(date)),
            html: `<div class="rc-day-list">${holidayRow}${rows || '<p class="rc-day-empty">No reservations on this day.</p>'}</div>`,
            confirmButtonText: 'Close',
            showCancelButton: false,
            showDenyButton: false,
            width: 560,
            customClass: {
                ...window.RMSwal.base.customClass,
                container: 'rm-swal-backdrop',
                htmlContainer: 'rc-day-list-wrap',
            },
            didOpen(popup) {
                popup.querySelectorAll('.rc-day-item[data-index]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const event = dayBookings[Number(button.dataset.index)];
                        if (window.Swal) window.Swal.close();
                        if (event) showReservationDetails(event);
                    });
                });
            },
        });
    }

    function goCreate(date) {
        const url = new URL(calendarElement.dataset.createUrl, window.location.origin);
        url.searchParams.set('date', localDateKey(date instanceof Date ? date : new Date(date)));
        window.location.href = url.toString();
    }

    function showReservationDetails(event) {
        const details = event.extendedProps || {};
        const start = details.reservationStart ? new Date(details.reservationStart) : (event.start instanceof Date ? event.start : new Date(event.start));
        const end = details.reservationEnd ? new Date(details.reservationEnd) : (event.end instanceof Date ? event.end : new Date(event.end));
        document.getElementById('reservationModalTitle').textContent = details.reference || 'Reservation details';
        document.getElementById('detailResource').textContent = details.resource || event.title || '—';
        document.getElementById('detailDate').textContent = formatStayDates(start, end);
        document.getElementById('detailTime').textContent = formatStayTimes(start, end, details.duration, details.reservationType);
        document.getElementById('detailOwner').textContent = details.owner || '—';
        document.getElementById('detailLocation').textContent = details.location || '—';
        document.getElementById('detailPurpose').textContent = details.purpose || '—';
        document.getElementById('detailStatus').innerHTML = statusBadge(details.statusValue || details.statusKey, details.status);
        const conflict = document.getElementById('detailConflict');
        const link = document.getElementById('detailLink');
        if (conflict) conflict.hidden = !details.conflict;
        if (link) {
            link.hidden = !details.url;
            if (details.url) link.href = details.url;
        }
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeReservationModal() {
        modal?.classList.remove('show');
        modal?.setAttribute('aria-hidden', 'true');
    }

    function isTimeGrid(viewType) {
        return ['timeGridWeek', 'timeGridDay'].includes(viewType);
    }

    function forCalendarView(eventList, holidayList, viewType) {
        if (!isTimeGrid(viewType)) {
            return [...eventList.map(withEventColors), ...holidayList];
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
            ...holidayList.filter((event) => event.display !== 'background'),
        ];
    }

    function eventColors(status) {
        if (['approved', 'confirmed', 'in_progress', 'completed'].includes(status)) return { background: '#bbf7d0', border: '#059669', text: '#14532d' };
        if (['pending_approval', 'changes_requested', 'draft'].includes(status)) return { background: '#fde68a', border: '#d97706', text: '#78350f' };
        if (['rejected', 'cancelled', 'expired'].includes(status)) return { background: '#fecaca', border: '#dc2626', text: '#7f1d1d' };
        return { background: '#bfdbfe', border: '#2563eb', text: '#1e3a8a' };
    }

    function withEventColors(event) {
        const colors = eventColors(event.extendedProps?.statusValue || event.extendedProps?.statusKey);
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

    function formatClock(date) {
        if (!date || Number.isNaN(date.getTime())) return '—';
        return date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
    }

    function stayEndDate(end) {
        if (end.getHours() === 0 && end.getMinutes() === 0) {
            return new Date(end.getTime() - 1000);
        }
        return end;
    }

    function isOvernightStay(start, end) {
        if (!start || !end || Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return false;
        return localDateKey(start) !== localDateKey(stayEndDate(end));
    }

    function formatStayDates(start, end) {
        if (!start || Number.isNaN(start.getTime())) return '—';
        if (!end || Number.isNaN(end.getTime()) || !isOvernightStay(start, end)) return formatDate(start);
        return `${formatDate(start)} – ${formatDate(stayEndDate(end))}`;
    }

    function formatStayTimes(start, end, duration, type) {
        if (!start || Number.isNaN(start.getTime())) return '—';
        if (type === 'hostel' || isOvernightStay(start, end)) {
            const endLabel = end && !Number.isNaN(end.getTime()) ? formatClock(end) : '—';
            const time = `Check-in ${formatClock(start)} · Check-out ${endLabel}`;
            return duration ? `${time} · ${duration}` : time;
        }
        const time = formatTimeRange(start, end);
        return duration ? `${time} · ${duration}` : time;
    }

    function formatTimeRange(start, end) {
        if (!start || Number.isNaN(start.getTime())) return '—';
        const startText = formatClock(start);
        if (!end || Number.isNaN(end.getTime())) return startText;
        return `${startText} – ${formatClock(end)}`;
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
});
