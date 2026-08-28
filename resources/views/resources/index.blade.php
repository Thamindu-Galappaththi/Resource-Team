@extends('layouts.app')

@section('title', 'Existing Resources')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">Existing Resources</h4>
                    <p class="text-muted">All registered resources and their current status.</p>

                    <table class="table table-hover align-middle" id="resourceTable">
                        <thead>
                            <tr>
                                <th>Name / Model</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="resourceTableBody">
                            {{-- Rows are injected by JS below --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadResources();
});

function loadResources() {
    fetch('/resource-list')
        .then(res => res.json())
        .then(renderTable)
        .catch(err => console.error('Failed to load resources', err));
}

function statusBadge(status) {
    const map = {
        active: 'bg-success',
        inactive: 'bg-secondary',
        under_maintenance: 'bg-warning text-dark',
        decommissioned: 'bg-dark',
        pending_deletion: 'bg-danger',
        deleted: 'bg-secondary',
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
    return `<span class="badge ${cls}">${label}</span>`;
}

function renderTable(resources) {
    const tbody = document.getElementById('resourceTableBody');
    tbody.innerHTML = '';

    resources.forEach(resource => {
        const category = resource.type?.category?.name ?? '-';
        const type = resource.type?.name ?? '-';
        const location = resource.location?.name ?? '-';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${resource.name_model}</td>
            <td>${category}</td>
            <td>${type}</td>
            <td>${location}</td>
            <td>${statusBadge(resource.status)}</td>
            <td class="text-end">${actionButtons(resource)}</td>
        `;
        tbody.appendChild(row);
    });
}

function actionButtons(resource) {
    // Once a resource is already pending/deleted, don't show a
    // "delete" button again — show approve/reject or nothing.
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