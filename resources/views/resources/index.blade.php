@extends('layouts.app')

@section('title', 'Existing Resources')

@section('content')
@php($canManageResources = auth()->user()?->hasPermission('resources.create') ?? false)
<div class="container-fluid mt-4">
    <style>
        #editResourceModal .modal-dialog { transform: scale(.96); transition: transform 250ms ease, opacity 250ms ease; }
        #editResourceModal.show .modal-dialog { transform: scale(1); }
        #resourceTable .btn { transition: transform 200ms ease, box-shadow 200ms ease; }
        #resourceTable .btn:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(42, 53, 71, .16); }
        #resourceTable .resource-row-removing { opacity: 0; transform: translateX(16px); transition: opacity 250ms ease, transform 250ms ease; }
    </style>

    <h4 class="mb-3">Existing Resources</h4>

    {{-- ===================== SUMMARY STAT CARDS ===================== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Resources</div>
                    <div class="fs-3 fw-bold" id="statTotal">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body">
                    <div class="text-muted small">Active</div>
                    <div class="fs-3 fw-bold text-success" id="statActive">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body">
                    <div class="text-muted small">Under Maintenance</div>
                    <div class="fs-3 fw-bold text-warning" id="statMaintenance">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body">
                    <div class="text-muted small">Pending Deletion</div>
                    <div class="fs-3 fw-bold text-danger" id="statPending">0</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== SEARCH + FILTERS ===================== --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="Search resource name or ID...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="categoryFilter">
                        <option value="">All Categories</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="under_maintenance">Under Maintenance</option>
                        <option value="decommissioned">Decommissioned</option>
                        <option value="pending_deletion">Pending Deletion</option>
                        <option value="deleted">Deleted</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary w-100" id="applyFiltersBtn">Apply Filters</button>
                        <button class="btn btn-outline-secondary w-100" id="clearFiltersBtn" type="button">Clear Filters</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== RESOURCE TABLE ===================== --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="resourceTable">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th>Resource Name</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Serial Number</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="resourceTableBody">
                        <tr><td colspan="7" class="text-center text-muted py-4">Loading resources…</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mt-2 mb-0" id="resultCount"></p>
        </div>
    </div>

    @if ($canManageResources)
        <div class="modal fade" id="editResourceModal" tabindex="-1" aria-labelledby="editResourceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editResourceModalLabel">Edit Resource</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="editResourceLoading" class="text-center text-muted py-4" role="status">Loading resource details…</div>
                        <form id="editResourceForm" hidden>
                            @csrf
                            @include('resources.partials.resource-fields')
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="editResourceSaveButton">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let allResources = [];   // everything loaded from the server
let categoriesSeen = new Set();
let editResourceTypes = [];
const canManageResources = @json($canManageResources);

document.addEventListener('DOMContentLoaded', () => {
    loadResources();

    document.getElementById('applyFiltersBtn').addEventListener('click', applyFilters);
    document.getElementById('clearFiltersBtn').addEventListener('click', clearFilters);
    document.getElementById('searchInput').addEventListener('input', applyFilters);
});

async function loadResources() {
    try {
        const response = await fetch('/resource-list', { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Could not load resources.');
        allResources = data;
        populateCategoryFilter(data);
        updateStatCards(data);
        applyFilters();
    } catch (error) {
        console.error('Failed to load resources', error);
        document.getElementById('resourceTableBody').innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(error.message || 'Failed to load resources.')}</td></tr>`;
    }
}

function populateCategoryFilter(resources) {
    const select = document.getElementById('categoryFilter');
    resources.forEach(r => {
        const catName = r.type?.category?.name;
        if (catName && !categoriesSeen.has(catName)) {
            categoriesSeen.add(catName);
            const opt = document.createElement('option');
            opt.value = catName;
            opt.textContent = catName;
            select.appendChild(opt);
        }
    });
}

function updateStatCards(resources) {
    document.getElementById('statTotal').textContent = resources.length;
    document.getElementById('statActive').textContent =
        resources.filter(r => r.status === 'active').length;
    document.getElementById('statMaintenance').textContent =
        resources.filter(r => r.status === 'under_maintenance').length;
    document.getElementById('statPending').textContent =
        resources.filter(r => r.status === 'pending_deletion').length;
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;

    const filtered = allResources.filter(r => {
        const matchesSearch = !search || r.name_model.toLowerCase().includes(search);
        const matchesCategory = !category || r.type?.category?.name === category;
        const matchesStatus = !status || r.status === status;
        return matchesSearch && matchesCategory && matchesStatus;
    });

    renderTable(filtered);
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value = '';
    applyFilters();
}

function statusBadge(status) {
    const map = {
        active: 'bg-success-subtle text-success',
        inactive: 'bg-secondary-subtle text-secondary',
        under_maintenance: 'bg-warning-subtle text-warning',
        decommissioned: 'bg-dark-subtle text-dark',
        pending_deletion: 'bg-danger-subtle text-danger',
        deleted: 'bg-secondary-subtle text-secondary',
    };
    const labels = {
        active: 'Active',
        inactive: 'Inactive',
        under_maintenance: 'Under Maintenance',
        decommissioned: 'Decommissioned',
        pending_deletion: 'Pending Deletion',
        deleted: 'Deleted',
    };
    const cls = map[status] || 'bg-light text-dark';
    const label = labels[status] || status;
    return `<span class="badge rounded-pill ${cls} px-3 py-2">${label}</span>`;
}

function renderTable(resources) {
    const tbody = document.getElementById('resourceTableBody');
    tbody.innerHTML = '';

    if (resources.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No resources found.</td></tr>`;
    }

    resources.forEach(resource => {
        const category = resource.type?.category?.name ?? '-';
        const type = resource.type?.name ?? '-';
        const location = resource.location?.name ?? '-';
        const serial = resource.serial_number || '—';

        const row = document.createElement('tr');
        row.dataset.resourceId = resource.id;
        row.innerHTML = `
            <td>
                <div class="fw-semibold">${escapeHtml(resource.name_model)}</div>
                <div class="text-muted small">ID: ${resource.id}</div>
            </td>
            <td>${escapeHtml(category)}</td>
            <td>${escapeHtml(type)}</td>
            <td>${escapeHtml(location)}</td>
            <td>${escapeHtml(serial)}</td>
            <td>${statusBadge(resource.status)}</td>
            <td class="text-end">${actionButtons(resource)}</td>
        `;
        tbody.appendChild(row);
    });

    document.getElementById('resultCount').textContent =
        `Showing ${resources.length} of ${allResources.length} results`;
}

function actionButtons(resource) {
    const controls = `<button type="button" class="btn btn-sm btn-outline-primary" aria-label="View resource ${resource.id}" title="View" onclick="viewResource(${resource.id})"><i class="ti ti-eye" aria-hidden="true"></i><span class="visually-hidden">View</span></button>${canManageResources ? `<button type="button" class="btn btn-sm btn-outline-secondary" aria-label="Edit resource ${resource.id}" title="Edit" onclick="editResource(${resource.id})"><i class="ti ti-edit" aria-hidden="true"></i><span class="visually-hidden">Edit</span></button>` : ''}`;
    if (resource.status === 'pending_deletion') {
        return `
            <div class="d-flex flex-wrap justify-content-end gap-1">${controls}
            <button class="btn btn-sm btn-success" onclick="approveDelete(${resource.id})">Approve</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="rejectDelete(${resource.id})">Reject</button></div>
        `;
    }
    if (resource.status === 'deleted') {
        return `<div class="d-flex flex-wrap justify-content-end gap-1">${controls}<span class="text-muted align-self-center">—</span></div>`;
    }
    return `<div class="d-flex flex-wrap justify-content-end gap-1">${controls}<button class="btn btn-sm btn-outline-danger" aria-label="Request deletion for resource ${resource.id}" onclick="requestDelete(${resource.id})">Delete</button></div>`;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
}

async function viewResource(id) {
    try {
        const response = await fetch(`/resources/${id}`, { headers: { 'Accept': 'application/json' } });
        const resource = await response.json();
        if (!response.ok) throw new Error(resource.message || 'Could not load resource details.');
        const details = [
            ['Resource ID', resource.id], ['Name / Model', resource.name_model],
            ['Category', resource.type?.category?.name || '—'], ['Type', resource.type?.name || '—'],
            ['Location', resource.location?.name || '—'], ['Serial Number', resource.serial_number || '—'],
            ['Status', resource.status || '—'], ['Created', resource.created_at || '—'], ['Last Updated', resource.updated_at || '—'],
            ['Linked Resources', (resource.linked_resources || []).map(item => `${item.name_model} (#${item.id})`).join(', ') || '—']
        ];
        const html = `<dl class="row text-start mb-0">${details.map(([label, value]) => `<dt class="col-sm-4">${escapeHtml(label)}</dt><dd class="col-sm-8">${escapeHtml(value)}</dd>`).join('')}</dl>`;
        await Swal.fire({ title: 'Resource Details', html, width: 700, confirmButtonText: 'Close' });
    } catch (error) {
        await Swal.fire({ icon: 'error', title: 'View failed', text: error.message });
    }
}

async function editResource(id) {
    const modalElement = document.getElementById('editResourceModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const form = document.getElementById('editResourceForm');
    const loading = document.getElementById('editResourceLoading');
    form.hidden = true;
    loading.hidden = false;
    modal.show();

    try {
        const [resource, categories, types, locations] = await Promise.all([
            fetchResourceJSON(`/resources/${id}`),
            fetchResourceJSON('/resource-categories'),
            fetchResourceJSON('/resource-types'),
            fetchResourceJSON('/locations')
        ]);
        editResourceTypes = types;
        fillSelect('resourceCategorySelect', categories, 'Select a category (e.g., IT Equipment)', item => item.name, item => item.id);
        fillSelect('resourceLocationSelect', locations, 'Select a location (e.g., Block A, Floor 2)', item => item.name, item => item.id);
        const categorySelect = document.getElementById('resourceCategorySelect');
        const typeSelect = document.getElementById('resourceTypeSelect');
        categorySelect.value = resource.type.resource_category_id;
        fillSelect('resourceTypeSelect', types.filter(item => String(item.resource_category_id) === String(categorySelect.value)), 'Select a resource type (e.g., Laptop)', item => item.name, item => item.id);
        typeSelect.disabled = false;
        typeSelect.value = resource.resource_type_id;
        document.getElementById('resourceLocationSelect').value = resource.location_id;
        document.getElementById('resourceNameModel').value = resource.name_model || '';
        document.getElementById('serialNumber').value = resource.serial_number || '';
        document.getElementById('resourceStatusSelect').value = resource.status || '';
        form.dataset.resourceId = id;
        loading.hidden = true;
        form.hidden = false;
    } catch (error) {
        modal.hide();
        await Swal.fire({ icon: 'error', title: 'Could not load resource', text: error.message });
    }
}

document.getElementById('resourceCategorySelect')?.addEventListener('change', event => {
    const selectedCategory = event.currentTarget.value;
    fillSelect('resourceTypeSelect', editResourceTypes.filter(item => String(item.resource_category_id) === String(selectedCategory)), 'Select a resource type (e.g., Laptop)', item => item.name, item => item.id);
    document.getElementById('resourceTypeSelect').disabled = false;
});

async function fetchResourceJSON(url) {
    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || `Could not load ${url}.`);
    return payload;
}

function fillSelect(id, items, placeholder, label, value) {
    const select = document.getElementById(id);
    select.replaceChildren(new Option(placeholder, '', true, true));
    select.options[0].disabled = true;
    items.forEach(item => select.add(new Option(label(item), value(item))));
}

document.getElementById('editResourceForm')?.addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const id = form.dataset.resourceId;
    const button = document.getElementById('editResourceSaveButton');
    button.disabled = true;
    try {
        const response = await fetch(`/resources/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                category_id: document.getElementById('resourceCategorySelect').value,
                resource_type_id: document.getElementById('resourceTypeSelect').value,
                location_id: document.getElementById('resourceLocationSelect').value,
                name_model: document.getElementById('resourceNameModel').value.trim(),
                serial_number: document.getElementById('serialNumber').value.trim() || null,
                status: document.getElementById('resourceStatusSelect').value
            })
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Could not update resource.');
        bootstrap.Modal.getInstance(document.getElementById('editResourceModal')).hide();
        await loadResources();
        await Swal.fire({ icon: 'success', title: 'Resource updated', text: 'Changes were saved.' });
    } catch (error) {
        await Swal.fire({ icon: 'error', title: 'Update failed', text: error.message });
    } finally {
        button.disabled = false;
    }
});

function requestDelete(id) {
    confirmDelete(id);
}

async function confirmDelete(id) {
    const confirmation = await Swal.fire({
        icon: 'warning',
        title: 'Are you sure?',
        text: 'This resource will be moved to the deleted items state and hidden from resource lists.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Cancel'
    });
    if (!confirmation.isConfirmed) return;

    try {
        const response = await fetch(`/resources/${id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || 'Could not delete resource.');
        const row = document.querySelector(`[data-resource-id="${id}"]`);
        row?.classList.add('resource-row-removing');
        await new Promise(resolve => setTimeout(resolve, 260));
        await loadResources();
        await Swal.fire({ icon: 'success', title: 'Deleted', text: 'Resource deleted successfully.' });
    } catch (error) {
        await Swal.fire({ icon: 'error', title: 'Delete failed', text: error.message });
    }
}

function approveDelete(id) {
    if (!confirm('Approve permanent removal of this resource?')) return;
    postAction(`/resources/${id}/approve-delete`);
}

function rejectDelete(id) {
    postAction(`/resources/${id}/reject-delete`);
}

function postAction(url) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    })
        .then(res => res.json())
        .then(() => loadResources())
        .catch(err => console.error('Action failed', err));
}
</script>
@endsection
