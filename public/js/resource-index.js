document.addEventListener('DOMContentLoaded', () => {
    const config = window.RM || {};
    const canManage = Boolean(config.canManage);
    const pageSize = 10;
    let allResources = [];
    let filteredResources = [];
    let currentPage = 1;
    let editResourceTypes = [];
    let searchTimer = null;
    let lookupCache = {
        categories: Array.isArray(config.lookups?.categories) ? config.lookups.categories : [],
        types: Array.isArray(config.lookups?.types) ? config.lookups.types : [],
        locations: Array.isArray(config.lookups?.locations) ? config.lookups.locations : [],
    };

    const swalFire = (options) => window.RMSwal.fire(options);

    loadResources();
    document.getElementById('applyFiltersBtn')?.addEventListener('click', () => { currentPage = 1; applyFilters(); });
    document.getElementById('clearFiltersBtn')?.addEventListener('click', clearFilters);
    document.getElementById('searchInput')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 1; applyFilters(); }, 180);
    });
    document.getElementById('searchInput')?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') { event.preventDefault(); currentPage = 1; applyFilters(); }
    });
    document.getElementById('resourceTableBody')?.addEventListener('click', onTableClick);
    document.getElementById('rmPagination')?.addEventListener('click', onPaginationClick);
    document.getElementById('resourceCategorySelect')?.addEventListener('change', (event) => {
        const types = editResourceTypes.filter((item) => String(item.resource_category_id) === String(event.currentTarget.value));
        window.RMSelect.setOptions('resourceTypeSelect', types.map((item) => ({ value: item.id, label: item.name })), 'Select a resource type (e.g., Laptop)', { disabled: !event.currentTarget.value });
    });
    document.getElementById('editResourceForm')?.addEventListener('submit', saveResource);

    async function loadResources() {
        const tbody = document.getElementById('resourceTableBody');
        try {
            const data = await fetchJSON(config.listUrl);
            allResources = Array.isArray(data) ? data : [];
            populateCategoryFilter(allResources);
            updateStatCards(allResources);
            applyFilters();
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(error.message || 'Failed to load resources.')}</td></tr>`;
        }
    }

    function populateCategoryFilter(resources) {
        const current = window.RMSelect.getValue('categoryFilter');
        const names = [...new Set(resources.map((resource) => resource.type?.category?.name).filter(Boolean))].sort();
        window.RMSelect.setOptions('categoryFilter', names.map((name) => ({ value: name, label: name })), 'All categories');
        if (current) {
            const match = names.find((name) => name === current);
            if (match) window.RMSelect.setValue('categoryFilter', match, match);
        }
    }

    function updateStatCards(resources) {
        setText('statTotal', resources.length);
        setText('statActive', resources.filter((resource) => resource.status === 'active').length);
        setText('statMaintenance', resources.filter((resource) => resource.status === 'under_maintenance').length);
        setText('statPending', resources.filter((resource) => resource.status === 'pending_deletion').length);
    }

    function applyFilters() {
        const search = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
        const category = document.getElementById('categoryFilter')?.value || '';
        const status = document.getElementById('statusFilter')?.value || '';

        filteredResources = allResources.filter((resource) => {
            const haystack = [
                resource.name_model,
                resource.id,
                resource.serial_number,
                resource.type?.name,
                resource.type?.category?.name,
                resource.location?.name,
            ].join(' ').toLowerCase();
            const matchesSearch = !search || haystack.includes(search);
            const matchesCategory = !category || resource.type?.category?.name === category;
            const matchesStatus = !status || resource.status === status;
            return matchesSearch && matchesCategory && matchesStatus;
        });

        const totalPages = Math.max(1, Math.ceil(filteredResources.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        renderTable();
        renderPagination();
    }

    function clearFilters() {
        document.getElementById('searchInput').value = '';
        window.RMSelect.setValue('categoryFilter', '', 'All categories');
        window.RMSelect.setValue('statusFilter', '', 'All statuses');
        currentPage = 1;
        applyFilters();
    }

    function pagedResources() {
        const start = (currentPage - 1) * pageSize;
        return filteredResources.slice(start, start + pageSize);
    }

    function renderTable() {
        const tbody = document.getElementById('resourceTableBody');
        const pageRows = pagedResources();
        tbody.replaceChildren();

        if (filteredResources.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5"><div class="fw-semibold mb-1">No resources found</div><div class="text-muted small">Try another search or clear the filters.</div></td></tr>`;
        } else {
            pageRows.forEach((resource) => tbody.appendChild(buildRow(resource)));
        }

        const start = filteredResources.length ? (currentPage - 1) * pageSize + 1 : 0;
        const end = Math.min(currentPage * pageSize, filteredResources.length);
        setText('resultCount', `Showing ${start}–${end} of ${filteredResources.length} resources`);
    }

    function buildRow(resource) {
        const row = document.createElement('tr');
        row.dataset.resourceId = resource.id;
        row.innerHTML = `
            <td>
                <div class="fw-semibold">${escapeHtml(resource.name_model)}</div>
                <div class="text-muted small">ID: ${escapeHtml(resource.id)}</div>
            </td>
            <td>${escapeHtml(resource.type?.category?.name ?? '—')}</td>
            <td>${escapeHtml(resource.type?.name ?? '—')}</td>
            <td>${escapeHtml(resource.location?.name ?? '—')}</td>
            <td>${escapeHtml(resource.serial_number || '—')}</td>
            <td>${statusBadge(resource.status)}</td>
            <td class="text-end">${actionButtons(resource)}</td>
        `;
        return row;
    }

    function actionButtons(resource) {
        const view = `<button type="button" class="btn btn-sm btn-outline-primary rm-icon-btn" data-action="view" data-id="${resource.id}" aria-label="View ${escapeHtml(resource.name_model)}" title="View"><i class="ti ti-eye"></i></button>`;
        const edit = canManage
            ? `<button type="button" class="btn btn-sm btn-outline-secondary rm-icon-btn" data-action="edit" data-id="${resource.id}" aria-label="Edit ${escapeHtml(resource.name_model)}" title="Edit"><i class="ti ti-edit"></i></button>`
            : '';

        if (resource.status === 'pending_deletion' && canManage) {
            return `<div class="d-flex flex-wrap justify-content-end gap-1">${view}${edit}
                <button type="button" class="btn btn-sm btn-success" data-action="approve" data-id="${resource.id}">Approve</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="reject" data-id="${resource.id}">Reject</button>
            </div>`;
        }

        const remove = canManage && resource.status !== 'deleted'
            ? `<button type="button" class="btn btn-sm btn-outline-danger rm-icon-btn" data-action="delete" data-id="${resource.id}" aria-label="Delete ${escapeHtml(resource.name_model)}" title="Delete"><i class="ti ti-trash"></i></button>`
            : '';

        return `<div class="d-flex flex-wrap justify-content-end gap-1">${view}${edit}${remove}</div>`;
    }

    function renderPagination() {
        const nav = document.getElementById('rmPagination');
        if (!nav) return;
        const totalPages = Math.ceil(filteredResources.length / pageSize);
        nav.replaceChildren();
        if (totalPages <= 1) return;

        const addItem = (label, page, disabled, active) => {
            const li = document.createElement('li');
            li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
            li.innerHTML = `<button type="button" class="page-link" data-page="${page}">${label}</button>`;
            nav.appendChild(li);
        };

        addItem('Prev', currentPage - 1, currentPage === 1, false);
        visiblePages(totalPages, currentPage).forEach((page) => {
            if (page === '…') {
                const li = document.createElement('li');
                li.className = 'page-item disabled';
                li.innerHTML = '<span class="page-link">…</span>';
                nav.appendChild(li);
                return;
            }
            addItem(String(page), page, false, page === currentPage);
        });
        addItem('Next', currentPage + 1, currentPage === totalPages, false);
    }

    function visiblePages(total, current) {
        if (total <= 7) return Array.from({ length: total }, (_, index) => index + 1);
        const pages = new Set([1, total, current, current - 1, current + 1]);
        const ordered = [...pages].filter((page) => page >= 1 && page <= total).sort((a, b) => a - b);
        return ordered.reduce((list, page, index) => {
            if (index && page - ordered[index - 1] > 1) list.push('…');
            list.push(page);
            return list;
        }, []);
    }

    function onPaginationClick(event) {
        const button = event.target.closest('[data-page]');
        if (!button || button.parentElement.classList.contains('disabled')) return;
        currentPage = Number(button.dataset.page);
        renderTable();
        renderPagination();
    }

    function onTableClick(event) {
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const id = button.dataset.id;
        const actions = { view: viewResource, edit: editResource, delete: confirmDelete, approve: approveDelete, reject: rejectDelete };
        actions[button.dataset.action]?.(id);
    }

    function statusBadge(status) {
        const map = {
            active: ['bg-success-subtle text-success', 'Active'],
            inactive: ['bg-secondary-subtle text-secondary', 'Inactive'],
            under_maintenance: ['bg-warning-subtle text-warning', 'Under Maintenance'],
            decommissioned: ['bg-dark-subtle text-dark', 'Decommissioned'],
            pending_deletion: ['bg-danger-subtle text-danger', 'Pending Deletion'],
            deleted: ['bg-secondary-subtle text-secondary', 'Deleted'],
        };
        const [cls, label] = map[status] || ['bg-light text-dark', status || '—'];
        return `<span class="badge rounded-pill rm-badge ${cls} px-3 py-2">${escapeHtml(label)}</span>`;
    }

    async function viewResource(id) {
        try {
            const cached = allResources.find((item) => String(item.id) === String(id));
            const resource = cached || await fetchJSON(config.showUrl.replace('__ID__', id));
            const linked = (resource.linked_resources || []).map((item) => `${item.name_model} (#${item.id})`).join(', ') || '—';
            const details = [
                ['Name / Model', resource.name_model],
                ['Category', resource.type?.category?.name],
                ['Type', resource.type?.name],
                ['Location', resource.location?.name],
                ['Serial Number', resource.serial_number],
                ['Status', statusLabel(resource.status)],
                ['Linked Resources', linked],
            ];
            const html = `<dl class="row text-start mb-0">${details.map(([label, value]) =>
                `<dt class="col-sm-4">${escapeHtml(label)}</dt><dd class="col-sm-8">${escapeHtml(value || '—')}</dd>`
            ).join('')}</dl>`;
            await swalFire({ title: 'Resource details', html, width: 680, confirmButtonText: 'Close' });
        } catch (error) {
            await swalFire({ icon: 'error', title: 'View failed', text: error.message });
        }
    }

    async function ensureLookups() {
        if (lookupCache.categories.length && lookupCache.types.length && lookupCache.locations.length) {
            return lookupCache;
        }
        const [categories, types, locations] = await Promise.all([
            fetchJSON(config.categoriesUrl),
            fetchJSON(config.typesUrl),
            fetchJSON(config.locationsUrl),
        ]);
        lookupCache = { categories, types, locations };
        return lookupCache;
    }

    function fillEditForm(resource, lookups) {
        editResourceTypes = lookups.types;
        window.RMSelect.setOptions('resourceCategorySelect', lookups.categories.map((item) => ({ value: item.id, label: item.name })), 'Select a category (e.g., IT Equipment)');
        window.RMSelect.setOptions('resourceLocationSelect', lookups.locations.map((item) => ({ value: item.id, label: item.name })), 'Select a location (e.g., Block A, Floor 2)');
        const categoryId = resource.type?.resource_category_id;
        const category = lookups.categories.find((item) => String(item.id) === String(categoryId));
        if (category) window.RMSelect.setValue('resourceCategorySelect', category.id, category.name);
        const filteredTypes = lookups.types.filter((item) => String(item.resource_category_id) === String(categoryId));
        window.RMSelect.setOptions('resourceTypeSelect', filteredTypes.map((item) => ({ value: item.id, label: item.name })), 'Select a resource type (e.g., Laptop)', { disabled: false });
        const type = filteredTypes.find((item) => String(item.id) === String(resource.resource_type_id));
        if (type) window.RMSelect.setValue('resourceTypeSelect', type.id, type.name);
        const location = lookups.locations.find((item) => String(item.id) === String(resource.location_id));
        if (location) window.RMSelect.setValue('resourceLocationSelect', location.id, location.name);
        window.RMSelect.setValue('resourceStatusSelect', resource.status || '', statusLabel(resource.status));
        document.getElementById('resourceNameModel').value = resource.name_model || '';
        document.getElementById('serialNumber').value = resource.serial_number || '';
    }

    async function editResource(id) {
        const modalElement = document.getElementById('editResourceModal');
        const form = document.getElementById('editResourceForm');
        const loading = document.getElementById('editResourceLoading');
        if (!modalElement || !form) return;

        const resource = allResources.find((item) => String(item.id) === String(id));
        if (!resource) {
            await swalFire({ icon: 'error', title: 'Could not load resource', text: 'That resource is no longer in the list.' });
            return;
        }

        const ready = lookupCache.categories.length && lookupCache.types.length && lookupCache.locations.length;
        if (ready) {
            fillEditForm(resource, lookupCache);
            form.dataset.resourceId = id;
            if (loading) loading.hidden = true;
            form.hidden = false;
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
            return;
        }

        form.hidden = true;
        if (loading) loading.hidden = false;
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
        try {
            fillEditForm(resource, await ensureLookups());
            form.dataset.resourceId = id;
            if (loading) loading.hidden = true;
            form.hidden = false;
        } catch (error) {
            bootstrap.Modal.getInstance(modalElement)?.hide();
            await swalFire({ icon: 'error', title: 'Could not load resource', text: error.message });
        }
    }

    async function saveResource(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const button = document.getElementById('editResourceSaveButton');
        button.disabled = true;
        try {
            await requestJSON(config.updateUrl.replace('__ID__', form.dataset.resourceId), 'PUT', {
                category_id: document.getElementById('resourceCategorySelect').value,
                resource_type_id: document.getElementById('resourceTypeSelect').value,
                location_id: document.getElementById('resourceLocationSelect').value,
                name_model: document.getElementById('resourceNameModel').value.trim(),
                serial_number: document.getElementById('serialNumber').value.trim() || null,
                status: document.getElementById('resourceStatusSelect').value,
            });
            bootstrap.Modal.getInstance(document.getElementById('editResourceModal'))?.hide();
            await loadResources();
            await swalFire({ icon: 'success', title: 'Resource updated', text: 'Changes were saved.', timer: 2500, showConfirmButton: false });
        } catch (error) {
            await swalFire({ icon: 'error', title: 'Update failed', text: error.message });
        } finally {
            button.disabled = false;
        }
    }

    async function confirmDelete(id) {
        const confirmation = await swalFire({
            icon: 'warning',
            title: 'Delete this resource?',
            text: 'It will be hidden from the inventory. Existing reservation history is kept.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            customClass: { ...window.RMSwal.base.customClass, confirmButton: 'btn btn-danger rm-swal-btn' },
        });
        if (!confirmation.isConfirmed) return;

        try {
            await requestJSON(config.deleteUrl.replace('__ID__', id), 'DELETE');
            await loadResources();
            await swalFire({ icon: 'success', title: 'Deleted', text: 'Resource removed from the inventory.', timer: 2500, showConfirmButton: false });
        } catch (error) {
            await swalFire({ icon: 'error', title: 'Delete failed', text: error.message });
        }
    }

    async function approveDelete(id) {
        const confirmation = await swalFire({
            icon: 'warning',
            title: 'Approve removal?',
            text: 'This marks the resource as deleted.',
            showCancelButton: true,
            confirmButtonText: 'Approve',
            customClass: { ...window.RMSwal.base.customClass, confirmButton: 'btn btn-danger rm-swal-btn' },
        });
        if (!confirmation.isConfirmed) return;
        await runAction(config.approveUrl.replace('__ID__', id), 'Removal approved');
    }

    async function rejectDelete(id) {
        await runAction(config.rejectUrl.replace('__ID__', id), 'Deletion request rejected');
    }

    async function runAction(url, successText) {
        try {
            await requestJSON(url, 'POST');
            await loadResources();
            await swalFire({ icon: 'success', title: 'Updated', text: successText, timer: 2200, showConfirmButton: false });
        } catch (error) {
            await swalFire({ icon: 'error', title: 'Action failed', text: error.message });
        }
    }

    async function fetchJSON(url) {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || 'Request failed.');
        return payload;
    }

    async function requestJSON(url, method, data) {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: data ? JSON.stringify(data) : undefined,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Request failed.');
        return payload;
    }

    function statusLabel(status) {
        return ({
            active: 'Active',
            inactive: 'Inactive',
            under_maintenance: 'Under Maintenance',
            decommissioned: 'Decommissioned',
            pending_deletion: 'Pending Deletion',
            deleted: 'Deleted',
        })[status] || status || '—';
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[character]));
    }
});
