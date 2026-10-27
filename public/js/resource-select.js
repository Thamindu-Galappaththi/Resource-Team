window.RMSelect = {
    bind(root) {
        (root || document).querySelectorAll('[data-cu-select]').forEach((wrap) => {
            if (wrap.dataset.bound === '1') return;
            wrap.dataset.bound = '1';
            wrap.addEventListener('mousedown', (event) => this.handleItemEvent(wrap, event));
            wrap.addEventListener('click', (event) => this.handleItemEvent(wrap, event));
        });
    },

    handleItemEvent(wrap, event) {
        const item = event.target.closest('[data-value]');
        if (!item || !wrap.contains(item)) return;
        event.preventDefault();
        this.setValue(wrap, item.getAttribute('data-value') ?? '', item.getAttribute('data-label') || item.textContent.trim());
    },

    wrapFor(id) {
        const node = document.getElementById(id);
        if (!node) return null;
        return node.matches('[data-cu-select]') ? node : node.closest('[data-cu-select]');
    },

    inputFor(id) {
        const wrap = this.wrapFor(id);
        if (!wrap) return document.getElementById(id);
        return wrap.querySelector('input[type="hidden"]') || document.getElementById(id);
    },

    getValue(id) {
        return this.inputFor(id)?.value || '';
    },

    setValue(wrapOrId, value, label) {
        const wrap = typeof wrapOrId === 'string' ? this.wrapFor(wrapOrId) : wrapOrId;
        if (!wrap) return;
        const input = wrap.querySelector('input[type="hidden"]');
        if (!input) return;
        const labelEl = wrap.querySelector('.js-select-label');
        const next = value == null ? '' : String(value);
        const previous = input.value;
        input.value = next;
        if (labelEl) {
            labelEl.textContent = label || this.placeholder(wrap);
            labelEl.classList.toggle('text-muted', !next);
        }
        wrap.classList.remove('is-invalid');
        wrap.querySelector('.cu-dropdown-toggle')?.classList.remove('is-invalid');
        wrap.parentElement?.querySelector('.invalid-feedback')?.classList.remove('d-block');
        if (previous !== input.value) {
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    },

    setOptions(id, items, placeholder, options) {
        const wrap = this.wrapFor(id);
        if (!wrap) return;
        const menu = wrap.querySelector('.cu-dropdown-menu');
        const placeholderValue = options?.placeholderValue ?? '';
        const current = this.getValue(id);
        wrap.dataset.placeholder = placeholder;
        menu.replaceChildren();
        const rows = [{ value: placeholderValue, label: placeholder }, ...items];
        rows.forEach((item) => {
            const li = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'dropdown-item';
            button.setAttribute('data-value', String(item.value ?? ''));
            button.setAttribute('data-label', item.label);
            button.textContent = item.label;
            li.appendChild(button);
            menu.appendChild(li);
        });
        const selected = items.find((item) => String(item.value) === String(current));
        if (selected) this.setValue(wrap, selected.value, selected.label);
        else this.setValue(wrap, placeholderValue, placeholder);
        if (typeof options?.disabled === 'boolean') this.setDisabled(id, options.disabled);
    },

    cascadeResourceFilters(config) {
        const resources = Array.isArray(config?.resources) ? config.resources : [];
        const emptyValue = config?.emptyValue ?? '';
        const isEmpty = (value) => value == null || value === '' || String(value) === String(emptyValue);
        const locationId = this.getValue(config.locationSelectId);
        const resourceLocation = (resource) => String(resource.location_id ?? resource.locationId ?? '');
        const resourceCategory = (resource) => String(resource.category_id ?? resource.categoryId ?? '');
        const resourceName = (resource) => resource.name || resource.name_model || '';
        const categories = Array.isArray(config?.categories) && config.categories.length
            ? config.categories
            : (() => {
                const seen = new Map();
                resources.forEach((resource) => {
                    const id = resourceCategory(resource);
                    if (!id || seen.has(id)) return;
                    seen.set(id, {
                        id,
                        name: resource.category_name || resource.category || 'Uncategorized',
                    });
                });
                return [...seen.values()].sort((left, right) => left.name.localeCompare(right.name));
            })();

        if (config.updateCategories !== false) {
            const allowed = new Set(
                resources
                    .filter((resource) => isEmpty(locationId) || resourceLocation(resource) === String(locationId))
                    .map((resource) => resourceCategory(resource))
                    .filter(Boolean)
            );
            const categoryItems = (isEmpty(locationId) ? categories : categories.filter((category) => allowed.has(String(category.id))))
                .map((category) => ({ value: String(category.id), label: category.name }));
            this.setOptions(config.categorySelectId, categoryItems, config.categoryPlaceholder || 'All categories', {
                placeholderValue: emptyValue,
            });
        }

        const categoryId = this.getValue(config.categorySelectId);
        const resourceItems = resources
            .filter((resource) => {
                if (!isEmpty(locationId) && resourceLocation(resource) !== String(locationId)) return false;
                if (!isEmpty(categoryId) && resourceCategory(resource) !== String(categoryId)) return false;
                return true;
            })
            .map((resource) => ({ value: String(resource.id), label: resourceName(resource) }));
        this.setOptions(config.resourceSelectId, resourceItems, config.resourcePlaceholder || 'All resources', {
            placeholderValue: emptyValue,
        });
    },

    setDisabled(id, disabled) {
        const wrap = this.wrapFor(id);
        const toggle = wrap?.querySelector('.cu-dropdown-toggle');
        if (toggle) toggle.disabled = Boolean(disabled);
        wrap?.classList.toggle('is-disabled', Boolean(disabled));
    },

    placeholder(wrap) {
        return wrap.dataset.placeholder || wrap.querySelector('[data-value]')?.getAttribute('data-label') || 'Select an option';
    },
};

window.RMSwal = {
    base: {
        buttonsStyling: false,
        reverseButtons: true,
        customClass: {
            popup: 'rm-swal',
            title: 'rm-swal-title',
            htmlContainer: 'rm-swal-text',
            confirmButton: 'btn btn-primary rm-swal-btn',
            cancelButton: 'btn btn-outline-secondary rm-swal-btn',
            denyButton: 'btn btn-outline-danger rm-swal-btn',
            input: 'form-control rm-swal-input',
            actions: 'rm-swal-actions',
            icon: 'rm-swal-icon',
        },
    },
    fire(options) {
        return Swal.fire({ ...this.base, ...options });
    },
};

document.addEventListener('DOMContentLoaded', () => window.RMSelect.bind());
