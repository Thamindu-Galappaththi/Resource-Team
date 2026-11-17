(function () {
    const page = document.querySelector('.rp-history-page');
    if (!page) {
        return;
    }

    const filtersForm = document.getElementById('rph-filters');
    const search = document.getElementById('rph-search');
    const dateFrom = document.getElementById('rph-from');
    const dateTo = document.getElementById('rph-to');
    const clearBtn = document.getElementById('rph-clear');
    const exportBtn = document.getElementById('rph-export');
    const stats = document.getElementById('rph-stats');
    const paymentsPanel = document.getElementById('rph-payments-panel');
    const dashboardPanel = document.getElementById('rph-dashboard-panel');
    const paymentsResults = document.getElementById('rph-payments-results');
    const dashboardResults = document.getElementById('rph-dashboard-results');
    const recordCount = document.getElementById('rph-record-count');
    const baseUrl = page.dataset.rphUrl || window.location.pathname;
    const exportUrl = page.dataset.rphExport || '';
    const requests = {};
    let searchTimer = null;
    let clearing = false;
    let tab = page.dataset.rphTab || 'payments';

    function selectValue(id) {
        return window.RMSelect ? window.RMSelect.getValue(id) : (document.getElementById(id)?.value || '');
    }

    function hasFilters() {
        return Boolean(
            (search?.value || '').trim()
            || selectValue('rph-status')
            || selectValue('rph-type')
            || dateFrom?.value
            || dateTo?.value
        );
    }

    function toggleClear() {
        if (clearBtn) {
            clearBtn.disabled = !hasFilters();
        }
    }

    function filterUrl(extra) {
        const url = new URL(baseUrl, window.location.origin);
        const params = {
            search: (search?.value || '').trim(),
            status: selectValue('rph-status'),
            resource_type: selectValue('rph-type'),
            date_from: dateFrom?.value || '',
            date_to: dateTo?.value || '',
            tab,
            ...(extra || {}),
        };
        Object.entries(params).forEach(([key, value]) => {
            if (value && !(key === 'tab' && value === 'payments')) {
                url.searchParams.set(key, value);
            }
        });
        return url;
    }

    function syncExport() {
        if (!exportBtn || !exportUrl) {
            return;
        }
        const url = new URL(exportUrl, window.location.origin);
        ['search', 'status', 'resource_type', 'date_from', 'date_to'].forEach((key) => {
            const value = filterUrl().searchParams.get(key);
            if (value) {
                url.searchParams.set(key, value);
            }
        });
        exportBtn.href = url.toString();
    }

    function setTab(next) {
        tab = next === 'dashboard' ? 'dashboard' : 'payments';
        page.dataset.rphTab = tab;
        page.querySelectorAll('a[data-rph-tab]').forEach((link) => {
            const active = link.getAttribute('data-rph-tab') === tab;
            link.classList.toggle('is-active', active);
            if (active) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
        if (paymentsPanel) {
            paymentsPanel.hidden = tab !== 'payments';
        }
        if (dashboardPanel) {
            dashboardPanel.hidden = tab !== 'dashboard';
        }
    }

    function load(section, href) {
        const target = section === 'stats' ? stats : (section === 'dashboard' ? dashboardResults : paymentsResults);
        if (!target) {
            return;
        }
        const requestId = (requests[section] = (requests[section] || 0) + 1);
        const url = new URL(href, window.location.origin);
        url.searchParams.set('section', section);
        url.searchParams.set('tab', tab);
        if (section !== 'stats') {
            target.classList.add('is-loading');
        }
        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        }).then((response) => {
            if (!response.ok) {
                throw new Error('Could not load payments.');
            }
            return response.text();
        }).then((html) => {
            if (requests[section] !== requestId) {
                return;
            }
            target.innerHTML = html;
            const total = target.querySelector('[data-total]')?.getAttribute('data-total');
            if (recordCount && total != null) {
                recordCount.textContent = `${Number(total).toLocaleString()} records`;
            }
            if (section !== 'stats') {
                const clean = new URL(href, window.location.origin);
                clean.searchParams.delete('section');
                history.replaceState({}, '', clean.toString());
            }
        }).catch(() => {
            if (requests[section] !== requestId) {
                return;
            }
            window.location.href = href;
        }).finally(() => {
            if (requests[section] === requestId) {
                target.classList.remove('is-loading');
            }
        });
    }

    function refresh(resetPage) {
        toggleClear();
        syncExport();
        const extra = resetPage ? { page: '' } : {};
        const url = filterUrl(extra);
        if (resetPage) {
            url.searchParams.delete('page');
        }
        load('payments', url.toString());
        load('dashboard', url.toString());
        load('stats', url.toString());
    }

    filtersForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        refresh(true);
    });

    search?.addEventListener('input', () => {
        toggleClear();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => refresh(true), 350);
    });

    ['rph-status', 'rph-type'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            if (!clearing) {
                refresh(true);
            }
        });
    });
    [dateFrom, dateTo].forEach((input) => {
        input?.addEventListener('change', () => {
            if (!clearing) {
                refresh(true);
            }
        });
    });

    clearBtn?.addEventListener('click', () => {
        if (clearBtn.disabled) {
            return;
        }
        clearing = true;
        if (search) {
            search.value = '';
        }
        if (dateFrom) {
            dateFrom.value = '';
        }
        if (dateTo) {
            dateTo.value = '';
        }
        if (window.RMSelect) {
            window.RMSelect.setValue('rph-status', '', 'All statuses');
            window.RMSelect.setValue('rph-type', '', 'All types');
        }
        clearing = false;
        refresh(true);
    });

    page.addEventListener('click', (event) => {
        const tabLink = event.target.closest('a[data-rph-tab]');
        if (tabLink && page.contains(tabLink)) {
            event.preventDefault();
            const next = tabLink.getAttribute('data-rph-tab');
            if (next === tab) {
                return;
            }
            setTab(next);
            history.replaceState({}, '', filterUrl().toString());
            return;
        }

        const paidLink = event.target.closest('[data-rph-paid]');
        if (paidLink) {
            event.preventDefault();
            clearing = true;
            if (window.RMSelect) {
                window.RMSelect.setValue('rph-status', 'paid', 'Paid');
            }
            clearing = false;
            setTab('payments');
            refresh(true);
            return;
        }

        const pageLink = event.target.closest('[data-rr-history], .rsv-pager a.page-link');
        if (pageLink && page.contains(pageLink)) {
            event.preventDefault();
            event.stopPropagation();
            load(pageLink.getAttribute('data-rr-history') || 'payments', pageLink.href);
        }
    });

    toggleClear();
    syncExport();
}());
