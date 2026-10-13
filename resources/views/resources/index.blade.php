@extends('layouts.app')

@section('title', 'Existing Resources')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/resource-management.css') }}?v=11">
@endpush

@section('content')
@php($canManageResources = auth()->user()?->hasPermission('resources.create') ?? false)
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
                        <button class="btn btn-primary flex-fill" id="applyFiltersBtn" type="button">Apply</button>
                        <button class="btn rm-clear-btn flex-fill" id="clearFiltersBtn" type="button">Clear</button>
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
                        <span class="rm-modal-icon" aria-hidden="true"><i class="ti ti-pencil"></i></span>
                        <div>
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
</script>
<script src="{{ asset('js/resource-select.js') }}?v=1"></script>
<script src="{{ asset('js/resource-index.js') }}?v=5"></script>
@endpush
