@extends('layouts.app')

@section('title', 'Existing Resources')

@section('content')
<div class="container-fluid mt-4">

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
                    <button class="btn btn-primary w-100" id="applyFiltersBtn">Apply Filters</button>
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
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="resourceTableBody">
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mt-2 mb-0" id="resultCount"></p>
        </div>
    </div>

</div>

<script>
let allResources = [];   // everything loaded from the server
let categoriesSeen = new Set();

document.addEventListener('DOMContentLoaded', () => {
    loadResources();

    document.getElementById('applyFiltersBtn').addEventListener('click', applyFilters);
    document.getElementById('searchInput').addEventListener('input', applyFilters);
});

function loadResources() {
    fetch('/resource-list')
        .then(res => res.json())
        .then(data => {
            allResources = data;
            populateCategoryFilter(data);
            updateStatCards(data);
            applyFilters();
        })
        .catch(err => console.error('Failed to load resources', err));
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
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No resources found.</td></tr>`;
    }

    resources.forEach(resource => {
        const category = resource.type?.category?.name ?? '-';
        const type = resource.type?.name ?? '-';
        const location = resource.location?.name ?? '-';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="fw-semibold">${resource.name_model}</div>
                <div class="text-muted small">ID: ${resource.id}</div>
            </td>
            <td>${category}</td>
            <td>${type}</td>
            <td>${location}</td>
            <td>${statusBadge(resource.status)}</td>
            <td class="text-end">${actionButtons(resource)}</td>
        `;
        tbody.appendChild(row);
    });

    document.getElementById('resultCount').textContent =
        `Showing ${resources.length} of ${allResources.length} results`;
}

function actionButtons(resource) {
    if (resource.status === 'pending_deletion') {
        return `
            <button class="btn btn-sm btn-success me-1" onclick="approveDelete(${resource.id})">Approve</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="rejectDelete(${resource.id})">Reject</button>
        `;
    }
    if (resource.status === 'deleted') {
        return `<span class="text-muted">—</span>`;
    }
    return `<button class="btn btn-sm btn-outline-danger" onclick="requestDelete(${resource.id})">Delete</button>`;
}

function requestDelete(id) {
    if (!confirm('Send this resource for deletion approval?')) return;
    postAction(`/resources/${id}/request-delete`);
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