(function () {
    const page = document.querySelector('.rpt-page');
    if (!page) return;

    const config = window.RPTIndex || {};
    const form = document.getElementById('rpt-filter-form');
    const results = document.getElementById('rpt-results');
    const loading = document.getElementById('rpt-loading');
    const clearButton = document.getElementById('rpt-clear');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const filterIds = ['from_date', 'to_date', 'location_id', 'resource_category_id', 'status', 'search'];
    const placeholders = {
        location_id: 'All locations',
        resource_category_id: 'All Categories',
        status: 'All statuses',
    };
    const pageKeys = ['util_page', 'res_page', 'lecturer_page'];

    let request = null;
    let searchTimer = null;
    let trendChart = null;
    let catChart = null;
    let activeTab = new URLSearchParams(window.location.search).get('tab') || 'facility';

    function headers() {
        return {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
        };
    }

    function ymd(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function presetRange(name) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const from = new Date(today);
        switch (name) {
            case 'today':
                break;
            case 'week': {
                const weekday = today.getDay() || 7;
                from.setDate(today.getDate() - weekday + 1);
                break;
            }
            case 'month':
                from.setDate(1);
                break;
            case 'last30':
                from.setDate(today.getDate() - 29);
                break;
            case 'quarter':
                from.setMonth(Math.floor(today.getMonth() / 3) * 3, 1);
                break;
            default:
                return null;
        }
        return { from: ymd(from), to: ymd(today) };
    }

    function currentParams(extra = {}) {
        const params = {};
        filterIds.forEach((id) => {
            const value = (document.getElementById(id)?.value || '').trim();
            if (value) params[id] = value;
        });
        if (activeTab && activeTab !== 'facility') params.tab = activeTab;
        Object.entries(extra).forEach(([key, value]) => {
            if (value) params[key] = value;
        });
        return params;
    }

    function hasFilters() {
        return filterIds.some((id) => (document.getElementById(id)?.value || '').trim());
    }

    function toggleClear() {
        if (clearButton) clearButton.disabled = !hasFilters();
    }

    function setLoading(isLoading) {
        page.classList.toggle('is-loading', isLoading);
        if (loading) loading.hidden = !isLoading;
        if (results) results.classList.toggle('is-loading', isLoading);
    }

    function updateExportLinks(urls) {
        if (!urls) return;
        document.querySelectorAll('.rpt-export-link').forEach((link) => {
            const type = link.dataset.export;
            if (type && urls[type]) link.setAttribute('href', urls[type]);
        });
    }

    function syncFilters(filters) {
        if (!filters) return;
        const from = document.getElementById('from_date');
        const to = document.getElementById('to_date');
        if (from && filters.from_date) from.value = filters.from_date;
        if (to && filters.to_date) to.value = filters.to_date;
        const range = document.getElementById('rpt-print-range');
        if (range && filters.from_date && filters.to_date) {
            range.textContent = `${filters.from_date} – ${filters.to_date}`;
        }
        highlightPreset();
    }

    function highlightPreset() {
        const from = document.getElementById('from_date')?.value;
        const to = document.getElementById('to_date')?.value;
        document.querySelectorAll('.rpt-preset-pill').forEach((pill) => {
            const range = presetRange(pill.dataset.preset);
            pill.classList.toggle('active', Boolean(range && range.from === from && range.to === to));
        });
    }

    function bindTabs() {
        document.querySelectorAll('.rpt-tab').forEach((button) => {
            button.classList.toggle('active', button.dataset.tab === activeTab);
        });
        document.querySelectorAll('.rpt-tab-pane').forEach((pane) => {
            pane.classList.toggle('active', pane.id === `pane-${activeTab}`);
        });
        requestAnimationFrame(() => {
            trendChart?.resize();
            catChart?.resize();
        });
    }

    function renderCharts(charts) {
        if (typeof Chart === 'undefined' || !charts) return;
        if (trendChart) {
            trendChart.destroy();
            trendChart = null;
        }
        if (catChart) {
            catChart.destroy();
            catChart = null;
        }
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            trendChart = new Chart(trendCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: charts.trend?.labels || [],
                    datasets: [
                        {
                            label: 'Approved',
                            data: charts.trend?.approved || [],
                            borderColor: '#16a77a',
                            backgroundColor: 'rgba(22,167,122,.1)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                        },
                        {
                            label: 'Cancelled / Rejected',
                            data: charts.trend?.cancelled || [],
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220,53,69,.08)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }
        const catCtx = document.getElementById('catChart');
        if (catCtx) {
            catChart = new Chart(catCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: charts.category?.labels || [],
                    datasets: [{
                        data: charts.category?.series || [],
                        backgroundColor: charts.category?.colors || [],
                        borderWidth: 2,
                        borderColor: '#fff',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                    cutout: '60%',
                },
            });
        }
    }

    function applyPayload(payload, url) {
        if (payload.html && results) results.innerHTML = payload.html;
        bindTabs();
        renderCharts(payload.charts);
        updateExportLinks(payload.export);
        syncFilters(payload.filters);
        toggleClear();
        if (url) history.replaceState({}, '', url);
    }

    function fetchReport(url) {
        const target = url || `${config.feed}?${new URLSearchParams(currentParams())}`;
        if (request) request.abort();
        request = new AbortController();
        setLoading(true);
        fetch(target, { headers: headers(), signal: request.signal, credentials: 'same-origin' })
            .then((response) => {
                if (!response.ok) throw new Error('Unable to load reports.');
                return response.json();
            })
            .then((payload) => {
                const next = new URL(config.url, window.location.origin);
                Object.entries(currentParams()).forEach(([key, value]) => next.searchParams.set(key, value));
                const source = url ? new URL(url, window.location.origin) : null;
                pageKeys.forEach((key) => {
                    const value = source?.searchParams.get(key);
                    if (value) next.searchParams.set(key, value);
                });
                applyPayload(payload, `${next.pathname}${next.search}`);
            })
            .catch((error) => {
                if (error.name !== 'AbortError') console.error(error);
            })
            .finally(() => setLoading(false));
    }

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        fetchReport();
    });

    clearButton?.addEventListener('click', () => {
        document.getElementById('search').value = '';
        document.getElementById('from_date').value = '';
        document.getElementById('to_date').value = '';
        Object.entries(placeholders).forEach(([id, label]) => window.RMSelect?.setValue(id, '', label));
        toggleClear();
        fetchReport(config.feed);
    });

    document.getElementById('search')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => fetchReport(), 400);
        toggleClear();
    });

    ['from_date', 'to_date', 'location_id', 'resource_category_id', 'status'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            toggleClear();
            fetchReport();
        });
    });

    document.querySelectorAll('.rpt-preset-pill').forEach((pill) => {
        pill.addEventListener('click', () => {
            const range = presetRange(pill.dataset.preset);
            if (!range) return;
            document.getElementById('from_date').value = range.from;
            document.getElementById('to_date').value = range.to;
            toggleClear();
            fetchReport();
        });
    });

    results?.addEventListener('click', (event) => {
        const tab = event.target.closest('.rpt-tab');
        if (tab) {
            event.preventDefault();
            activeTab = tab.dataset.tab || 'facility';
            bindTabs();
            const next = new URL(window.location.href);
            if (activeTab === 'facility') next.searchParams.delete('tab');
            else next.searchParams.set('tab', activeTab);
            history.replaceState({}, '', `${next.pathname}${next.search}`);
            return;
        }
        const pageLink = event.target.closest('[data-rpt-page]');
        if (pageLink) {
            event.preventDefault();
            const url = new URL(pageLink.href, window.location.origin);
            url.pathname = new URL(config.feed, window.location.origin).pathname;
            if (activeTab && activeTab !== 'facility') url.searchParams.set('tab', activeTab);
            fetchReport(url.toString());
        }
    });

    document.getElementById('rpt-print')?.addEventListener('click', () => {
        document.querySelectorAll('.rpt-tab-pane').forEach((pane) => pane.classList.add('active'));
        trendChart?.resize();
        catChart?.resize();
        window.print();
    });
    window.addEventListener('afterprint', () => bindTabs());

    bindTabs();
    renderCharts(config.charts);
    updateExportLinks(config.export);
    highlightPreset();
    toggleClear();
})();
