@extends('layouts.app')

@section('title', 'Existing Resources')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/resource-management.css') }}?v=20">
@endpush

@section('content')
@php($canManageResources = auth()->user()?->hasPermission('resources.create') ?? false)
<<<<<<< HEAD
<div class="container-fluid mt-4 resource-index-page">
    <style>
        .resource-index-page { --ri-ink:#152238; --ri-muted:#65738a; --ri-line:#dfe5ee; color:var(--ri-ink); }
        .resource-index-page .page-intro { margin-bottom:1.25rem; }
        .resource-index-page .card { border:1px solid var(--ri-line)!important; border-radius:16px; background:#fff; box-shadow:0 10px 30px rgba(19,35,58,.09)!important; }
        .resource-index-page .card.resource-stat-card { border:1px solid #e5ebf3!important; background:#fff!important; box-shadow:0 10px 24px rgba(9,30,66,.1)!important; }
        .resource-index-page .filter-card, .resource-index-page .table-card { animation:ri-rise .55s .12s both; }
        .resource-index-page .card-body { padding:1.25rem; }
        .resource-index-page .text-muted { color:var(--ri-muted)!important; }
        .resource-index-page .form-control, .resource-index-page .form-select { min-height:44px; color:#18263a; border-color:#cfd8e5; border-radius:9px; box-shadow:none; }
        .resource-index-page .form-control:focus, .resource-index-page .form-select:focus { border-color:#5578e8; box-shadow:0 0 0 .2rem rgba(49,95,232,.15); }
        .resource-index-page .btn-primary { --bs-btn-bg:#315fe8; --bs-btn-border-color:#315fe8; --bs-btn-hover-bg:#244ecb; --bs-btn-hover-border-color:#244ecb; border-radius:9px; font-weight:600; }
        .resource-index-page #resourceTable thead th { color:#526078; background:#edf1f7; border-bottom-color:#dfe5ee; font-size:.72rem; letter-spacing:.045em; }
        .resource-index-page #resourceTable tbody td { color:#26364d; border-color:#edf0f5; }
        .resource-index-page #resourceTable tbody tr { transition:background-color .18s ease,transform .18s ease; }
        .resource-index-page #resourceTable tbody tr:hover { background:#f2f6fc; }
        .resource-index-page #resourceTable tbody tr.resource-row-enter { animation:ri-row-in .3s both; }
        .resource-index-page .btn { transition:transform .18s ease,box-shadow .18s ease,background-color .18s ease; }
        .resource-index-page .btn:hover { transform:translateY(-1px); }
        .resource-index-page .btn:focus-visible { outline:3px solid rgba(49,95,232,.3); outline-offset:2px; }
        .resource-index-page .search-wrap { position:relative; }
        .resource-index-page .search-wrap .form-control { padding-left:2.6rem; }
        .resource-index-page .search-icon { position:absolute; z-index:2; top:50%; left:.9rem; color:#8290a4; transform:translateY(-50%); pointer-events:none; }
        .resource-index-page .results-summary { color:#65738a; }
        .resource-index-page .status-badge { display:inline-flex; align-items:center; justify-content:center; min-width:104px; min-height:38px; padding:.5rem 1rem!important; font-size:.92rem; font-weight:600; letter-spacing:.01em; }
        .resource-index-page .status-badge.status-inactive { color:#2165b0!important; background:#e4f1ff!important; border:1px solid #cfe4fb; }
        @keyframes ri-rise { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        @keyframes ri-row-in { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
        @keyframes ri-stat-in { from { opacity:0; transform:translateY(14px) scale(.985); } to { opacity:1; transform:translateY(0) scale(1); } }
        #editResourceModal .modal-dialog { transform: scale(.96); transition: transform 250ms ease, opacity 250ms ease; }
        #editResourceModal.show .modal-dialog { transform: scale(1); }
        #resourceTable .btn { transition: transform 200ms ease, box-shadow 200ms ease; }
        #resourceTable .btn:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(42, 53, 71, .16); }
        #resourceTable .resource-row-removing { opacity: 0; transform: translateX(16px); transition: opacity 250ms ease, transform 250ms ease; }
        @media (prefers-reduced-motion: reduce) {
            .resource-index-page *, .resource-index-page *::before, .resource-index-page *::after { animation-duration:.01ms!important; animation-iteration-count:1!important; scroll-behavior:auto!important; transition-duration:.01ms!important; }
        }
        @media (max-width:767.98px) {
            .resource-index-page .card-body { padding:1rem; }
            .resource-index-page .page-heading { font-size:1.6rem; }
            .resource-index-page #resourceTable { min-width:760px; }
        }
    </style>

    <div class="resource-page-banner page-intro"><h1 class="page-heading">Existing resources</h1><p>Search, filter, and manage your resource inventory.</p></div>

    {{-- ===================== SUMMARY STAT CARDS ===================== --}}
    <div class="row g-3 mb-4 resource-stat-grid">
        <div class="col-md-3">
            <div class="card summary-card resource-stat-card stat-total h-100">
                <div class="resource-stat-top"><span class="resource-stat-icon"><i class="ti ti-box" aria-hidden="true"></i></span><div class="resource-stat-label">Total Resources</div></div>
                <div class="resource-stat-value" id="statTotal">0</div>
                <p class="resource-stat-description">Items in your inventory</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card resource-stat-card stat-active h-100">
                <div class="resource-stat-top"><span class="resource-stat-icon"><i class="ti ti-circle-check" aria-hidden="true"></i></span><div class="resource-stat-label">Active</div></div>
                <div class="resource-stat-value" id="statActive">0</div>
                <p class="resource-stat-description">Ready to use</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card resource-stat-card stat-maintenance h-100">
                <div class="resource-stat-top"><span class="resource-stat-icon"><i class="ti ti-tool" aria-hidden="true"></i></span><div class="resource-stat-label">Under Maintenance</div></div>
                <div class="resource-stat-value" id="statMaintenance">0</div>
                <p class="resource-stat-description">Currently being serviced</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card resource-stat-card stat-pending h-100">
                <div class="resource-stat-top"><span class="resource-stat-icon"><i class="ti ti-trash" aria-hidden="true"></i></span><div class="resource-stat-label">Pending Deletion</div></div>
                <div class="resource-stat-value" id="statPending">0</div>
                <p class="resource-stat-description">Awaiting review</p>
=======
<div class="container-fluid mt-4 rm-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 rm-header">
        <div>
            <h1 class="rm-title mb-1">Existing resources</h1>
            <p class="rm-subtitle mb-0">Search, filter, and manage the resource inventory.</p>
        </div>
        @if ($canManageResources)
            <a href="{{ route('resources.create') }}" class="btn btn-primary rm-create"><i class="ti ti-plus me-2"></i>Create resource</a>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="rm-stat p-3 p-md-4" style="--stat-color:#1769c2;--stat-icon-bg:#e8f0fa">
                <div class="d-flex justify-content-between align-items-center"><span class="rm-stat-icon"><i class="ti ti-packages"></i></span></div>
                <div class="rm-stat-label mt-3">Total resources</div>
                <div class="rm-stat-number" id="statTotal">0</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="rm-stat p-3 p-md-4" style="--stat-color:#16a77a;--stat-icon-bg:#e7f6f0">
                <div class="d-flex justify-content-between align-items-center"><span class="rm-stat-icon"><i class="ti ti-circle-check"></i></span></div>
                <div class="rm-stat-label mt-3">Active</div>
                <div class="rm-stat-number" id="statActive">0</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="rm-stat p-3 p-md-4" style="--stat-color:#d97706;--stat-icon-bg:#fff4e5">
                <div class="d-flex justify-content-between align-items-center"><span class="rm-stat-icon"><i class="ti ti-tool"></i></span></div>
                <div class="rm-stat-label mt-3">Under maintenance</div>
                <div class="rm-stat-number" id="statMaintenance">0</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="rm-stat p-3 p-md-4" style="--stat-color:#dc3545;--stat-icon-bg:#fdecee">
                <div class="d-flex justify-content-between align-items-center"><span class="rm-stat-icon"><i class="ti ti-trash"></i></span></div>
                <div class="rm-stat-label mt-3">Pending deletion</div>
                <div class="rm-stat-number" id="statPending">0</div>
>>>>>>> 83fa4dc2490e6d9583ee5be9e7771a3b3088792c
            </div>
        </div>
    </div>

    <div class="card border-0 rm-panel mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="searchInput" class="form-label">Search</label>
                    <div class="input-group rm-search-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" id="searchInput"
                            placeholder="Search by name, ID, serial, type..." autocomplete="off">
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label">Category</label>
                    <x-rm-select id="categoryFilter" placeholder="All categories"
                        :options="[['value' => '', 'label' => 'All categories']]" />
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label">Status</label>
                    <x-rm-select id="statusFilter" placeholder="All statuses" :options="[
                        ['value' => '', 'label' => 'All statuses'],
                        ['value' => 'active', 'label' => 'Active'],
                        ['value' => 'inactive', 'label' => 'Inactive'],
                        ['value' => 'under_maintenance', 'label' => 'Under Maintenance'],
                        ['value' => 'decommissioned', 'label' => 'Decommissioned'],
                        ['value' => 'pending_deletion', 'label' => 'Pending Deletion'],
                    ]" />
                </div>
                <div class="col-12 col-lg-2">
                    <label class="form-label d-none d-lg-block">&nbsp;</label>
                    <div class="d-flex gap-2 rm-filter-actions">
                        <button class="rm-clear-btn" id="clearFiltersBtn" type="button" disabled>Clear</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 rm-panel">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="resourceTable">
                    <thead>
                        <tr>
                            <th>Resource name</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Serial number</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="resourceTableBody">
                        <tr><td colspan="7" class="text-center text-muted py-4">Loading resources…</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
                <p class="small text-muted mb-0" id="resultCount" aria-live="polite"></p>
                <ul class="pagination rm-pagination mb-0" id="rmPagination"></ul>
            </div>
        </div>
    </div>

    @if ($canManageResources)
        <div class="modal fade" id="editResourceModal" tabindex="-1" aria-labelledby="editResourceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="rm-modal-heading">
                        <h5 class="modal-title" id="editResourceModalLabel">Edit Resource</h5>
                            <p class="rm-modal-sub mb-0">Update inventory details and save.</p>
                        </div>
                        <button type="button" class="rm-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="modal-body">
                        <div id="editResourceLoading" class="text-center text-muted py-4" role="status" hidden>Loading resource details…</div>
                        <form id="editResourceForm">
                            @csrf
                            @include('resources.partials.resource-fields')
                            <div class="mb-3" id="editFeaturesWrap" hidden>
                                <label class="form-label">Category / type features</label>
                                <div id="editFeaturesList" class="rm-linked-list"></div>
                            </div>
                            <div class="mb-0" id="editLinkedWrap">
                                <label class="form-label">Link resources</label>
                                <div id="editLinkedList" class="rm-linked-list"></div>
                                <p class="text-muted small mb-0 mt-2">Select related inventory items to keep with this resource.</p>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" form="editResourceForm" id="editResourceSaveButton">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.RM = {
        canManage: @json($canManageResources),
        listUrl: @json(route('resources.list')),
        showUrl: @json(route('resources.show', ['resource' => '__ID__'])),
        updateUrl: @json(route('resources.update', ['resource' => '__ID__'])),
        deleteUrl: @json(route('resources.destroy', ['resource' => '__ID__'])),
        approveUrl: @json(route('resources.approve-delete', ['resource' => '__ID__'])),
        rejectUrl: @json(route('resources.reject-delete', ['resource' => '__ID__'])),
        categoriesUrl: @json(route('resource-categories.index')),
        typesUrl: @json(route('resource-types.index')),
        locationsUrl: @json(route('locations.index')),
        lookups: @json($lookups ?? ['categories' => [], 'types' => [], 'locations' => []]),
    };
<<<<<<< HEAD
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
    const inactiveStyle = status === 'inactive' ? 'status-inactive' : '';
    return `<span class="badge rounded-pill status-badge ${inactiveStyle} ${cls} px-3 py-2">${label}</span>`;
}

function renderTable(resources) {
    const tbody = document.getElementById('resourceTableBody');
    tbody.innerHTML = '';

    if (resources.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5"><div class="fw-semibold text-dark mb-1">No resources found</div><div class="text-muted small">Try another search or clear the filters to see all resources.</div></td></tr>`;
    }

    resources.forEach(resource => {
        const category = resource.type?.category?.name ?? '-';
        const type = resource.type?.name ?? '-';
        const location = resource.location?.name ?? '-';
        const serial = resource.serial_number || '—';

        const row = document.createElement('tr');
        row.classList.add('resource-row-enter');
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
=======
>>>>>>> 83fa4dc2490e6d9583ee5be9e7771a3b3088792c
</script>
<script src="{{ asset('js/resource-select.js') }}?v=1"></script>
<script src="{{ asset('js/resource-index.js') }}?v=12"></script>
@endpush
