(function () {
    const page = document.querySelector('.rr-admin');
    if (!page) {
        return;
    }

    const catalogNode = document.getElementById('rr-catalog');
    const catalog = catalogNode ? JSON.parse(catalogNode.textContent) : { types: [], resources: [] };

    function resourceItems(typeId) {
        return (catalog.resources || [])
            .filter((item) => !typeId || String(item.type) === String(typeId))
            .map((item) => ({ value: String(item.value), label: typeId ? (item.name || item.label) : item.label }));
    }

    function filterResources(typeId, resourceId, keepValue) {
        if (!window.RMSelect) {
            return;
        }
        const current = keepValue == null ? window.RMSelect.getValue(resourceId) : String(keepValue);
        const items = resourceItems(typeId);
        window.RMSelect.setOptions(resourceId, items, 'All resources of type');
        const selected = items.find((item) => item.value === current);
        if (selected) {
            window.RMSelect.setValue(resourceId, selected.value, selected.label);
        }
    }

    function bindTypeFilter(typeId, resourceId) {
        const input = document.getElementById(typeId);
        if (!input || !document.getElementById(resourceId)) {
            return;
        }
        filterResources(window.RMSelect.getValue(typeId), resourceId);
        input.addEventListener('change', () => filterResources(window.RMSelect.getValue(typeId), resourceId, ''));
    }

    if (window.RMSelect) {
        bindTypeFilter('rr-type', 'rr-resource');
        bindTypeFilter('rr-edit-type', 'rr-edit-resource');
    }

    function sectionTarget(section) {
        return {
            rate_history: 'rr-rate-history',
            discount_history: 'rr-discount-history',
            rate_catalog: 'rr-rate-catalog',
        }[section] || null;
    }

    const sectionRequests = {};

    function loadSection(section, href) {
        const targetId = sectionTarget(section);
        const target = targetId ? document.getElementById(targetId) : null;
        if (!target) {
            window.location.href = href;
            return;
        }
        const requestId = (sectionRequests[section] = (sectionRequests[section] || 0) + 1);
        const url = new URL(href, window.location.origin);
        url.searchParams.set('section', section);
        target.classList.add('is-loading');
        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        }).then((response) => {
            if (!response.ok) {
                throw new Error('Could not load that section.');
            }
            return response.text();
        }).then((html) => {
            if (sectionRequests[section] !== requestId) {
                return;
            }
            target.innerHTML = html;
            history.replaceState({}, '', href);
        }).catch(() => {
            if (sectionRequests[section] !== requestId) {
                return;
            }
            window.location.href = href;
        }).finally(() => {
            if (sectionRequests[section] === requestId) {
                target.classList.remove('is-loading');
            }
        });
    }

    const searchForm = page.querySelector('[data-rr-catalog-search]');
    const search = document.getElementById('rr-rate-search');
    const typeFilter = document.getElementById('rr-type-filter');
    const statusFilter = document.getElementById('rr-status-filter');
    const clearFilters = document.getElementById('rr-clear-filters');
    let searchTimer = null;
    let clearingFilters = false;

    function catalogFilterUrl() {
        const url = new URL(window.location.href);
        url.searchParams.delete('section');
        const query = search ? search.value.trim() : '';
        if (query) {
            url.searchParams.set('catalog_search', query);
        } else {
            url.searchParams.delete('catalog_search');
        }
        const type = typeFilter && window.RMSelect ? window.RMSelect.getValue('rr-type-filter') : '';
        if (type) {
            url.searchParams.set('catalog_type', type);
        } else {
            url.searchParams.delete('catalog_type');
        }
        const status = statusFilter && window.RMSelect ? window.RMSelect.getValue('rr-status-filter') : 'active';
        if (status && status !== 'active') {
            url.searchParams.set('catalog_status', status);
        } else {
            url.searchParams.delete('catalog_status');
        }
        url.searchParams.delete('catalog_page');
        url.hash = 'rr-rates';
        return url.toString();
    }

    function catalogHasFilters() {
        const query = search ? search.value.trim() : '';
        const type = typeFilter && window.RMSelect ? window.RMSelect.getValue('rr-type-filter') : '';
        const status = statusFilter && window.RMSelect ? window.RMSelect.getValue('rr-status-filter') : 'active';
        return Boolean(query || type || (status && status !== 'active'));
    }

    function toggleClear() {
        if (clearFilters) {
            clearFilters.disabled = !catalogHasFilters();
        }
    }

    if (searchForm && search) {
        searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            loadSection('rate_catalog', catalogFilterUrl());
        });
        search.addEventListener('input', () => {
            toggleClear();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadSection('rate_catalog', catalogFilterUrl()), 350);
        });
    }
    [typeFilter, statusFilter].forEach((filter) => {
        filter?.addEventListener('change', () => {
            if (clearingFilters) {
                return;
            }
            toggleClear();
            loadSection('rate_catalog', catalogFilterUrl());
        });
    });
    clearFilters?.addEventListener('click', () => {
        if (clearFilters.disabled) {
            return;
        }
        clearingFilters = true;
        clearTimeout(searchTimer);
        if (search) {
            search.value = '';
        }
        window.RMSelect?.setValue('rr-type-filter', '', 'All types');
        window.RMSelect?.setValue('rr-status-filter', 'active', 'Active');
        clearingFilters = false;
        toggleClear();
        loadSection('rate_catalog', catalogFilterUrl());
    });
    toggleClear();

    function showTab(section) {
        page.querySelectorAll('.rr-tabs a').forEach((item) => {
            const on = item.getAttribute('href') === section;
            item.classList.toggle('is-active', on);
            if (on) {
                item.setAttribute('aria-current', 'page');
            } else {
                item.removeAttribute('aria-current');
            }
        });
        page.querySelectorAll('.rr-catalog-panel, .rr-management, .rr-stats').forEach((item) => {
            item.hidden = true;
        });
        if (section === '#rr-rates') {
            page.querySelectorAll('.rr-catalog-panel, #rr-add-rate, .rr-stats').forEach((item) => {
                item.hidden = false;
            });
        } else if (section === '#rr-discounts') {
            const discounts = page.querySelector('#rr-discounts');
            if (discounts) {
                discounts.hidden = false;
            }
        } else {
            page.querySelectorAll('#rr-history, #rr-rate-history-panel').forEach((item) => {
                item.hidden = false;
            });
        }
    }

    function requestedTab() {
        const hash = window.location.hash;
        if (hash === '#rr-discounts') {
            return '#rr-discounts';
        }
        if (hash === '#rr-history' || hash === '#rr-discount-history' || hash === '#rr-rate-history') {
            return '#rr-history';
        }
        if (page.dataset.rrTab === 'discounts') {
            return '#rr-discounts';
        }
        if (page.dataset.rrTab === 'history') {
            return '#rr-history';
        }
        return '#rr-rates';
    }

    page.querySelectorAll('.rr-tabs a').forEach((tab) => tab.addEventListener('click', (event) => {
        const section = tab.getAttribute('href');
        if (!page.querySelector(section)) {
            return;
        }
        event.preventDefault();
        showTab(section);
        const url = new URL(window.location.href);
        url.hash = section;
        history.replaceState({}, '', url);
    }));
    showTab(requestedTab());

    page.querySelector('.rr-export-btn')?.addEventListener('click', () => {
        const table = document.getElementById('rr-rate-table');
        if (!table) {
            return;
        }
        const rows = [...table.querySelectorAll('tr')].filter((row) => !row.hidden && !row.classList.contains('rr-empty-row'));
        if (rows.length <= 1) {
            return;
        }
        const csv = rows.map((row) => [...row.querySelectorAll('th, td')].slice(0, 7).map((cell) => {
            const clone = cell.cloneNode(true);
            clone.querySelectorAll('button, form, .rr-actions').forEach((node) => node.remove());
            return `"${clone.textContent.trim().replace(/"/g, '""')}"`;
        }).join(',')).join('\r\n');
        const link = document.createElement('a');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        link.href = url;
        link.download = 'resource-rates.csv';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    });

    function constraintFields(form) {
        return form.querySelectorAll('[data-rr-required]:not([data-cu-select]), [data-rr-pattern]');
    }

    function clearSelectErrors(form) {
        form.querySelectorAll('[data-cu-select][data-rr-required]').forEach((wrap) => {
            wrap.classList.remove('is-invalid');
            wrap.querySelector('.cu-dropdown-toggle')?.classList.remove('is-invalid');
        });
    }

    function validateSelects(form) {
        let valid = true;
        form.querySelectorAll('[data-cu-select][data-rr-required]').forEach((wrap) => {
            const empty = !(wrap.querySelector('input[type="hidden"]')?.value);
            wrap.classList.toggle('is-invalid', empty);
            wrap.querySelector('.cu-dropdown-toggle')?.classList.toggle('is-invalid', empty);
            if (empty) {
                valid = false;
            }
        });
        return valid;
    }

    function disarm(form) {
        constraintFields(form).forEach((field) => {
            field.required = false;
            field.removeAttribute('pattern');
            field.setCustomValidity('');
            field.classList.remove('is-invalid');
        });
    }

    function arm(form) {
        constraintFields(form).forEach((field) => {
            if (field.hasAttribute('data-rr-required')) {
                field.required = true;
            }
            if (field.dataset.rrPattern) {
                field.pattern = field.dataset.rrPattern;
            }
            field.removeAttribute('title');
        });
    }

    function fieldMessage(field) {
        if (field.validity.valueMissing) {
            return 'Please fill out this field.';
        }
        if (field.validity.patternMismatch) {
            return 'Use letters, numbers, hyphens, or underscores.';
        }
        return field.validationMessage || 'Please check this field.';
    }

    function showFieldErrors(form) {
        let valid = true;
        constraintFields(form).forEach((field) => {
            const ok = field.checkValidity();
            field.classList.toggle('is-invalid', !ok);
            const error = field.parentElement?.querySelector(':scope > .rr-field-error');
            if (error) {
                error.textContent = ok ? '' : fieldMessage(field);
            }
            if (!ok) {
                valid = false;
            }
        });
        return valid;
    }

    const forms = document.querySelectorAll('.rr-admin form, #rr-rate-modal form, #rr-discount-modal form');
    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.hasAttribute('data-confirm-title')) {
                return;
            }
            arm(form);
            const fieldsValid = showFieldErrors(form);
            const selectsValid = validateSelects(form);
            if (!fieldsValid || !selectsValid) {
                event.preventDefault();
                if (window.RMAction) {
                    window.RMAction.unlock(form);
                }
                return;
            }
            disarm(form);
            clearSelectErrors(form);
        });
        form.addEventListener('input', (event) => {
            const field = event.target;
            if (!(field instanceof HTMLElement) || !field.matches('[data-rr-required], [data-rr-pattern]')) {
                return;
            }
            if (!field.classList.contains('is-invalid')) {
                return;
            }
            field.required = field.hasAttribute('data-rr-required');
            if (field.dataset.rrPattern) {
                field.pattern = field.dataset.rrPattern;
            }
            if (String(field.value).trim() !== '' && field.checkValidity()) {
                field.classList.remove('is-invalid');
                field.required = false;
                field.removeAttribute('pattern');
            }
        });
    });

    document.addEventListener('pointerdown', (event) => {
        const submit = event.target.closest('button[type="submit"]');
        const activeForm = submit ? submit.closest('form') : null;
        forms.forEach((form) => {
            if (form !== activeForm) {
                disarm(form);
                clearSelectErrors(form);
            }
        });
    }, true);

    const confirmModalEl = document.getElementById('rr-confirm-modal');
    const confirmModal = confirmModalEl && window.bootstrap ? new window.bootstrap.Modal(confirmModalEl) : null;
    let pendingForm = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm-title') || !page.contains(form)) {
            return;
        }
        if (form.dataset.rrConfirmed === '1') {
            delete form.dataset.rrConfirmed;
            return;
        }
        event.preventDefault();
        if (window.RMAction) {
            window.RMAction.unlock(form);
        }
        pendingForm = form;
        const title = form.dataset.confirmTitle || 'Confirm?';
        const text = form.dataset.confirmText || '';
        const kind = form.dataset.confirmKind || 'deactivate';
        if (!confirmModal) {
            if (window.confirm(title)) {
                form.dataset.rrConfirmed = '1';
                form.submit();
            }
            pendingForm = null;
            return;
        }
        document.getElementById('rr-confirm-title').textContent = title;
        document.getElementById('rr-confirm-text').textContent = text;
        const submitBtn = document.getElementById('rr-confirm-submit');
        if (submitBtn) {
            submitBtn.textContent = form.dataset.confirmButton || 'Confirm';
            submitBtn.className = kind === 'activate' ? 'btn btn-success' : 'btn btn-danger';
        }
        const icon = confirmModalEl.querySelector('.rsv-modal-icon i');
        if (icon) {
            icon.className = kind === 'activate' ? 'ti ti-circle-check' : 'ti ti-alert-triangle';
        }
        confirmModal.show();
    }, true);

    function showAlert(message, type) {
        page.querySelectorAll('.rsv-auto-alert').forEach((alert) => alert.remove());
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} rsv-auto-alert`;
        alert.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        alert.textContent = message;
        (page.querySelector('.rr-page-heading') || page.querySelector('.rsv-header'))?.insertAdjacentElement('afterend', alert);
        setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }

    function bumpStat(id, delta) {
        const el = document.getElementById(id);
        if (!el) {
            return;
        }
        const next = Math.max(0, Number(String(el.textContent).replace(/,/g, '')) + delta);
        el.textContent = next.toLocaleString('en-US');
    }

    function flipToggle(form) {
        const button = form.querySelector('button');
        const icon = button?.querySelector('i');
        const row = form.closest('tr');
        const badge = row?.querySelector('[data-label="Status"] .badge');
        if (!button || !icon || !badge) {
            return;
        }
        const activating = form.dataset.confirmKind === 'activate';
        const isRate = Boolean(form.closest('#rr-rate-catalog'));
        const subject = (button.getAttribute('aria-label') || '').replace(/^(Activate|Deactivate)\s+/i, '');
        badge.className = activating ? 'badge text-bg-success' : 'badge text-bg-secondary';
        badge.textContent = activating ? 'Active' : 'Inactive';
        form.action = form.action.replace(activating ? /\/activate(?=\/?$)/ : /\/deactivate(?=\/?$)/, activating ? '/deactivate' : '/activate');
        form.dataset.confirmKind = activating ? 'deactivate' : 'activate';
        form.dataset.confirmButton = activating ? 'Deactivate' : 'Activate';
        form.dataset.confirmTitle = activating
            ? (isRate ? 'Deactivate this rate?' : 'Deactivate this discount?')
            : (isRate ? 'Activate this rate?' : 'Activate this discount?');
        form.dataset.confirmText = activating
            ? (isRate ? 'It will no longer be used when a reservation is invoiced.' : 'This category will use a 0% discount until a new rule is added.')
            : (isRate ? 'It will be used when a reservation is invoiced.' : 'This category discount will apply again when a reservation is invoiced.');
        button.title = activating ? 'Deactivate' : 'Activate';
        button.setAttribute('aria-label', `${button.title} ${subject}`.trim());
        button.classList.toggle('btn-outline-danger', activating);
        button.classList.toggle('btn-outline-success', !activating);
        icon.className = activating ? 'ti ti-ban' : 'ti ti-circle-check';
        bumpStat(isRate ? 'rr-active-rates' : 'rr-active-discounts', activating ? 1 : -1);
    }

    function refreshHistory(section) {
        const url = new URL(window.location.href);
        url.searchParams.delete('section');
        url.hash = section === 'discount_history' ? 'rr-discount-history' : 'rr-rate-history';
        loadSection(section, url.toString());
    }

    document.getElementById('rr-confirm-submit')?.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }
        const form = pendingForm;
        pendingForm = null;
        confirmModal?.hide();
        const body = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        }).then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const errors = data.errors ? Object.values(data.errors).flat() : [];
                throw new Error(errors[0] || data.message || 'Could not update that row.');
            }
            flipToggle(form);
            showAlert(data.message || 'Updated.', 'success');
            refreshHistory(form.closest('#rr-rate-catalog') ? 'rate_history' : 'discount_history');
        }).catch((error) => {
            showAlert(error.message || 'Could not update that row.', 'danger');
        });
    });

    confirmModalEl?.addEventListener('hidden.bs.modal', () => {
        pendingForm = null;
    });

    const rateModalEl = document.getElementById('rr-rate-modal');
    const rateForm = document.getElementById('rr-rate-edit-form');
    const rateModal = rateModalEl && rateForm && window.bootstrap ? new window.bootstrap.Modal(rateModalEl) : null;
    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('.rr-edit-rate');
        if (!button || !rateModal || !rateForm) {
            return;
        }
        rateForm.action = button.dataset.action;
        const type = (catalog.types || []).find((item) => String(item.value) === String(button.dataset.type || ''));
        window.RMSelect.setValue('rr-edit-type', button.dataset.type || '', type?.label || 'Select type');
        filterResources(button.dataset.type || '', 'rr-edit-resource', button.dataset.resource || '');
        document.getElementById('rr-edit-base').value = button.dataset.base || '';
        document.getElementById('rr-edit-hours').value = button.dataset.hours || '';
        document.getElementById('rr-edit-extra').value = button.dataset.extra || '';
        document.getElementById('rr-edit-tax').value = button.dataset.tax || '0';
        document.getElementById('rr-edit-from').value = button.dataset.from || '';
        document.getElementById('rr-edit-to').value = button.dataset.to || '';
        document.getElementById('rr-edit-title').textContent = button.dataset.label || 'Edit rate';
        rateModal.show();
    });

    const discountModalEl = document.getElementById('rr-discount-modal');
    const discountForm = document.getElementById('rr-discount-edit-form');
    const discountModal = discountModalEl && discountForm && window.bootstrap ? new window.bootstrap.Modal(discountModalEl) : null;
    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('.rr-edit-discount');
        if (!button || !discountModal || !discountForm) {
            return;
        }
        discountForm.action = button.dataset.action;
        document.getElementById('rr-edit-category').value = button.dataset.category || '';
        document.getElementById('rr-edit-percent').value = button.dataset.percent || '';
        discountModal.show();
    });

    page.querySelectorAll('.rsv-auto-alert').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[char]));
    }

    const infoModalEl = document.getElementById('rr-info-modal');
    const infoModal = infoModalEl && window.bootstrap ? new window.bootstrap.Modal(infoModalEl) : null;

    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-rr-info]');
        if (!button || !infoModal) {
            return;
        }
        let details = [];
        try {
            details = JSON.parse(button.dataset.details || '[]');
        } catch (error) {
            details = [];
        }
        const title = [button.dataset.action, button.dataset.title].filter(Boolean).join(' · ') || 'Change details';
        const subtitle = [button.dataset.when, button.dataset.actor].filter(Boolean).join(' · ');
        document.getElementById('rr-info-title').textContent = title;
        document.getElementById('rr-info-sub').textContent = subtitle;
        const body = document.getElementById('rr-info-details');
        if (!Array.isArray(details) || details.length === 0) {
            body.innerHTML = '<p class="mb-0 text-muted">No details recorded.</p>';
        } else {
            body.innerHTML = details.map((row) => {
                const label = escapeHtml(row?.label || '');
                const value = escapeHtml(row?.value ?? '—');
                return `<div class="rsv-detail-row"><span class="rsv-detail-label">${label}</span><span class="rsv-detail-value">${value}</span></div>`;
            }).join('');
        }
        infoModal.show();
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('[data-rr-history]');
        if (!link) {
            return;
        }
        event.preventDefault();
        loadSection(link.dataset.rrHistory, link.href);
    });
})();
