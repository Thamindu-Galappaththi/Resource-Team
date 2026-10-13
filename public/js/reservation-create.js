(function () {
    const config = window.RVCreate || {};
    const lookups = config.lookups || { locations: [], categories: [], resources: [] };
    const holidays = config.holidays || {};

    const resourceSelect = document.getElementById('resource_id');
    const linkedWrap = document.getElementById('linkedWrap');
    const linkedBox = document.getElementById('linkedResources');
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
        const serial = resource.serial_number ? ` (${resource.serial_number})` : '';
        const location = resource.location_name ? ` — ${resource.location_name}` : '';
        return `${resource.name_model}${serial}${location}`;
    }

    function filteredResources() {
        const locationId = window.RMSelect.getValue('location_id');
        const categoryId = window.RMSelect.getValue('category_id');
        return (lookups.resources || []).filter((resource) => {
            if (locationId && String(resource.location_id) !== String(locationId)) return false;
            if (categoryId && String(resource.category_id) !== String(categoryId)) return false;
            return true;
        });
    }

    function selectedResourceIds() {
        const ids = [];
        if (resourceSelect.value) ids.push(resourceSelect.value);
        linkedBox.querySelectorAll('input[name="resource_ids[]"]:checked').forEach((el) => ids.push(el.value));
        return ids;
    }

    function refreshResources() {
        const rows = filteredResources();
        const current = resourceSelect.value || config.prefillResourceId || '';
        window.RMSelect.setOptions(
            'resource_id',
            rows.map((resource) => ({ value: String(resource.id), label: resourceLabel(resource) })),
            'Select a resource'
        );
        const stillVisible = rows.find((row) => String(row.id) === String(current));
        if (stillVisible) {
            window.RMSelect.setValue('resource_id', String(stillVisible.id), resourceLabel(stillVisible));
        }
        renderLinked();
        checkAvailability();
    }

    function renderLinked() {
        const resource = (lookups.resources || []).find((row) => String(row.id) === String(resourceSelect.value));
        linkedBox.innerHTML = '';
        if (!resource) {
            linkedWrap.hidden = true;
            return;
        }

        const linkedResources = (resource.linked_resources || []).filter((row) => row.status === 'active');
        const linkedIds = linkedResources.map((row) => String(row.id));
        const extraResources = (config.prefillResourceIds || [])
            .map(String)
            .filter((id) => id !== String(resource.id) && !linkedIds.includes(id))
            .map((id) => (lookups.resources || []).find((row) => String(row.id) === id))
            .filter(Boolean);

        if (!linkedResources.length && !extraResources.length) {
            linkedWrap.hidden = true;
            return;
        }

        linkedWrap.hidden = false;
        [...linkedResources, ...extraResources].forEach((linked) => {
            const id = `linked-${linked.id}`;
            const checked = (config.prefillResourceIds || []).map(String).includes(String(linked.id)) ? ' checked' : '';
            linkedBox.insertAdjacentHTML('beforeend', `<label class="rv-linked-item" for="${id}">
                <input class="form-check-input" type="checkbox" name="resource_ids[]" value="${linked.id}" id="${id}"${checked}>
                <span>${linked.name_model}${linked.serial_number ? ` (${linked.serial_number})` : ''}</span>
            </label>`);
        });
        linkedBox.querySelectorAll('input').forEach((el) => el.addEventListener('change', checkAvailability));
    }

    function setAvailability(state, message) {
        availabilityAlert.className = `rv-status ${state}`;
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

        try {
            const response = await fetch(`${config.availabilityUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                signal: availabilityRequest.signal,
            });
            const data = await response.json();
            slotAvailable = Boolean(data.available);
            submitBtn.disabled = !slotAvailable;
            setAvailability(slotAvailable ? 'ok' : 'busy', data.message || (slotAvailable ? 'This slot is available.' : 'Selected slot unavailable'));
        } catch (error) {
            if (error.name === 'AbortError') return;
            slotAvailable = true;
            submitBtn.disabled = false;
            setAvailability('warn', 'Could not check availability right now. You can still submit and the server will re-check.');
        }
    }

    ['location_id', 'category_id'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', refreshResources);
    });
    resourceSelect.addEventListener('change', () => {
        renderLinked();
        checkAvailability();
    });
    ['reservation_date', 'start_time', 'end_time'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', checkAvailability);
        document.getElementById(id)?.addEventListener('input', checkAvailability);
    });

    form?.addEventListener('submit', (event) => {
        if (!resourceSelect.value) {
            event.preventDefault();
            showFlash('Select a resource.');
            document.getElementById('resource_id')?.closest('.cu-select')?.classList.add('is-invalid');
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
    if (config.prefillCategoryId) {
        const category = (lookups.categories || []).find((row) => String(row.id) === String(config.prefillCategoryId));
        if (category) window.RMSelect.setValue('category_id', String(category.id), category.name);
    }

    refreshResources();
})();
