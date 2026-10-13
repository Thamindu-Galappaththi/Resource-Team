(function () {
    const config = window.RSVIndex || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const filterIds = ['search', 'from_date', 'to_date', 'location_id', 'resource_category_id', 'resource_id', 'status'];
    const placeholders = {
        location_id: 'All locations',
        resource_category_id: 'All categories',
        resource_id: 'All resources',
        status: 'All statuses',
    };

    let rows = new Map();
    let active = null;
    let request = null;
    let searchTimer = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[char]));
    }

    function headers() {
        return {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
        };
    }

    function currentParams() {
        const params = {};
        filterIds.forEach((id) => {
            const value = (document.getElementById(id)?.value || '').trim();
            if (value) params[id] = value;
        });
        return params;
    }

    function hasFilters() {
        return Object.keys(currentParams()).length > 0;
    }

    function toggleClear() {
        const clear = document.getElementById('rsv-clear');
        if (clear) clear.disabled = !hasFilters();
    }

    function setLoading(isLoading) {
        const overlay = document.getElementById('rsv-loading');
        document.getElementById('rsv-results')?.classList.toggle('is-loading', isLoading);
        if (overlay) overlay.hidden = !isLoading;
    }

    function dismissAlert(alert, delay = 5000) {
        if (!alert || alert.classList.contains('d-none')) return;
        clearTimeout(alert._rsvTimer);
        alert._rsvTimer = setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.id === 'rsv-flash') {
                    alert.classList.add('d-none');
                    alert.style.opacity = '';
                    alert.textContent = '';
                } else {
                    alert.remove();
                }
            }, 300);
        }, delay);
    }

    function showFlash(message, type = 'success') {
        const flash = document.getElementById('rsv-flash');
        if (!flash || !message) return;
        flash.className = `alert alert-${type} rsv-auto-alert`;
        flash.textContent = message;
        flash.style.opacity = '1';
        dismissAlert(flash);
    }

    function applySummary(summary) {
        if (!summary) return;
        const format = (value) => Number(value || 0).toLocaleString();
        const map = { total: 'rsv-stat-total', pending: 'rsv-stat-pending', approved: 'rsv-stat-approved', cancelled: 'rsv-stat-cancelled' };
        Object.entries(map).forEach(([key, id]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = format(summary[key]);
        });
    }

    function actionButtons(row) {
        let html = '';
        if (row.can_view) {
            html += `<button type="button" class="btn btn-sm btn-outline-secondary rsv-icon-btn" data-rsv-view="${row.id}" title="View" aria-label="View reservation ${escapeHtml(row.reference)}"><i class="ti ti-eye"></i></button>`;
        }
        if (row.can_cancel) {
            html += `<button type="button" class="btn btn-sm btn-outline-danger rsv-icon-btn" data-rsv-cancel="${row.id}" title="Cancel" aria-label="Cancel reservation ${escapeHtml(row.reference)}"><i class="ti ti-x"></i></button>`;
        }
        return html ? `<span class="rsv-action-group">${html}</span>` : '—';
    }

    function renderPagination(payload) {
        const meta = payload.meta || {};
        const links = payload.links || {};
        if (!meta.total || (meta.last_page || 1) <= 1) return '';
        const pages = (links.pages || []).map((page) => (
            `<li class="page-item ${page.active ? 'active' : ''}">
                <a class="page-link" href="${escapeHtml(page.url)}" data-rsv-page>${page.page}</a>
            </li>`
        )).join('');
        const prev = links.prev
            ? `<li class="page-item"><a class="page-link" href="${escapeHtml(links.prev)}" data-rsv-page>‹</a></li>`
            : `<li class="page-item disabled"><span class="page-link">‹</span></li>`;
        const next = links.next
            ? `<li class="page-item"><a class="page-link" href="${escapeHtml(links.next)}" data-rsv-page>›</a></li>`
            : `<li class="page-item disabled"><span class="page-link">›</span></li>`;
        return `<div class="rsv-pager">
            <p class="small text-muted mb-0">Showing ${meta.from}–${meta.to} of ${meta.total}</p>
            <ul class="pagination mb-0">${prev}${pages}${next}</ul>
        </div>`;
    }

    function render(payload) {
        rows = new Map((payload.reservations || []).map((row) => [String(row.id), row]));
        applySummary(payload.summary);
        toggleClear();
        const root = document.getElementById('rsv-results');
        if (!root) return;
        const list = payload.reservations || [];
        if (!list.length) {
            root.innerHTML = `<div class="rsv-empty">
                <i class="ti ti-calendar-off d-block mb-2"></i>
                <p class="mb-0 fw-semibold">No reservations found.</p>
                <p class="small mb-0">Try a different search or clear the filters.</p>
            </div>`;
            return;
        }
        const body = list.map((row) => `<tr>
            <td class="rsv-ref" data-label="Reservation ID">${escapeHtml(row.reference)}</td>
            <td class="rsv-purpose" data-label="Purpose">${escapeHtml(row.purpose || '—')}</td>
            <td class="rsv-resource" data-label="Resource">${escapeHtml(row.resource_names)}</td>
            <td class="rsv-hide-md" data-label="Requester">${escapeHtml(row.requester)}</td>
            <td class="text-nowrap" data-label="Schedule"><div>${escapeHtml(row.date)}</div><div class="small text-muted">${escapeHtml(row.time)}</div></td>
            <td class="rsv-hide-md" data-label="Location">${escapeHtml(row.location)}</td>
            <td data-label="Status"><span class="badge ${escapeHtml(row.status_badge)}">${escapeHtml(row.status_label)}</span></td>
            <td class="text-end text-nowrap rsv-actions" data-label="Actions">${actionButtons(row)}</td>
        </tr>`).join('');
        root.innerHTML = `<div class="table-responsive">
            <table class="table rsv-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Reservation ID</th>
                        <th>Purpose</th>
                        <th>Resource</th>
                        <th class="rsv-hide-md">Requester</th>
                        <th>Schedule</th>
                        <th class="rsv-hide-md">Location</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        </div>${renderPagination(payload)}`;
    }

    function closeModals() {
        ['rsv-view-modal', 'rsv-cancel-modal'].forEach((id) => {
            const modal = document.getElementById(id);
            bootstrap.Modal.getInstance(modal)?.hide();
        });
        document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    function fetchList(url, push = true) {
        closeModals();
        const target = url || `${config.url}${Object.keys(currentParams()).length ? `?${new URLSearchParams(currentParams())}` : ''}`;
        if (request) request.abort();
        setLoading(true);
        request = new AbortController();
        fetch(target, { headers: headers(), signal: request.signal, credentials: 'same-origin' })
            .then((response) => {
                if (!response.ok) throw new Error('Could not update the list.');
                return response.json();
            })
            .then((payload) => {
                render(payload);
                if (push) {
                    const next = new URL(target, window.location.origin);
                    history.replaceState({}, '', `${window.location.pathname}${next.search}`);
                }
            })
            .catch((error) => {
                if (error.name !== 'AbortError') showFlash(error.message || 'Could not update the list.', 'danger');
            })
            .finally(() => setLoading(false));
    }

    function detailRow(label, value) {
        return `<div class="rsv-detail-row"><span class="rsv-detail-label">${escapeHtml(label)}</span><span class="rsv-detail-value">${escapeHtml(value ?? '—')}</span></div>`;
    }

    function openView(id) {
        const row = rows.get(String(id));
        if (!row) return;
        active = row;
        document.getElementById('rsv-view-title').textContent = row.reference;
        document.getElementById('rsv-view-sub').textContent = row.purpose || row.title || 'Reservation details';
        const badge = document.getElementById('rsv-view-badge');
        badge.className = `badge ${row.status_badge}`;
        badge.textContent = row.status_label;
        const items = (row.items || []).length
            ? row.items.map((item) => `<tr><td>${escapeHtml(item.name)}</td><td>${escapeHtml(item.category)}</td><td>${escapeHtml(item.status)}</td></tr>`).join('')
            : '<tr><td colspan="3" class="text-muted">No resources recorded.</td></tr>';
        const history = (row.history || []).length
            ? row.history.map((item) => `<div class="rsv-history">
                <div class="rsv-history-title">${escapeHtml(item.from ? `${item.from} → ${item.to}` : item.to)}</div>
                <div class="rsv-history-meta">${escapeHtml(item.actor)} · ${escapeHtml(item.at || '')}</div>
                ${item.reason ? `<div class="small mt-1">${escapeHtml(item.reason)}</div>` : ''}
            </div>`).join('')
            : '<p class="small text-muted mb-0">No history recorded.</p>';
        document.getElementById('rsv-view-body').innerHTML = `<div class="row g-4">
            <div class="col-lg-7">
                <h3 class="rsv-section-title">Reservation details</h3>
                <div class="rsv-details">
                    ${detailRow('Purpose', row.purpose || '—')}
                    ${detailRow('Requester', row.requester)}
                    ${detailRow('Created by', row.created_by)}
                    ${detailRow('Location', row.location)}
                    ${detailRow('Date', row.date)}
                    ${detailRow('Time', `${row.time} (Asia/Colombo)`)}
                    ${detailRow('Attendees', row.attendee_count ?? '—')}
                    ${row.cancellation_reason ? detailRow('Cancel reason', row.cancellation_reason) : ''}
                </div>
            </div>
            <aside class="col-lg-5">
                <h3 class="rsv-section-title">Status history</h3>
                ${history}
            </aside>
        </div>
        <section class="rsv-booked">
            <h3 class="rsv-section-title">Booked resources</h3>
            <div class="table-responsive rsv-mini-table">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Resource</th><th>Category</th><th>Item status</th></tr></thead>
                    <tbody>${items}</tbody>
                </table>
            </div>
        </section>`;
        const cancelBtn = document.getElementById('rsv-view-cancel');
        cancelBtn.hidden = !row.can_cancel;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('rsv-view-modal')).show();
    }

    function openCancel(id) {
        const row = rows.get(String(id)) || active;
        if (!row || !row.can_cancel) return;
        active = row;
        document.getElementById('rsv-cancel-title').textContent = `Cancel ${row.reference}`;
        const reason = document.getElementById('rsv-cancel-reason');
        const error = document.getElementById('rsv-cancel-error');
        reason.value = '';
        reason.classList.remove('is-invalid');
        error.classList.add('d-none');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('rsv-view-modal')).hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('rsv-cancel-modal')).show();
    }

    function resetFilters() {
        document.getElementById('search').value = '';
        document.getElementById('from_date').value = '';
        document.getElementById('to_date').value = '';
        Object.entries(placeholders).forEach(([id, label]) => window.RMSelect?.setValue(id, '', label));
        toggleClear();
        fetchList(config.url);
    }

    document.getElementById('rsv-filters')?.addEventListener('submit', (event) => {
        event.preventDefault();
        fetchList();
    });
    document.getElementById('rsv-clear')?.addEventListener('click', resetFilters);
    document.getElementById('search')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => fetchList(), 350);
        toggleClear();
    });
    ['from_date', 'to_date', 'location_id', 'resource_category_id', 'resource_id', 'status'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            toggleClear();
            fetchList();
        });
    });

    document.getElementById('rsv-results')?.addEventListener('click', (event) => {
        const view = event.target.closest('[data-rsv-view]');
        if (view) {
            event.preventDefault();
            openView(view.dataset.rsvView);
            return;
        }
        const cancel = event.target.closest('[data-rsv-cancel]');
        if (cancel) {
            event.preventDefault();
            openCancel(cancel.dataset.rsvCancel);
            return;
        }
        const page = event.target.closest('[data-rsv-page]');
        if (page) {
            event.preventDefault();
            fetchList(page.href);
        }
    });

    document.getElementById('rsv-view-cancel')?.addEventListener('click', () => {
        if (active) openCancel(active.id);
    });

    document.getElementById('rsv-cancel-form')?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!active?.cancel_url) return;
        const reason = document.getElementById('rsv-cancel-reason');
        const error = document.getElementById('rsv-cancel-error');
        const submit = document.getElementById('rsv-cancel-submit');
        const body = new FormData();
        body.set('cancellation_reason', reason.value.trim());
        submit.disabled = true;
        fetch(active.cancel_url, { method: 'POST', headers: headers(), body, credentials: 'same-origin' })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (response.status === 422) {
                    const message = data.errors?.cancellation_reason?.[0] || data.message || 'Reason is required.';
                    reason.classList.add('is-invalid');
                    error.textContent = message;
                    error.classList.remove('d-none');
                    throw new Error('invalid');
                }
                if (!response.ok) throw new Error(data.message || 'Could not cancel this reservation.');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('rsv-cancel-modal')).hide();
                showFlash(data.message || 'Reservation cancelled successfully.');
                fetchList();
            })
            .catch((error) => {
                if (error.message !== 'invalid') showFlash(error.message, 'danger');
            })
            .finally(() => { submit.disabled = false; });
    });

    document.querySelectorAll('.rsv-auto-alert').forEach((alert) => dismissAlert(alert));
    if (config.payload) render(config.payload);
})();
