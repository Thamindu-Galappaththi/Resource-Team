window.RMHostelFields = (function () {
    const box = () => document.getElementById('hostelBedFields');
    const inputs = () => [...(box()?.querySelectorAll('[data-bed-group]') || [])];
    const nameInput = () => document.getElementById('resourceNameModel');

    function selectedLabel(id) {
        const wrap = window.RMSelect?.wrapFor(id);
        return (wrap?.querySelector('.js-select-label')?.textContent || '').trim().toLowerCase();
    }

    /** Hostel beds apply once the Hostel Room category and its Bed type are both chosen. */
    function isHostel() {
        const section = box();
        if (!section || !window.RMSelect?.getValue('resourceCategorySelect') || !window.RMSelect?.getValue('resourceTypeSelect')) return false;
        return selectedLabel('resourceCategorySelect') === (section.dataset.hostelCategory || '').trim().toLowerCase()
            && selectedLabel('resourceTypeSelect') === (section.dataset.bedType || '').trim().toLowerCase();
    }

    function updateTotal() {
        const total = inputs().reduce((sum, input) => sum + (Number.parseInt(input.value, 10) || 0), 0);
        const target = box()?.querySelector('[data-bed-total]');
        if (target) target.textContent = String(total);
    }

    function sync() {
        const section = box();
        if (!section) return;
        const on = isHostel();
        section.hidden = !on;
        inputs().forEach((input) => { input.required = on; });
        const name = nameInput();
        const label = name?.closest('.mb-3')?.querySelector('label');
        label?.querySelector('[data-name-required]')?.classList.toggle('d-none', on);
        label?.querySelector('[data-name-optional]')?.classList.toggle('d-none', !on);
        if (name) {
            name.required = !on;
            name.placeholder = on ? 'Leave blank to name it automatically (e.g., Welisara Hostel)' : 'e.g., Computer Lab 03';
        }
        updateTotal();
    }

    function payload() {
        if (!isHostel()) return { bed_counts: null };
        return {
            bed_counts: Object.fromEntries(inputs().map((input) => [input.dataset.bedGroup, input.value === '' ? null : Number(input.value)])),
        };
    }

    function validate(showFieldError) {
        if (!isHostel()) return true;
        let ok = true;
        inputs().forEach((input) => {
            const value = Number(input.value);
            const max = Number(input.max);
            if (input.value === '' || !Number.isInteger(value) || value < 0 || value > max) {
                showFieldError(input.id, `Enter 0 to ${max} beds.`);
                ok = false;
            }
        });
        return ok;
    }

    function fill(resource) {
        const counts = resource?.bed_counts || {};
        inputs().forEach((input) => {
            input.value = counts[input.dataset.bedGroup] ?? input.dataset.bedDefault;
        });
        sync();
    }

    function reset() {
        fill(null);
    }

    /** Maps Laravel errors like "bed_counts.female" to the matching input id. */
    function errorFields() {
        return Object.fromEntries([
            ['bed_counts', inputs()[0]?.id],
            ...inputs().map((input) => [`bed_counts.${input.dataset.bedGroup}`, input.id]),
        ].filter(([, id]) => id));
    }

    function fieldIds() {
        return inputs().map((input) => input.id);
    }

    document.addEventListener('change', (event) => {
        if (['resourceCategorySelect', 'resourceTypeSelect'].includes(event.target?.id)) sync();
    });
    document.addEventListener('input', (event) => {
        if (event.target?.matches?.('[data-bed-group]')) updateTotal();
    });

    return { isHostel, sync, payload, validate, fill, reset, errorFields, fieldIds };
})();
