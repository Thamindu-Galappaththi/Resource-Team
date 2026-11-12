(function () {
    const config = window.RVCreate || {};
    const lookups = config.lookups || { locations: [], categories: [], resources: [] };
    const holidays = config.holidays || {};

    const resourceSelect = document.getElementById('resource_id');
    const availabilityAlert = document.getElementById('availabilityAlert');
    const holidayAlert = document.getElementById('holidayAlert');
    const submitBtn = document.getElementById('submitReservation');
    const form = document.getElementById('reservationForm');

    let availabilityTimer = null;
    let availabilityRequest = null;
    let slotAvailable = true;

    function dismissAlert(alert, delay = 5000) {
        if (!alert || alert.classList.contains('d-none')) return;
        clearTimeout(alert._rvTimer);
        alert._rvTimer = setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.classList.add('d-none');
                alert.style.opacity = '';
                if (alert.id === 'rv-flash') alert.textContent = '';
                else alert.remove();
            }, 300);
        }, delay);
    }

    function showFlash(message, type = 'danger') {
        const flash = document.getElementById('rv-flash');
        if (!flash) return;
        flash.className = `alert alert-${type} rv-auto-alert`;
        flash.textContent = message;
        flash.style.opacity = '1';
        dismissAlert(flash);
        flash.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function resourceLabel(resource) {
        return resource.name_model || 'Resource';
    }

    function categoriesForLocation(locationId) {
        if (!locationId) return [];
        const seen = new Map();
        (lookups.resources || []).forEach((resource) => {
            if (String(resource.location_id) !== String(locationId) || !resource.category_id) return;
            const id = String(resource.category_id);
            if (seen.has(id)) return;
            const name = resource.category_name
                || (lookups.categories || []).find((category) => String(category.id) === id)?.name;
            if (!name) return;
            seen.set(id, { id: resource.category_id, name });
        });
        return [...seen.values()].sort((left, right) => left.name.localeCompare(right.name));
    }

    function filteredResources() {
        const locationId = window.RMSelect.getValue('location_id');
        const categoryIds = window.RMSelect.getValues('category_id');
        if (!locationId || !categoryIds.length) return [];
        const allowed = new Set(categoryIds.map(String));
        return (lookups.resources || []).filter((resource) => {
            if (String(resource.location_id) !== String(locationId)) return false;
            return allowed.has(String(resource.category_id));
        });
    }

    function refreshCategories() {
        const locationId = window.RMSelect.getValue('location_id');
        const rows = categoriesForLocation(locationId);
        const keep = [
            ...(config.prefillCategoryIds || []),
            config.prefillCategoryId,
            ...window.RMSelect.getValues('category_id'),
        ].filter(Boolean).map(String);
        window.RMSelect.setOptions(
            'category_id',
            rows.map((category) => ({ value: String(category.id), label: category.name })),
            locationId ? 'Select categories' : 'Select a location first',
            { disabled: !locationId, selected: keep }
        );
    }

    function selectedResourceIds() {
        return window.RMSelect.getValues('resource_id');
    }

    function refreshResources() {
        const rows = filteredResources();
        const ready = Boolean(window.RMSelect.getValue('location_id') && window.RMSelect.getValues('category_id').length);
        const keep = [
            ...(config.prefillResourceIds || []),
            config.prefillResourceId,
            ...window.RMSelect.getValues('resource_id'),
        ].filter(Boolean).map(String);
        window.RMSelect.setOptions(
            'resource_id',
            rows.map((resource) => ({ value: String(resource.id), label: resourceLabel(resource) })),
            ready ? 'Select resources' : 'Select a location and category first',
            { disabled: !ready, selected: keep }
        );
        checkAvailability();
    }

    function setAvailability(state, message) {
        availabilityAlert.className = state === 'idle' ? 'rv-status' : `rv-status is-${state}`;
        const icons = { idle: 'ti-info-circle', ok: 'ti-circle-check', busy: 'ti-ban', warn: 'ti-alert-circle' };
        const icon = icons[state] || 'ti-info-circle';
        availabilityAlert.innerHTML = `<i class="ti ${icon}"></i><span>${message}</span>`;
    }

    function showHoliday() {
        const date = document.getElementById('reservation_date').value;
        const holiday = holidays[date];
        if (!holiday) {
            holidayAlert.hidden = true;
            holidayAlert.innerHTML = '';
            return;
        }
        holidayAlert.hidden = false;
        holidayAlert.className = `rv-status is-holiday`;
        holidayAlert.innerHTML = `<i class="ti ti-moon"></i><span>${holiday.title} · ${holiday.typeLabel}</span>`;
    }

    function checkAvailability() {
        showHoliday();
        clearTimeout(availabilityTimer);
        availabilityTimer = setTimeout(runAvailability, 250);
    }

    async function runAvailability() {
        const ids = selectedResourceIds();
        const date = document.getElementById('reservation_date').value;
        const start = document.getElementById('start_time').value;
        const end = document.getElementById('end_time').value;

        if (!ids.length || !date || !start || !end) {
            slotAvailable = true;
            submitBtn.disabled = false;
            setAvailability('idle', 'Select a resource and time slot to check availability.');
            return;
        }
        if (end <= start) {
            slotAvailable = false;
            submitBtn.disabled = true;
            setAvailability('busy', 'End time must be after start time.');
            return;
        }

        if (availabilityRequest) availabilityRequest.abort();
        availabilityRequest = new AbortController();
        const params = new URLSearchParams();
        ids.forEach((id) => params.append('resource_ids[]', id));
        params.set('reservation_date', date);
        params.set('start_time', start);
        params.set('end_time', end);
        if (config.ignoreReservationId) {
            params.set('ignore_reservation_id', config.ignoreReservationId);
        }

        try {
            const response = await fetch(`${config.availabilityUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                signal: availabilityRequest.signal,
            });
            const data = await response.json();
            slotAvailable = Boolean(data.available);
            submitBtn.disabled = !slotAvailable;
            setAvailability(slotAvailable ? 'ok' : 'busy', data.message || (slotAvailable ? 'This slot is available.' : 'This slot is not available.'));
        } catch (error) {
            if (error.name === 'AbortError') return;
            slotAvailable = true;
            submitBtn.disabled = false;
            setAvailability('warn', 'Could not check availability right now. You can still submit and the server will re-check.');
        }
    }

    document.getElementById('location_id')?.addEventListener('change', () => {
        refreshCategories();
        refreshResources();
    });
    document.getElementById('category_id')?.addEventListener('change', refreshResources);
    resourceSelect.addEventListener('change', checkAvailability);
    ['reservation_date', 'start_time', 'end_time'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', checkAvailability);
        document.getElementById(id)?.addEventListener('input', checkAvailability);
    });

    form?.addEventListener('submit', (event) => {
        const locationId = window.RMSelect.getValue('location_id');
        const categoryIds = window.RMSelect.getValues('category_id');
        const resourceIds = window.RMSelect.getValues('resource_id');
        const bookingType = document.getElementById('booking_type')?.value;
        if (!locationId) {
            event.preventDefault();
            showFlash('Select a location.');
            document.getElementById('location_id')?.closest('.cu-select')?.classList.add('is-invalid');
            return;
        }
        if (!categoryIds.length) {
            event.preventDefault();
            showFlash('Select a resource category.');
            document.getElementById('category_id')?.closest('.cu-select')?.classList.add('is-invalid');
            return;
        }
        if (!resourceIds.length) {
            event.preventDefault();
            showFlash('Select a resource.');
            document.getElementById('resource_id')?.closest('.cu-select')?.classList.add('is-invalid');
            return;
        }
        if (!bookingType) {
            event.preventDefault();
            showFlash('Select a time range.');
            document.getElementById('booking_type')?.closest('.cu-select')?.classList.add('is-invalid');
            return;
        }
        const title = document.getElementById('title');
        if (title && !title.value.trim()) {
            event.preventDefault();
            title.classList.add('is-invalid');
            title.focus();
            showFlash('Enter a reservation name.');
            return;
        }
        if (submitBtn.disabled || !slotAvailable) {
            event.preventDefault();
            showFlash('Choose an available time slot before submitting.');
        }
    });

    document.querySelectorAll('.rv-auto-alert').forEach((alert) => dismissAlert(alert));

    if (config.prefillLocationId) {
        const location = (lookups.locations || []).find((row) => String(row.id) === String(config.prefillLocationId));
        if (location) window.RMSelect.setValue('location_id', String(location.id), location.name);
    }
    refreshCategories();
    refreshResources();
})();
