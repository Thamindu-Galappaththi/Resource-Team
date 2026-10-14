(function () {
    const form = document.getElementById('hr-filters');
    const placeholders = { room_type_id: 'All categories', status: 'All statuses' };

    function dismissAlert(alert, delay = 5000) {
        if (!alert || alert.classList.contains('d-none')) return;
        clearTimeout(alert._hrTimer);
        alert._hrTimer = setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.id === 'hr-flash') {
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
        const flash = document.getElementById('hr-flash');
        if (!flash || !message) return;
        flash.className = `alert alert-${type} hr-auto-alert`;
        flash.textContent = message;
        flash.style.opacity = '1';
        dismissAlert(flash);
    }

    function currentParams() {
        const params = {};
        ['search', 'check_in_from', 'check_in_to', 'room_type_id', 'status'].forEach((id) => {
            const value = (document.getElementById(id)?.value || '').trim();
            if (value) params[id] = value;
        });
        return params;
    }

    function toggleClear() {
        const clear = document.getElementById('hr-clear');
        if (clear) clear.disabled = Object.keys(currentParams()).length === 0;
    }

    function setLoading(isLoading) {
        const overlay = document.getElementById('hr-loading');
        document.getElementById('hr-results')?.classList.toggle('is-loading', isLoading);
        if (overlay) overlay.hidden = !isLoading;
    }

    function closeModals() {
        document.querySelectorAll('#hostel-reservation-modals .modal').forEach((modal) => {
            bootstrap.Modal.getInstance(modal)?.hide();
        });
        document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    function filterUrl() {
        const url = new URL(form.action, window.location.origin);
        url.search = new URLSearchParams(currentParams()).toString();
        return url.toString();
    }

    let request = null;
    let searchTimer = null;

    async function refresh(url) {
        closeModals();
        request?.abort();
        request = new AbortController();
        setLoading(true);
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                signal: request.signal,
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Unable to load hostel reservations.');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextResults = page.querySelector('#hr-results');
            const nextModals = page.querySelector('#hostel-reservation-modals');
            const currentResults = document.getElementById('hr-results');
            const currentModals = document.getElementById('hostel-reservation-modals');
            if (!nextResults || !nextModals || !currentResults || !currentModals) {
                throw new Error('The reservation results could not be updated.');
            }
            currentResults.replaceWith(nextResults);
            currentModals.replaceWith(nextModals);
            history.replaceState(null, '', url);
            toggleClear();
        } catch (error) {
            if (error.name === 'AbortError') return;
            showFlash(error.message || 'Unable to load hostel reservations.');
        } finally {
            setLoading(false);
        }
    }

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        refresh(filterUrl());
    });

    document.getElementById('hr-clear')?.addEventListener('click', () => {
        document.getElementById('search').value = '';
        document.getElementById('check_in_from').value = '';
        document.getElementById('check_in_to').value = '';
        Object.entries(placeholders).forEach(([id, label]) => window.RMSelect?.setValue(id, '', label));
        toggleClear();
        refresh(form.action);
    });

    document.getElementById('search')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => refresh(filterUrl()), 350);
        toggleClear();
    });

    ['check_in_from', 'check_in_to', 'room_type_id', 'status'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            toggleClear();
            refresh(filterUrl());
        });
    });

    document.addEventListener('click', (event) => {
        const pageLink = event.target.closest?.('#hr-results .pagination a');
        if (!pageLink) return;
        event.preventDefault();
        refresh(pageLink.href);
    });

    document.querySelectorAll('.hr-auto-alert').forEach((alert) => dismissAlert(alert));
    toggleClear();
})();
