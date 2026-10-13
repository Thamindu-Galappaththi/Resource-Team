window.RMSelect = {
    bind(root) {
        (root || document).querySelectorAll('[data-cu-select]').forEach((wrap) => {
            if (wrap.dataset.bound === '1') return;
            wrap.dataset.bound = '1';
            wrap.addEventListener('click', (event) => {
                const item = event.target.closest('[data-value]');
                if (!item || !wrap.contains(item)) return;
                this.setValue(wrap, item.dataset.value, item.dataset.label || item.textContent.trim());
            });
        });
    },

    wrapFor(id) {
        const input = document.getElementById(id);
        return input ? input.closest('[data-cu-select]') : null;
    },

    getValue(id) {
        return document.getElementById(id)?.value || '';
    },

    setValue(wrapOrId, value, label) {
        const wrap = typeof wrapOrId === 'string' ? this.wrapFor(wrapOrId) : wrapOrId;
        if (!wrap) return;
        const input = wrap.querySelector('input[type="hidden"]');
        const labelEl = wrap.querySelector('.js-select-label');
        const previous = input.value;
        input.value = value ?? '';
        if (labelEl) {
            labelEl.textContent = label || this.placeholder(wrap);
            labelEl.classList.toggle('text-muted', !input.value);
        }
        wrap.querySelector('.cu-dropdown-toggle')?.classList.remove('is-invalid');
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
            button.dataset.value = String(item.value ?? '');
            button.dataset.label = item.label;
            button.textContent = item.label;
            li.appendChild(button);
            menu.appendChild(li);
        });
        const selected = items.find((item) => String(item.value) === String(current));
        if (selected) this.setValue(wrap, selected.value, selected.label);
        else this.setValue(wrap, placeholderValue, placeholder);
        if (typeof options?.disabled === 'boolean') this.setDisabled(id, options.disabled);
    },

    setDisabled(id, disabled) {
        const wrap = this.wrapFor(id);
        const toggle = wrap?.querySelector('.cu-dropdown-toggle');
        if (toggle) toggle.disabled = Boolean(disabled);
        wrap?.classList.toggle('is-disabled', Boolean(disabled));
    },

    placeholder(wrap) {
        return wrap.dataset.placeholder || wrap.querySelector('[data-value]')?.dataset.label || 'Select an option';
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
