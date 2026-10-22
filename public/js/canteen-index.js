(function () {
    const form = document.getElementById('cp-filters');
    const placeholders = {
        meal_type: 'All meals',
        status: 'All statuses',
        requested_by_user_id: 'All requesters',
    };
    const filterIds = ['search', 'from_date', 'to_date', 'meal_type', 'status', 'requested_by_user_id'];

    function dismissAlert(alert, delay = 5000) {
        if (!alert || alert.classList.contains('d-none')) return;
        clearTimeout(alert._cpTimer);
        alert._cpTimer = setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.id === 'cp-flash') {
                    alert.classList.add('d-none');
                    alert.style.opacity = '';
                    alert.textContent = '';
                } else {
                    alert.remove();
                }
            }, 300);
        }, delay);
    }

    function showFlash(message, type = 'danger') {
        const flash = document.getElementById('cp-flash');
        if (!flash || !message) return;
        flash.className = `alert alert-${type} cp-auto-alert`;
        flash.textContent = message;
        flash.style.opacity = '1';
        dismissAlert(flash);
    }

    function currentParams() {
        const params = {};
        filterIds.forEach((id) => {
            const value = (document.getElementById(id)?.value || '').trim();
            if (value) params[id] = value;
        });
        return params;
    }

    function toggleClear() {
        const clear = document.getElementById('cp-clear');
        if (clear) clear.disabled = Object.keys(currentParams()).length === 0;
    }

    function setLoading(isLoading) {
        const overlay = document.getElementById('cp-loading');
        document.getElementById('cp-results')?.classList.toggle('is-loading', isLoading);
        if (overlay) overlay.hidden = !isLoading;
    }

    function filterUrl() {
        const url = new URL(form.action, window.location.origin);
        url.search = new URLSearchParams(currentParams()).toString();
        return url.toString();
    }

    let request = null;
    let searchTimer = null;

    async function refresh(url) {
        request?.abort();
        request = new AbortController();
        setLoading(true);
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                signal: request.signal,
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Unable to load canteen reservations.');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextResults = page.querySelector('#cp-results');
            const currentResults = document.getElementById('cp-results');
            if (!nextResults || !currentResults) {
                throw new Error('The reservation list could not be updated.');
            }
            currentResults.replaceWith(nextResults);
            document.querySelector('#cp-results .cp-table-scroll')?.scrollTo(0, 0);
            history.replaceState(null, '', url);
            toggleClear();
        } catch (error) {
            if (error.name === 'AbortError') return;
            showFlash(error.message || 'Unable to load canteen reservations.');
        } finally {
            setLoading(false);
        }
    }

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        refresh(filterUrl());
    });

    document.getElementById('cp-clear')?.addEventListener('click', () => {
        const search = document.getElementById('search');
        const fromDate = document.getElementById('from_date');
        const toDate = document.getElementById('to_date');
        if (search) search.value = '';
        if (fromDate) fromDate.value = '';
        if (toDate) toDate.value = '';
        Object.entries(placeholders).forEach(([id, label]) => window.RMSelect?.setValue(id, '', label));
        toggleClear();
        refresh(form.action);
    });

    document.getElementById('search')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => refresh(filterUrl()), 350);
        toggleClear();
    });

    ['from_date', 'to_date', 'meal_type', 'status', 'requested_by_user_id'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            toggleClear();
            refresh(filterUrl());
        });
    });

    document.addEventListener('click', (event) => {
        const pageLink = event.target.closest?.('#cp-results .pagination a');
        if (!pageLink) return;
        event.preventDefault();
        refresh(pageLink.href);
    });

    document.querySelectorAll('.cp-auto-alert').forEach((alert) => dismissAlert(alert));
    toggleClear();
})();
