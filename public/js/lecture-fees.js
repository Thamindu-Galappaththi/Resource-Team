(function () {
    const page = document.querySelector('.lf-page');
    if (!page) {
        return;
    }

    const catalog = document.getElementById('lf-catalog');
    const searchForm = page.querySelector('[data-lf-search]');
    const search = document.getElementById('lf-search');
    const typeFilter = document.getElementById('lf-filter-type');
    const courseFilter = document.getElementById('lf-filter-course');
    const qualificationFilter = document.getElementById('lf-filter-qualification');
    const statusFilter = document.getElementById('lf-filter-status');
    const clearBtn = document.getElementById('lf-clear');
    const baseUrl = page.dataset.lfUrl || window.location.pathname;
    let searchTimer = null;
    let clearing = false;
    let resetting = false;
    let saving = false;
    let requestId = 0;
    let officialRates = {};
    try {
        officialRates = JSON.parse(document.getElementById('lf-rates')?.textContent || '{}');
    } catch (error) {
        officialRates = {};
    }

    function selectValue(id) {
        return window.RMSelect ? window.RMSelect.getValue(id) : (document.getElementById(id)?.value || '');
    }

    function hasFilters() {
        return Boolean(
            (search?.value || '').trim()
            || selectValue('lf-filter-type')
            || selectValue('lf-filter-course')
            || selectValue('lf-filter-qualification')
            || (selectValue('lf-filter-status') && selectValue('lf-filter-status') !== 'all')
        );
    }

    function toggleClear() {
        if (clearBtn) {
            clearBtn.disabled = !hasFilters();
        }
    }

    function filterUrl() {
        const url = new URL(baseUrl, window.location.origin);
        const params = {
            search: (search?.value || '').trim(),
            user_type: selectValue('lf-filter-type'),
            department: selectValue('lf-filter-course'),
            qualification: selectValue('lf-filter-qualification'),
            status: selectValue('lf-filter-status') || 'all',
        };
        Object.entries(params).forEach(([key, value]) => {
            if (value && !(key === 'status' && value === 'all')) {
                url.searchParams.set(key, value);
            }
        });
        return url;
    }

    function loadCatalog(href) {
        if (!catalog) {
            window.location.href = href;
            return;
        }
        const current = ++requestId;
        const url = new URL(href, window.location.origin);
        catalog.classList.add('is-loading');
        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        }).then((response) => {
            if (!response.ok) {
                throw new Error('Could not load lecture rates.');
            }
            return response.text();
        }).then((html) => {
            if (current !== requestId) {
                return;
            }
            catalog.innerHTML = html;
            const clean = new URL(href, window.location.origin);
            history.replaceState({}, '', clean.toString());
        }).catch(() => {
            if (current === requestId) {
                window.location.href = href;
            }
        }).finally(() => {
            if (current === requestId) {
                catalog.classList.remove('is-loading');
            }
        });
    }

    function refresh() {
        toggleClear();
        const url = filterUrl();
        url.searchParams.delete('page');
        loadCatalog(url.toString());
    }

    searchForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        refresh();
    });
    search?.addEventListener('input', () => {
        toggleClear();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(refresh, 350);
    });
    [typeFilter, courseFilter, qualificationFilter, statusFilter].forEach((input) => {
        input?.addEventListener('change', () => {
            if (!clearing) {
                refresh();
            }
        });
    });
    clearBtn?.addEventListener('click', () => {
        if (clearBtn.disabled) {
            return;
        }
        clearing = true;
        if (search) {
            search.value = '';
        }
        window.RMSelect?.setValue('lf-filter-type', '', 'All lecture types');
        window.RMSelect?.setValue('lf-filter-course', '', 'All course levels');
        window.RMSelect?.setValue('lf-filter-qualification', '', 'All qualifications');
        window.RMSelect?.setValue('lf-filter-status', 'all', 'All statuses');
        clearing = false;
        refresh();
    });

    page.addEventListener('click', (event) => {
        const pageLink = event.target.closest('[data-rr-history], .rsv-pager a.page-link');
        if (pageLink && catalog?.contains(pageLink)) {
            event.preventDefault();
            loadCatalog(pageLink.href);
        }
    });

    function suggestedRate(type, course, qualification) {
        return officialRates?.[type]?.[course]?.[qualification];
    }

    function bindRateFill(typeId, courseId, qualificationId, rateId) {
        const fill = () => {
            const rateInput = document.getElementById(rateId);
            if (resetting || !rateInput || rateInput.dataset.manual === '1') {
                return;
            }
            const match = suggestedRate(selectValue(typeId), selectValue(courseId), selectValue(qualificationId));
            if (match != null) {
                rateInput.value = match;
            }
        };
        [typeId, courseId, qualificationId].forEach((id) => {
            document.getElementById(id)?.addEventListener('change', fill);
        });
        document.getElementById(rateId)?.addEventListener('input', (event) => {
            event.target.dataset.manual = '1';
        });
    }

    bindRateFill('lf-type', 'lf-course', 'lf-qualification', 'lf-rate');
    bindRateFill('lf-edit-type', 'lf-edit-course', 'lf-edit-qualification', 'lf-edit-rate');

    function showAlert(message, type) {
        page.querySelectorAll('.rsv-auto-alert').forEach((alert) => alert.remove());
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} rsv-auto-alert`;
        alert.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        alert.textContent = message;
        (page.querySelector('.rr-page-heading') || page)?.insertAdjacentElement('afterend', alert);
        setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }

    function applyStats(stats) {
        if (!stats) {
            return;
        }
        const map = {
            total: 'lf-stat-total',
            active: 'lf-stat-active',
            inactive: 'lf-stat-inactive',
            lecturers: 'lf-stat-lecturers',
        };
        Object.entries(map).forEach(([key, id]) => {
            const el = document.getElementById(id);
            if (el && stats[key] != null) {
                el.textContent = Number(stats[key]).toLocaleString('en-US');
            }
        });
    }

    function setSelect(id, value, label) {
        const input = document.getElementById(id);
        const wrap = input?.closest('[data-cu-select]') || input;
        if (input) {
            input.value = value;
            input.defaultValue = value;
        }
        const labelEl = wrap?.querySelector?.('.js-select-label');
        if (labelEl) {
            labelEl.textContent = label;
            labelEl.classList.toggle('text-muted', !value);
        }
        wrap?.classList?.remove('is-invalid');
        wrap?.querySelector?.('.cu-dropdown-toggle')?.classList.remove('is-invalid');
    }

    function resetAddForm() {
        const form = document.getElementById('lf-add-form');
        if (!form) {
            return;
        }
        const rateInput = form.querySelector('[name="hourly_rate"]');
        resetting = true;
        form.querySelectorAll('[data-cu-select]').forEach((wrap) => {
            const input = wrap.querySelector('input[type="hidden"]');
            const isStatus = input && (input.name === 'status' || input.id === 'lf-status');
            const placeholder = wrap.getAttribute('data-placeholder') || 'Select';
            if (input) {
                input.value = isStatus ? 'active' : '';
                input.defaultValue = input.value;
            }
            const labelEl = wrap.querySelector('.js-select-label');
            if (labelEl) {
                labelEl.textContent = isStatus ? 'Active' : placeholder;
                labelEl.classList.toggle('text-muted', !isStatus);
            }
            wrap.classList.remove('is-invalid');
            wrap.querySelector('.cu-dropdown-toggle')?.classList.remove('is-invalid');
        });
        if (rateInput) {
            rateInput.value = '';
            rateInput.defaultValue = '';
            delete rateInput.dataset.manual;
            rateInput.classList.remove('is-invalid');
        }
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('.rr-field-error').forEach((el) => {
            el.textContent = '';
        });
        resetting = false;
    }

    function submitRateForm(form, onSuccess) {
        if (saving) {
            return Promise.resolve();
        }
        const isAdd = form.id === 'lf-add-form';
        const payload = new FormData(form);
        const submit = isAdd
            ? document.getElementById('lf-add-submit')
            : form.querySelector('.rr-submit, [type="submit"]');
        saving = true;
        if (submit) {
            submit.disabled = true;
        }
        return fetch(form.action, {
            method: 'POST',
            body: payload,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
        }).then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const errors = data.errors ? Object.values(data.errors).flat() : [];
                throw new Error(errors[0] || data.message || 'Could not save that lecture rate.');
            }
            if (isAdd) {
                resetAddForm();
            }
            applyStats(data.stats);
            showAlert(data.message || 'Lecture rate saved.', 'success');
            refresh();
            onSuccess?.();
        }).catch((error) => {
            showAlert(error.message || 'Could not save that lecture rate.', 'danger');
        }).finally(() => {
            saving = false;
            if (submit) {
                submit.disabled = false;
            }
        });
    }

    window.lfSubmitLectureRate = function (form) {
        submitRateForm(form || document.getElementById('lf-add-form'));
    };

    const addForm = document.getElementById('lf-add-form');
    const addButton = document.getElementById('lf-add-submit');
    addForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        event.stopPropagation();
        submitRateForm(addForm);
    }, true);
    if (addButton && addButton.dataset.lfBound !== '1') {
        addButton.dataset.lfBound = '1';
        addButton.addEventListener('click', function () {
            submitRateForm(addForm);
        });
    }

    const editModalEl = document.getElementById('lf-edit-modal');
    const editForm = document.getElementById('lf-edit-form');
    const editModal = editModalEl && editForm && window.bootstrap ? new window.bootstrap.Modal(editModalEl) : null;
    page.addEventListener('click', (event) => {
        const button = event.target.closest('.lf-edit');
        if (!button || !editModal || !editForm) {
            return;
        }
        editForm.action = button.dataset.action;
        window.RMSelect?.setValue('lf-edit-type', button.dataset.type || '', button.dataset.typeLabel || 'Select lecture type');
        window.RMSelect?.setValue('lf-edit-course', button.dataset.course || '', button.dataset.courseLabel || 'Select course level');
        window.RMSelect?.setValue('lf-edit-qualification', button.dataset.qualification || '', button.dataset.qualificationLabel || 'Select qualification');
        const rateInput = document.getElementById('lf-edit-rate');
        rateInput.value = button.dataset.rate || '';
        rateInput.dataset.manual = '1';
        window.RMSelect?.setValue('lf-edit-status', button.dataset.status || 'active', button.dataset.status === 'inactive' ? 'Inactive' : 'Active');
        document.getElementById('lf-edit-title').textContent = button.dataset.label ? `Edit ${button.dataset.label}` : 'Edit lecture rate';
        editModal.show();
    });
    editForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        submitRateForm(editForm, () => editModal?.hide());
    });

    const confirmModalEl = document.getElementById('lf-confirm-modal');
    const confirmModal = confirmModalEl && window.bootstrap ? new window.bootstrap.Modal(confirmModalEl) : null;
    let pendingForm = null;
    page.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm-title') || !page.contains(form)) {
            return;
        }
        event.preventDefault();
        pendingForm = form;
        document.getElementById('lf-confirm-title').textContent = form.getAttribute('data-confirm-title') || 'Confirm?';
        document.getElementById('lf-confirm-text').textContent = form.getAttribute('data-confirm-text') || '';
        const submit = document.getElementById('lf-confirm-submit');
        submit.textContent = form.getAttribute('data-confirm-button') || 'Confirm';
        const kind = form.getAttribute('data-confirm-kind');
        submit.className = kind === 'delete' ? 'btn btn-danger' : (kind === 'deactivate' ? 'btn btn-warning' : 'btn btn-primary');
        confirmModal?.show();
    });
    document.getElementById('lf-confirm-submit')?.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }
        const form = pendingForm;
        pendingForm = null;
        confirmModal?.hide();
        submitRateForm(form);
    });

    page.querySelectorAll('.rsv-auto-alert').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    toggleClear();
}());
