document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('.ap-page');
    if (!page) return;

    const config = window.APIndex || {};
    const form = document.getElementById('ap-filters');
    const clearButton = document.getElementById('ap-clear');
    const search = document.getElementById('search');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const canDecide = Boolean(config.canDecide);
    const viewModalEl = document.getElementById('ap-view-modal');
    const viewModal = viewModalEl && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(viewModalEl) : null;
    const swalFire = (options) => window.RMSwal.fire(options);
    const filterIds = ['search', 'from_date', 'to_date', 'location_id', 'category_id', 'status'];
    const placeholders = {
        location_id: 'All locations',
        category_id: 'All categories',
        status: 'All decisions',
    };
    let records = new Map();
    let searchTimer = null;
    let request = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[character]));
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
        if (clearButton) clearButton.disabled = !hasFilters();
    }

    function headers() {
        return {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
        };
    }

    function actionButtons(row) {
        const view = `<button type="button" class="btn btn-sm btn-outline-secondary rsv-icon-btn" data-ap-view="${row.id}" title="View" aria-label="View ${escapeHtml(row.reference)}"><i class="ti ti-eye"></i></button>`;
        const archive = canDecide
            ? `<button type="button" class="btn btn-sm btn-outline-danger rsv-icon-btn" data-ap-archive="${row.id}" title="Remove" aria-label="Remove ${escapeHtml(row.reference)} from approvals"><i class="ti ti-trash"></i></button>`
            : '';
        return `<span class="rsv-action-group">${view}${archive}</span>`;
    }

    function renderPager(pager) {
        if (!pager?.total) return '';
        if ((pager.last || 1) <= 1) {
            return `<div class="rsv-pager"><p class="small text-muted mb-0">Showing ${pager.from}–${pager.to} of ${pager.total}</p></div>`;
        }
        const pages = (pager.pages || []).map((page, index, list) => {
            const gap = index && page.page - list[index - 1].page > 1
                ? '<li class="page-item disabled"><span class="page-link">…</span></li>'
                : '';
            const item = page.active
                ? `<li class="page-item active"><span class="page-link">${page.page}</span></li>`
                : `<li class="page-item"><a class="page-link" href="${escapeHtml(page.url)}" data-ap-page>${page.page}</a></li>`;
            return `${gap}${item}`;
        }).join('');
        const prev = pager.prev
            ? `<li class="page-item"><a class="page-link" href="${escapeHtml(pager.prev)}" data-ap-page rel="prev">‹</a></li>`
            : '<li class="page-item disabled"><span class="page-link">‹</span></li>';
        const next = pager.next
            ? `<li class="page-item"><a class="page-link" href="${escapeHtml(pager.next)}" data-ap-page rel="next">›</a></li>`
            : '<li class="page-item disabled"><span class="page-link">›</span></li>';
        return `<div class="rsv-pager">
            <p class="small text-muted mb-0">Showing ${pager.from}–${pager.to} of ${pager.total}</p>
            <ul class="pagination mb-0">${prev}${pages}${next}</ul>
        </div>`;
    }

    function limitText(value, max) {
        const text = String(value ?? '').trim();
        return text.length > max ? `${text.slice(0, max - 1).trimEnd()}…` : text;
    }

    function renderTable(rows, pager, options) {
        if (!rows.length) {
            return `<div class="rsv-empty">
                <i class="ti ${options.emptyIcon} d-block mb-2"></i>
                <p class="mb-0 fw-semibold">${escapeHtml(options.emptyTitle)}</p>
                <p class="small mb-0">${escapeHtml(options.emptyCopy)}</p>
            </div>`;
        }
        const body = rows.map((row) => `<tr>
            <td class="rsv-ref" data-label="Reservation ID">${escapeHtml(row.reference)}</td>
            <td class="rsv-purpose" data-label="Purpose" title="${escapeHtml(row.title)}">${escapeHtml(limitText(row.title, 40))}</td>
            <td class="rsv-resource" data-label="Resource">${escapeHtml(row.resources)}</td>
            <td class="rsv-hide-md" data-label="Requester">${escapeHtml(row.requester)}</td>
            <td class="text-nowrap" data-label="Schedule"><div>${escapeHtml(row.short_date || row.date)}</div><div class="small text-muted">${escapeHtml(row.time)}</div></td>
            <td data-label="${escapeHtml(options.statusLabel)}"><span class="badge ${escapeHtml(row.status_badge)}">${escapeHtml(row.status_label)}</span></td>
            <td class="text-end text-nowrap rsv-actions" data-label="Actions">${actionButtons(row)}</td>
        </tr>`).join('');
        return `<div class="table-responsive">
            <table class="table rsv-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Reservation ID</th>
                        <th>Purpose</th>
                        <th>Resource</th>
                        <th class="rsv-hide-md">Requester</th>
                        <th>Schedule</th>
                        <th>${escapeHtml(options.statusLabel)}</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        </div>${renderPager(pager)}`;
    }

    function applyPayload(payload) {
        records = new Map([...(payload.pending || []), ...(payload.history || [])].map((row) => [String(row.id), row]));
        const pendingEl = document.getElementById('ap-stat-pending');
        const historyEl = document.getElementById('ap-stat-history');
        if (pendingEl) pendingEl.textContent = Number(payload.summary?.pending || 0).toLocaleString();
        if (historyEl) historyEl.textContent = Number(payload.summary?.history || 0).toLocaleString();
        const pendingCount = document.getElementById('ap-pending-count');
        if (pendingCount) pendingCount.textContent = `${payload.summary?.pending || 0} awaiting a decision`;
        const pendingRoot = document.getElementById('ap-pending-results');
        const historyRoot = document.getElementById('ap-history-results');
        if (pendingRoot) {
            pendingRoot.innerHTML = renderTable(payload.pending || [], payload.pending_pager, {
                statusLabel: 'Status',
                emptyIcon: 'ti-circle-check',
                emptyTitle: 'You’re all caught up',
                emptyCopy: 'No pending requests match these filters.',
            });
        }
        if (historyRoot) {
            historyRoot.innerHTML = renderTable(payload.history || [], payload.history_pager, {
                statusLabel: 'Decision',
                emptyIcon: 'ti-history',
                emptyTitle: 'No decisions match these filters.',
                emptyCopy: 'Try a different search or clear the filters.',
            });
        }
        toggleClear();
    }

    function fetchList(url, push = true) {
        const target = url || `${config.url}${Object.keys(currentParams()).length ? `?${new URLSearchParams(currentParams())}` : ''}`;
        if (request) request.abort();
        request = new AbortController();
        page.classList.add('is-loading');
        fetch(target, { headers: headers(), signal: request.signal, credentials: 'same-origin' })
            .then((response) => {
                if (!response.ok) throw new Error('Could not update the list.');
                return response.json();
            })
            .then((payload) => {
                applyPayload(payload);
                if (push) {
                    const next = new URL(target, window.location.origin);
                    history.replaceState({}, '', `${window.location.pathname}${next.search}`);
                }
            })
            .catch((error) => {
                if (error.name !== 'AbortError') console.error(error);
            })
            .finally(() => page.classList.remove('is-loading'));
    }

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        fetchList();
    });
    ['from_date', 'to_date', 'location_id', 'category_id', 'status'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            toggleClear();
            fetchList();
        });
    });
    search?.addEventListener('input', () => {
        toggleClear();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => fetchList(), 350);
    });
    clearButton?.addEventListener('click', () => {
        if (clearButton.disabled) return;
        search.value = '';
        document.getElementById('from_date').value = '';
        document.getElementById('to_date').value = '';
        Object.entries(placeholders).forEach(([id, label]) => window.RMSelect.setValue(id, '', label));
        toggleClear();
        fetchList(config.url);
    });

    page.addEventListener('click', (event) => {
        const pageLink = event.target.closest('[data-ap-page]');
        if (pageLink) {
            event.preventDefault();
            fetchList(pageLink.href);
            return;
        }
        const viewButton = event.target.closest('[data-ap-view]');
        if (viewButton) {
            const row = records.get(String(viewButton.getAttribute('data-ap-view')));
            if (!row) return;
            fillView(row);
            viewModal?.show();
            return;
        }
        const archiveButton = event.target.closest('[data-ap-archive]');
        if (archiveButton) {
            event.preventDefault();
            archiveRecord(archiveButton.getAttribute('data-ap-archive'));
        }
    });

    function detailRow(label, value) {
        return `<div class="rsv-detail-row"><div class="rsv-detail-label">${escapeHtml(label)}</div><div class="rsv-detail-value">${value || '—'}</div></div>`;
    }

    function fillView(row) {
        document.getElementById('ap-view-title').textContent = row.reference || 'Reservation';
        document.getElementById('ap-view-sub').textContent = row.title || 'Booking information';
        const badge = document.getElementById('ap-view-badge');
        badge.className = `badge ${row.status_badge || 'bg-secondary'}`;
        badge.textContent = row.status_label || '—';
        const resources = (row.resource_badges || []).map((item) => `<span class="badge bg-light text-dark border me-1 mb-1">${escapeHtml(item)}</span>`).join('')
            || escapeHtml(row.resources || '—');
        document.getElementById('ap-view-body').innerHTML = `
            <div class="rsv-details">
                ${detailRow('Requester', escapeHtml([row.requester, row.requester_email].filter(Boolean).join(' · ')))}
                ${detailRow('Submitted', escapeHtml(row.submitted || '—'))}
                ${detailRow('Schedule', escapeHtml(`${row.date || '—'} · ${row.time || '—'}`))}
                ${detailRow('Location', escapeHtml(row.location || '—'))}
                ${detailRow('Attendees', escapeHtml(row.attendees ?? '—'))}
                ${detailRow('Purpose', escapeHtml(row.purpose || '—'))}
                ${detailRow('Resources', resources)}
                ${row.description ? detailRow('Additional details', escapeHtml(row.description)) : ''}
                ${row.comment ? detailRow('Latest comment', escapeHtml(row.comment)) : ''}
            </div>
            ${row.url ? `<p class="mt-3 mb-0"><a href="${escapeHtml(row.url)}" class="small text-decoration-none"><i class="ti ti-external-link me-1"></i>Open full reservation page</a></p>` : ''}
        `;
        const comment = document.getElementById('ap-decision-comment');
        const decisionForm = document.getElementById('ap-decision-form');
        const showDecision = canDecide && row.pending;
        if (decisionForm) {
            decisionForm.action = row.decision_url || '';
            decisionForm.hidden = !showDecision;
        }
        if (comment) comment.value = '';
    }

    async function archiveRecord(id) {
        const row = records.get(String(id));
        if (!row || !canDecide) return;
        const result = await swalFire({
            icon: 'warning',
            title: 'Remove from Approvals?',
            text: 'The reservation and its history stay in Existing Reservations.',
            input: 'textarea',
            inputPlaceholder: 'Add a reason for the audit history',
            inputAttributes: { maxlength: '1000' },
            showCancelButton: true,
            confirmButtonText: 'Remove',
            cancelButtonText: 'Keep',
            customClass: {
                ...window.RMSwal.base.customClass,
                container: 'rm-swal-backdrop',
                confirmButton: 'btn btn-danger rm-swal-btn',
            },
            inputValidator: (value) => (value && value.trim() ? undefined : 'A reason is required.'),
        });
        if (!result.isConfirmed) return;

        const formEl = document.createElement('form');
        formEl.method = 'POST';
        formEl.action = row.archive_url;
        formEl.innerHTML = `
            <input type="hidden" name="_token" value="${escapeHtml(csrf || '')}">
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="archive_reason" value="${escapeHtml(String(result.value || '').trim())}">
        `;
        document.body.appendChild(formEl);
        formEl.submit();
    }

    document.querySelectorAll('.rsv-auto-alert').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    if (config.payload) applyPayload(config.payload);
    else toggleClear();
});
