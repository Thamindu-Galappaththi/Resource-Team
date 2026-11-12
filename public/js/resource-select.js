window.RMSelect = {
    bind(root) {
        (root || document).querySelectorAll('[data-cu-select]').forEach((wrap) => {
            if (wrap.dataset.bound === '1') return;
            wrap.dataset.bound = '1';
            wrap.addEventListener('mousedown', (event) => this.handleItemEvent(wrap, event));
            wrap.addEventListener('click', (event) => this.handleItemEvent(wrap, event));
        });
    },

    isMultiple(wrap) {
        return Boolean(wrap?.hasAttribute('data-cu-multiple'));
    },

    handleItemEvent(wrap, event) {
        const item = event.target.closest('[data-value]');
        if (!item || !wrap.contains(item)) return;
        if (this.isMultiple(wrap)) {
            if (event.type !== 'click') {
                event.preventDefault();
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            const value = item.getAttribute('data-value') ?? '';
            if (value === '') return;
            this.toggleValue(wrap, value);
            return;
        }
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
        return wrap.querySelector('input[id]') || document.getElementById(id);
    },

    getValue(id) {
        return this.inputFor(id)?.value || '';
    },

    getValues(id) {
        const wrap = this.wrapFor(id);
        if (!wrap) {
            const value = document.getElementById(id)?.value;
            return value ? [String(value)] : [];
        }
        return this.valuesFrom(wrap);
    },

    valuesFrom(wrap) {
        if (this.isMultiple(wrap)) {
            const holder = wrap.querySelector('[data-cu-array-inputs]');
            const fromHolder = [...(holder?.querySelectorAll('input') || [])].map((input) => String(input.value)).filter(Boolean);
            if (fromHolder.length) return fromHolder;
        }
        const value = wrap.querySelector('input[type="hidden"]')?.value;
        return value ? [String(value)] : [];
    },

    toggleValue(wrap, value) {
        const current = this.valuesFrom(wrap);
        const next = current.includes(String(value))
            ? current.filter((item) => item !== String(value))
            : [...current, String(value)];
        this.applyValues(wrap, next);
    },

    setValues(id, values) {
        const wrap = this.wrapFor(id);
        if (!wrap) return;
        this.applyValues(wrap, values);
    },

    applyValues(wrap, values) {
        const unique = [...new Set((values || []).map((value) => String(value)).filter(Boolean))];
        const input = wrap.querySelector('input[id]');
        const previous = this.valuesFrom(wrap).join('\0');
        wrap.querySelectorAll('[data-value]').forEach((item) => {
            const on = unique.includes(item.getAttribute('data-value') ?? '');
            item.classList.toggle('is-selected', on);
            const box = item.querySelector('input[type="checkbox"]');
            if (box) box.checked = on;
        });
        if (input) input.value = unique[0] || '';
        this.syncArrayInputs(wrap, unique);
        const labelEl = wrap.querySelector('.js-select-label');
        if (labelEl) {
            const labels = unique.map((value) => wrap.querySelector(`[data-value="${CSS.escape(value)}"]`)?.getAttribute('data-label')).filter(Boolean);
            if (!labels.length) {
                labelEl.textContent = this.placeholder(wrap);
                labelEl.classList.add('text-muted');
            } else {
                labelEl.textContent = labels.length <= 2 ? labels.join(', ') : `${labels[0]} +${labels.length - 1} more`;
                labelEl.classList.remove('text-muted');
            }
        }
        wrap.classList.remove('is-invalid');
        wrap.querySelector('.cu-dropdown-toggle')?.classList.remove('is-invalid');
        wrap.parentElement?.querySelector('.invalid-feedback')?.classList.remove('d-block');
        if (previous !== unique.join('\0')) {
            input?.dispatchEvent(new Event('change', { bubbles: true }));
        }
    },

    syncArrayInputs(wrap, values) {
        const name = wrap.dataset.cuArray;
        if (!name) return;
        let holder = wrap.querySelector('[data-cu-array-inputs]');
        if (!holder) {
            holder = document.createElement('div');
            holder.hidden = true;
            holder.setAttribute('data-cu-array-inputs', '1');
            wrap.insertBefore(holder, wrap.firstChild);
        }
        holder.replaceChildren();
        values.forEach((value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            holder.append(input);
        });
    },

    setValue(wrapOrId, value, label) {
        const wrap = typeof wrapOrId === 'string' ? this.wrapFor(wrapOrId) : wrapOrId;
        if (!wrap) return;
        if (this.isMultiple(wrap)) {
            this.applyValues(wrap, value ? [value] : []);
            return;
        }
        const input = wrap.querySelector('input[id]');
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
        const multiple = this.isMultiple(wrap);
        const current = multiple ? this.getValues(id) : [this.getValue(id)];
        wrap.dataset.placeholder = placeholder;
        menu.replaceChildren();
        const rows = multiple ? items : [{ value: placeholderValue, label: placeholder }, ...items];
        rows.forEach((item) => {
            const li = document.createElement('li');
            if (multiple) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'dropdown-item cu-check-item';
                button.setAttribute('data-value', String(item.value ?? ''));
                button.setAttribute('data-label', item.label);
                const box = document.createElement('input');
                box.type = 'checkbox';
                box.className = 'form-check-input';
                box.tabIndex = -1;
                const text = document.createElement('span');
                text.textContent = item.label;
                button.append(box, text);
                li.appendChild(button);
            } else {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'dropdown-item';
                button.setAttribute('data-value', String(item.value ?? ''));
                button.setAttribute('data-label', item.label);
                button.textContent = item.label;
                li.appendChild(button);
            }
            menu.appendChild(li);
        });
        const allowed = new Set(items.map((item) => String(item.value ?? '')));
        const selected = (options?.selected || current).map(String).filter((value) => allowed.has(value));
        if (multiple) this.applyValues(wrap, selected);
        else {
            const match = items.find((item) => String(item.value) === String(selected[0] || current[0] || ''));
            if (match) this.setValue(wrap, match.value, match.label);
            else this.setValue(wrap, placeholderValue, placeholder);
        }
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
