(function () {
    const config = window.RSVCal || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const filterLabels = {
        locationFilter: 'All locations',
        categoryFilter: 'All categories',
        resourceFilter: 'All resources',
        statusFilter: 'All statuses',
    };

    let selectedYear = Number(config.year);
    let request = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[char]));
    }

    function limit(value, max) {
        const text = String(value || '');
        return text.length > max ? `${text.slice(0, max - 1)}…` : text;
    }

    function bookingsFor(iso) {
        return config.reservationsByDate?.[iso] || [];
    }

    function chipClass(status) {
        if (status === 'pending_approval' || status === 'changes_requested') return 'is-pending';
        if (status === 'approved' || status === 'confirmed') return 'is-approved';
        return '';
    }

    function currentFilters() {
        return {
            location_id: document.getElementById('locationFilter')?.value || '',
            resource_category_id: document.getElementById('categoryFilter')?.value || '',
            resource_id: document.getElementById('resourceFilter')?.value || '',
            status: document.getElementById('statusFilter')?.value || '',
        };
    }

    function queryFrom(year, month, extra = {}) {
        const params = new URLSearchParams();
        params.set('year', String(year));
        params.set('month', String(month));
        Object.entries({ ...currentFilters(), ...extra }).forEach(([key, value]) => {
            if (value) params.set(key, String(value));
        });
        return params;
    }

    function setLoading(isLoading) {
        const board = document.getElementById('rsv-board');
        const overlay = document.getElementById('rsv-loading');
        board?.classList.toggle('is-loading', isLoading);
        if (overlay) overlay.hidden = !isLoading;
    }

    function renderList(iso) {
        const list = document.getElementById('reservationList');
        const bookings = bookingsFor(iso);
        const createLink = document.getElementById('createForDate');
        if (createLink) {
            createLink.href = `${config.createUrl}?date=${encodeURIComponent(iso)}`;
        }
        if (!list) return;

        if (!bookings.length) {
            list.innerHTML = '<p class="rsv-empty-day">No bookings for this day.</p>';
            return;
        }

        list.innerHTML = bookings.map((reservation) => {
            const title = escapeHtml(reservation.reference || reservation.title || 'Reservation');
            const purpose = escapeHtml(reservation.title || reservation.purpose || '');
            const status = escapeHtml(reservation.status_label || '');
            const resources = escapeHtml(reservation.resources || '');
            const meta = [reservation.requester, reservation.location].filter(Boolean).map(escapeHtml).join(' · ');
            const time = `${escapeHtml(reservation.start_time)} – ${escapeHtml(reservation.end_time)}`;
            const url = reservation.url ? `<a class="btn btn-sm btn-outline-primary mt-2" href="${escapeHtml(reservation.url)}">View details</a>` : '';

            return `<article class="rsv-item">
                <div class="d-flex justify-content-between gap-2">
                    <h6>${title}</h6>
                    <span class="badge ${chipClass(reservation.status) === 'is-pending' ? 'text-bg-warning' : 'text-bg-primary'}">${status}</span>
                </div>
                ${purpose ? `<p>${purpose}</p>` : ''}
                ${resources ? `<small class="text-muted d-block">${resources}</small>` : ''}
                ${meta ? `<small class="text-muted d-block">${meta}</small>` : ''}
                <small class="text-muted d-block">${time}</small>
                ${url}
            </article>`;
        }).join('');
    }

    function bindDayCells() {
        document.querySelectorAll('.rsv-cell.is-day').forEach((cell) => {
            cell.addEventListener('click', () => {
                const iso = cell.dataset.date;
                const selectedDate = document.getElementById('selectedDate');
                if (selectedDate) selectedDate.textContent = cell.dataset.displayDate || iso;
                renderList(iso);
            });
        });
    }

    function renderGrid(cells) {
        const grid = document.getElementById('rsv-grid');
        if (!grid) return;

        grid.innerHTML = cells.map((cell) => {
            if (cell.empty) {
                return '<div class="rsv-cell is-empty" aria-hidden="true"></div>';
            }

            const classes = ['rsv-cell', 'is-day'];
            if (cell.isToday) classes.push('is-today');
            if (cell.isWeekend) classes.push('is-weekend');
            const countLabel = cell.count
                ? `, ${cell.count} booking${cell.count === 1 ? '' : 's'}`
                : '';
            const chips = (cell.preview || []).map((booking) => {
                const statusClass = `is-${String(booking.status || '').replaceAll('_', '-')}`;
                const label = `${booking.start_time || ''} ${limit(booking.title || booking.reference, 18)}`.trim();
                return `<span class="rsv-chip ${statusClass}">${escapeHtml(label)}</span>`;
            }).join('');
            const more = cell.count > 2 ? `<span class="rsv-more">+${cell.count - 2} more</span>` : '';

            return `<button type="button" class="${classes.join(' ')}" data-bs-toggle="modal" data-bs-target="#eventModal" data-date="${escapeHtml(cell.iso)}" data-display-date="${escapeHtml(cell.display)}" aria-label="${escapeHtml(cell.display)}${escapeHtml(countLabel)}">
                <span class="rsv-daynum">${cell.day}</span>
                <span class="rsv-events">${chips}${more}</span>
            </button>`;
        }).join('');

        bindDayCells();
    }

    function applyPayload(payload, { push = true } = {}) {
        config.year = payload.year;
        config.month = payload.month;
        config.prev = payload.prev;
        config.next = payload.next;
        config.query = payload.calendarQuery || {};
        config.reservationsByDate = payload.reservationsByDate || {};
        selectedYear = Number(payload.year);

        document.getElementById('rsv-year').value = payload.year;
        document.getElementById('rsv-month').value = payload.month;
        const monthLabel = document.getElementById('rsv-month-label');
        if (monthLabel) monthLabel.textContent = payload.label;
        const yearTitle = document.getElementById('yearTitle');
        if (yearTitle) yearTitle.textContent = payload.year;

        document.getElementById('rsv-stat-month').textContent = payload.stats.month;
        document.getElementById('rsv-stat-today').textContent = payload.stats.today;
        document.getElementById('rsv-stat-pending').textContent = payload.stats.pending;
        document.getElementById('rsv-stat-approved').textContent = payload.stats.approved;

        document.querySelectorAll('.rsv-month-pick').forEach((button) => {
            button.classList.toggle('is-current', Number(button.dataset.month) === Number(payload.month));
        });

        const params = queryFrom(payload.year, payload.month);
        document.getElementById('rsv-prev')?.setAttribute('href', `${config.calendarUrl}?${queryFrom(payload.prev.year, payload.prev.month)}`);
        document.getElementById('rsv-next')?.setAttribute('href', `${config.calendarUrl}?${queryFrom(payload.next.year, payload.next.month)}`);
        document.getElementById('rsv-clear')?.setAttribute('href', `${config.calendarUrl}?year=${payload.year}&month=${payload.month}`);

        renderGrid(payload.cells || []);
        const url = `${config.calendarUrl}?${params.toString()}`;
        if (push) history.pushState({ rsv: true }, '', url);
        else history.replaceState({ rsv: true }, '', url);
    }

    async function load(params, { push = true } = {}) {
        if (request) request.abort();
        request = new AbortController();
        setLoading(true);

        try {
            const response = await fetch(`${config.calendarUrl}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                signal: request.signal,
            });
            if (!response.ok) throw new Error('Could not update the calendar.');
            const payload = await response.json();
            applyPayload(payload, { push });
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.href = `${config.calendarUrl}?${params.toString()}`;
            }
        } finally {
            setLoading(false);
        }
    }

    function navigateTo(year, month, extra) {
        load(queryFrom(year, month, extra));
    }

    function interceptNav(event, year, month) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        event.preventDefault();
        navigateTo(year, month);
    }

    document.getElementById('rsv-prev')?.addEventListener('click', (event) => {
        interceptNav(event, config.prev.year, config.prev.month);
    });

    document.getElementById('rsv-next')?.addEventListener('click', (event) => {
        interceptNav(event, config.next.year, config.next.month);
    });

    document.getElementById('rsv-filters')?.addEventListener('submit', (event) => {
        event.preventDefault();
        navigateTo(config.year, config.month);
    });

    document.getElementById('rsv-clear')?.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        event.preventDefault();
        Object.keys(filterLabels).forEach((id) => {
            window.RMSelect?.setValue(id, '', filterLabels[id]);
        });
        load(queryFrom(config.year, config.month));
    });

    const yearTitle = document.getElementById('yearTitle');
    document.getElementById('previousYear')?.addEventListener('click', () => {
        selectedYear -= 1;
        if (yearTitle) yearTitle.textContent = selectedYear;
    });
    document.getElementById('nextYear')?.addEventListener('click', () => {
        selectedYear += 1;
        if (yearTitle) yearTitle.textContent = selectedYear;
    });

    document.querySelectorAll('.rsv-month-pick').forEach((button) => {
        button.addEventListener('click', () => {
            bootstrap.Modal.getInstance(document.getElementById('monthModal'))?.hide();
            navigateTo(selectedYear, Number(button.dataset.month));
        });
    });

    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        load(params, { push: false });
    });

    bindDayCells();
})();
