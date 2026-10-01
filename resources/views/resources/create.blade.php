@extends('layouts.app')

@section('title', 'Create Resources')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4">Create Resources</h4>

                    {{-- ===================== NAV TABS ===================== --}}
                    <ul class="nav nav-tabs" id="resourceTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="category-tab" data-bs-toggle="tab"
                                data-bs-target="#category-pane" type="button" role="tab">
                                Resource Category
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="type-tab" data-bs-toggle="tab"
                                data-bs-target="#type-pane" type="button" role="tab">
                                Resource Type
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="resource-tab" data-bs-toggle="tab"
                                data-bs-target="#resource-pane" type="button" role="tab">
                                Resource
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="linked-tab" data-bs-toggle="tab"
                                data-bs-target="#linked-pane" type="button" role="tab">
                                Linked Resource
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content border border-top-0 p-4 rounded-bottom" id="resourceTabsContent">

                        {{-- ===================== TAB 1: RESOURCE CATEGORY ===================== --}}
                        <div class="tab-pane fade show active" id="category-pane" role="tabpanel">
                            <div class="card category-section-card mb-4" style="background:#fff; border:1px solid #d9e2ef; border-radius:12px; box-shadow:0 4px 16px rgba(42,53,71,.12); overflow:hidden;">
                                <div class="card-header bg-white border-bottom-0 px-4 pt-4"><h4 class="mb-0">Create Resource Category</h4></div>
                                <div class="card-body px-4 pb-4">
                            <form id="categoryForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="categoryName" class="form-label">Category Name</label>
                                    <input type="text" class="form-control" id="categoryName" name="category_name" required>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Additional Features</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addFeatureBtn">
                                        <i class="bi bi-plus-lg"></i> Add Feature
                                    </button>
                                </div>

                                <div id="featuresContainer">
                                    {{-- feature rows injected here by JS --}}
                                </div>

                                <button type="submit" class="btn btn-primary mt-3">Create Category</button>
                            </form>
                                </div>
                            </div>
                        </div>

                        {{-- ===================== TAB 2: RESOURCE TYPE ===================== --}}
                        <div class="tab-pane fade" id="type-pane" role="tabpanel">
                            <form id="typeForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="typeCategorySelect" class="form-label">Select Category</label>
                                    <select class="form-select category-select" id="typeCategorySelect" name="category_id" required>
                                        <option value="" selected disabled>-- Select Category --</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="resourceTypeName" class="form-label">Resource Type</label>
                                    <input type="text" class="form-control" id="resourceTypeName" name="resource_type" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Additional Features</label>
                                    <div id="typeAdditionalFeatures" class="border rounded p-3 bg-light text-muted small">
                                        Select a category above to view its additional features.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Create Resource Type</button>
                            </form>
                        </div>

                        {{-- ===================== TAB 3: RESOURCE ===================== --}}
                        <div class="tab-pane fade" id="resource-pane" role="tabpanel">
                            <form id="resourceForm">
                                @csrf
                                @include('resources.partials.resource-fields')

                                <button type="submit" class="btn btn-primary" id="saveResourceButton">Create Resource</button>
                            </form>
                        </div>

                        {{-- ===================== TAB 4: LINKED RESOURCE ===================== --}}
                        <div class="tab-pane fade" id="linked-pane" role="tabpanel">
                            <form id="linkedForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="primaryResourceSelect" class="form-label">Resource</label>
                                    <select class="form-select resource-select" id="primaryResourceSelect" name="resource_id" required>
                                        <option value="" selected disabled>-- Select Resource --</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="linkedResourceSelect" class="form-label">Link To Resource</label>
                                    <select class="form-select resource-select" id="linkedResourceSelect" name="linked_resource_id" required>
                                        <option value="" selected disabled>-- Select Resource To Link --</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary">Create Linked Resource</button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm overflow-hidden mt-4" id="existingCategoriesCard">
                <div class="card-header border-0 px-4 py-3" style="background:#d4e2ff; color:#164f73;"><h4 class="h6 mb-0"><i class="ti ti-info-circle me-2" aria-hidden="true"></i>Existing Categories</h4></div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead><tr><th>Category</th><th>Features</th><th class="text-end">Actions</th></tr></thead>
                            <tbody id="existingCategoriesBody"><tr><td colspan="3" class="text-muted">Loading categories…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        ?? document.querySelector('input[name="_token"]')?.value;
    const editResourceId = new URLSearchParams(window.location.search).get('edit');

    // ---------------------------------------------------------------
    // In-memory state used to populate dropdowns across tabs without
    // a full page reload. Swap the fetch URLs below for your actual
    // Laravel route names / controller endpoints.
    // ---------------------------------------------------------------
    const state = {
        categories: [],   // { id, name, features: [...] }
        types: [],        // { id, category_id, name }
        resources: [],    // { id, name_model, serial_number }
        locations: []     // { id, name }
    };

    const nextTab = { categoryForm: 'type-tab', typeForm: 'resource-tab', resourceForm: 'linked-tab' };
    const existingCategoriesCard = document.getElementById('existingCategoriesCard');
    document.getElementById('resourceTabs').addEventListener('shown.bs.tab', event => {
        existingCategoriesCard.hidden = event.target.id !== 'category-tab';
    });

    async function success(message, formId) {
        const result = await Swal.fire({ icon: 'success', title: 'Success', text: message, confirmButtonText: 'OK' });
        if (result.isConfirmed && nextTab[formId]) bootstrap.Tab.getOrCreateInstance(document.getElementById(nextTab[formId])).show();
    }

    async function postJSON(url, data) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Request failed');
        }
        return res.json();
    }

    function addSelectOption(select, value, label) {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = label;
        select.appendChild(opt);
    }

    function refreshCategorySelects() {
        document.querySelectorAll('.category-select').forEach(select => {
            const current = select.value;
            select.querySelectorAll('option[data-dynamic]').forEach(o => o.remove());
            state.categories.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                opt.setAttribute('data-dynamic', '1');
                select.appendChild(opt);
            });
            if (current) select.value = current;
        });
        renderExistingCategories();
    }

    function renderExistingCategories() {
        const body = document.getElementById('existingCategoriesBody');
        if (!body) return;
        body.replaceChildren();
        if (!state.categories.length) {
            body.innerHTML = '<tr><td colspan="3" class="text-muted">No active categories found.</td></tr>';
            return;
        }
        state.categories.forEach(category => {
            const row = document.createElement('tr');
            const name = document.createElement('td'); name.textContent = category.name;
            const features = document.createElement('td'); features.textContent = (category.features || []).map(feature => feature.name).join(', ') || '—';
            const actions = document.createElement('td'); actions.className = 'text-end';
            actions.innerHTML = `<button type="button" class="btn btn-sm btn-outline-primary me-2" data-edit-id="${category.id}">Edit</button><button type="button" class="btn btn-sm btn-outline-danger" data-delete-id="${category.id}">Delete</button>`;
            row.append(name, features, actions); body.appendChild(row);
        });
    }

    document.getElementById('existingCategoriesBody').addEventListener('click', async event => {
        const editButton = event.target.closest('[data-edit-id]');
        const deleteButton = event.target.closest('[data-delete-id]');
        const id = editButton?.dataset.editId || deleteButton?.dataset.deleteId;
        if (!id) return;
        const category = state.categories.find(item => String(item.id) === String(id));
        if (!category) return;
        if (editButton) {
            const result = await Swal.fire({ title: 'Edit Category', input: 'text', inputValue: category.name, showCancelButton: true, inputValidator: value => !value?.trim() ? 'Enter a category name.' : undefined });
            if (!result.isConfirmed) return;
            try {
                const response = await fetch(`/resource-categories/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({ category_name: result.value.trim() }) });
                const updated = await response.json(); if (!response.ok) throw new Error(updated.message || 'Update failed');
                category.name = updated.name; refreshCategorySelects();
                await Swal.fire({ icon: 'success', title: 'Updated', text: 'Category updated successfully.' });
            } catch (error) { await Swal.fire({ icon: 'error', title: 'Update failed', text: error.message }); }
            return;
        }
        try {
            const checkResponse = await fetch(`/resource-categories/${id}/delete-check`, { headers: { 'Accept': 'application/json' } });
            const check = await checkResponse.json(); if (!checkResponse.ok) throw new Error(check.message || 'Could not check linked resources');
            const warning = check.linked_resources ? ` This category has ${check.linked_resources} linked resource(s).` : '';
            const confirmation = await Swal.fire({ icon: 'warning', title: 'Delete category?', text: `“${category.name}” will be soft deleted and hidden from active lists.${warning}`, showCancelButton: true, confirmButtonText: 'Soft delete', confirmButtonColor: '#dc3545' });
            if (!confirmation.isConfirmed) return;
            const response = await fetch(`/resource-categories/${id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
            const result = await response.json(); if (!response.ok) throw new Error(result.message || 'Delete failed');
            state.categories = state.categories.filter(item => String(item.id) !== String(id)); refreshCategorySelects();
            await Swal.fire({ icon: 'success', title: 'Deleted', text: 'Category soft deleted.' });
        } catch (error) { await Swal.fire({ icon: 'error', title: 'Delete failed', text: error.message }); }
    });

    function refreshResourceSelects() {
        document.querySelectorAll('.resource-select').forEach(select => {
            const current = select.value;
            select.querySelectorAll('option[data-dynamic]').forEach(o => o.remove());
            state.resources.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = `${r.name_model}${r.serial_number ? ` (${r.serial_number})` : ''}`;
                opt.setAttribute('data-dynamic', '1');
                select.appendChild(opt);
            });
            if (current) select.value = current;
        });
    }

    function refreshLocationSelects() {
        document.querySelectorAll('.location-select').forEach(select => {
            const current = select.value;
            select.querySelectorAll('option[data-dynamic]').forEach(o => o.remove());
            state.locations.forEach(loc => {
                const opt = document.createElement('option');
                opt.value = loc.id;
                opt.textContent = loc.name;
                opt.setAttribute('data-dynamic', '1');
                select.appendChild(opt);
            });
            if (current) select.value = current;
        });
    }

    // Renders the selected category's "Additional Features" (created back
    // on Tab 1) into the read-only panel that replaced the old
    // Description field on Tab 2. Purely informational — nothing here
    // is submitted with the resource type form.
    function renderCategoryFeatures(categoryId) {
        const panel = document.getElementById('typeAdditionalFeatures');
        const category = state.categories.find(c => String(c.id) === String(categoryId));

        if (!category) {
            panel.classList.add('text-muted');
            panel.innerHTML = 'Select a category above to view its additional features.';
            return;
        }

        const features = category.features || [];

        if (features.length === 0) {
            panel.classList.add('text-muted');
            panel.innerHTML = 'This category has no additional features.';
            return;
        }

        panel.classList.remove('text-muted');
        panel.innerHTML = '<ul class="list-unstyled mb-0">' +
            features.map(f => {
                const name = f.name || '(unnamed feature)';
                const optionsPart = f.enabled && f.options
                    ? ` — <span class="text-muted">options: ${f.options}</span>`
                    : (f.enabled ? ' — <span class="text-muted">enabled, no options set</span>' : ' — <span class="text-muted">disabled</span>');
                return `<li><strong>${name}</strong>${optionsPart}</li>`;
            }).join('') +
            '</ul>';
    }

    function refreshTypeSelectForCategory(categoryId) {
        const typeSelect = document.getElementById('resourceTypeSelect');
        typeSelect.querySelectorAll('option').forEach(o => o.remove());

        if (!categoryId) {
            addSelectOption(typeSelect, '', 'Select a resource type (e.g., Laptop)');
            typeSelect.options[0].disabled = true;
            typeSelect.options[0].selected = true;
            typeSelect.disabled = true;
            return;
        }

        const filtered = state.types.filter(t => String(t.category_id) === String(categoryId));
        typeSelect.disabled = false;
        addSelectOption(typeSelect, '', filtered.length ? 'Select a resource type (e.g., Laptop)' : 'No resource types available for this category');
        typeSelect.options[0].disabled = true;
        typeSelect.options[0].selected = true;
        filtered.forEach(t => addSelectOption(typeSelect, t.id, t.name));
    }

    // -----------------------------------------------------------
    // TAB 1: Resource Category + dynamic "Additional Features"
    // -----------------------------------------------------------
    const featuresContainer = document.getElementById('featuresContainer');
    let featureIndex = 0;

    function addFeatureRow() {
        const idx = featureIndex++;
        const row = document.createElement('div');
        row.className = 'row align-items-center mb-2 feature-row';
        row.innerHTML = `
            <div class="col-md-5">
                <input type="text" class="form-control" placeholder="Feature Name"
                    name="features[${idx}][name]">
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch">
                    <input class="form-check-input feature-toggle" type="checkbox"
                        name="features[${idx}][enabled]" id="featureToggle${idx}">
                    <label class="form-check-label" for="featureToggle${idx}">Enabled</label>
                </div>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control feature-options" placeholder="Options"
                    name="features[${idx}][options]" disabled>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger remove-feature-btn">&times;</button>
            </div>
        `;
        featuresContainer.appendChild(row);

        const toggle = row.querySelector('.feature-toggle');
        const optionsInput = row.querySelector('.feature-options');
        toggle.addEventListener('change', function () {
            optionsInput.disabled = !this.checked;
            if (!this.checked) optionsInput.value = '';
        });

        row.querySelector('.remove-feature-btn').addEventListener('click', function () {
            row.remove();
        });
    }

    document.getElementById('addFeatureBtn').addEventListener('click', addFeatureRow);
    addFeatureRow(); // start with one row

    document.getElementById('categoryForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const categoryName = document.getElementById('categoryName').value.trim();

        const features = Array.from(document.querySelectorAll('.feature-row')).map(row => ({
            name: row.querySelector('[name$="[name]"]').value,
            enabled: row.querySelector('.feature-toggle').checked,
            options: row.querySelector('.feature-options').value
        }));

        try {
            // Adjust to your actual route, e.g. route('resource-categories.store')
            const result = await postJSON('/resource-categories', {
                category_name: categoryName,
                features
            });

            // result.features comes back from the backend since the
            // controller returns $category->load('features') — keep
            // it on the category object so Tab 2 can display it later
            // without another request.
            const newCategory = { id: result.id, name: categoryName, features: result.features || [] };
            state.categories.push(newCategory);
            refreshCategorySelects();

            await success('Category created successfully.', 'categoryForm');
        } catch (err) {
            await Swal.fire({ icon: 'error', title: editResourceId ? 'Save failed' : 'Create failed', text: err.message });
        }
    });

    // -----------------------------------------------------------
    // TAB 2: Resource Type
    // -----------------------------------------------------------
    document.getElementById('typeCategorySelect').addEventListener('change', function () {
        renderCategoryFeatures(this.value);
    });

    document.getElementById('typeForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const categoryId = document.getElementById('typeCategorySelect').value;
        const typeName = document.getElementById('resourceTypeName').value.trim();

        try {
            // Adjust to your actual route, e.g. route('resource-types.store')
            // Description was removed from this tab, so it's no longer
            // sent — the backend field stays nullable, so this is safe.
            const result = await postJSON('/resource-types', {
                category_id: categoryId,
                resource_type: typeName
            });

            state.types.push({ id: result.id, category_id: categoryId, name: typeName });

            await success('Resource type created successfully.', 'typeForm');
        } catch (err) {
            await Swal.fire({ icon: 'error', title: 'Create failed', text: err.message });
        }
    });

    // -----------------------------------------------------------
    // TAB 3: Resource
    // -----------------------------------------------------------
    document.getElementById('resourceCategorySelect').addEventListener('change', function () {
        refreshTypeSelectForCategory(this.value);
    });

    document.getElementById('resourceForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const categoryId = document.getElementById('resourceCategorySelect').value;
        const typeId = document.getElementById('resourceTypeSelect').value;
        const locationId = document.getElementById('resourceLocationSelect').value;
        const nameModel = document.getElementById('resourceNameModel').value.trim();
        const serialNumber = document.getElementById('serialNumber').value.trim() || null;
        const status = document.getElementById('resourceStatusSelect').value;
        // Resource Owner is just a hardcoded display name right now —
        // nothing is read from it or sent to the backend for it.

        try {
            // Adjust to your actual route, e.g. route('resources.store')
            const resourcePayload = {
                category_id: categoryId,
                resource_type_id: typeId,
                location_id: locationId,
                name_model: nameModel,
                serial_number: serialNumber,
                status
            };
            const result = editResourceId
                ? await requestJSON(`/resources/${editResourceId}`, 'PUT', resourcePayload)
                : await postJSON('/resources', resourcePayload);

            if (editResourceId) {
                await Swal.fire({ icon: 'success', title: 'Resource updated', text: 'Resource changes were saved.' });
                window.location.assign('/resources');
                return;
            }
            state.resources.push({ id: result.id, name_model: nameModel, serial_number: serialNumber || '' });
            refreshResourceSelects();

            await success('Resource created successfully.', 'resourceForm');
        } catch (err) {
            await Swal.fire({ icon: 'error', title: 'Create failed', text: err.message });
        }
    });

    // -----------------------------------------------------------
    // TAB 4: Linked Resource
    // -----------------------------------------------------------
    document.getElementById('linkedForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const resourceId = document.getElementById('primaryResourceSelect').value;
        const linkedResourceId = document.getElementById('linkedResourceSelect').value;

        if (resourceId === linkedResourceId) {
            await Swal.fire({ icon: 'warning', title: 'Invalid link', text: 'A resource cannot be linked to itself.' });
            return;
        }

        try {
            await postJSON(@json(route('resource-links.store')), {
                resource_id: resourceId,
                linked_resource_id: linkedResourceId
            });

            await Swal.fire({ icon: 'success', title: 'Success', text: 'Linked resource created successfully.', confirmButtonText: 'OK' });
        } catch (err) {
            await Swal.fire({ icon: 'error', title: 'Create failed', text: err.message });
        }
    });

    // -----------------------------------------------------------
    // Initial load.
    //
    // Earlier this only worked off window.__initialCategories /
    // __initialTypes / etc, which the CONTROLLER had to inject via
    // @@json(...) for this to have any data on page load. If that
    // injection was ever missed (or the controller doesn't pass those
    // variables), everything created in a PREVIOUS page load/session
    // would silently not show up in the dropdowns — even though it's
    // sitting fine in the database — because state.types/.categories/
    // etc simply started empty again on every fresh load.
    //
    // Fetching directly from the GET endpoints here instead means the
    // dropdowns are always populated from whatever is actually in the
    // database, regardless of what the controller does or doesn't
    // inject. This is what fixes "a type only shows up in the Tab 3
    // dropdown the same session it was created, not after a reload".
    // -----------------------------------------------------------
    async function fetchJSON(url) {
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) {
            throw new Error(`Failed to load ${url}`);
        }
        return res.json();
    }

    async function requestJSON(url, method, data) {
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(data)
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Request failed');
        return payload;
    }

    async function loadInitialData() {
        // Each of these runs independently — if one endpoint 404s or
        // errors, the others still load fine and console.error tells
        // you exactly which one failed, instead of one bad route
        // silently wiping out every dropdown on the page (which is
        // what Promise.all would do here — it rejects everything the
        // moment any single promise in the list rejects).

        try {
            // eager-loads "features" server-side
            const categories = await fetchJSON('/resource-categories');
            state.categories = categories;
            refreshCategorySelects();
        } catch (err) {
            console.error('Failed to load /resource-categories:', err);
        }

        try {
            const types = await fetchJSON('/resource-types');
            // The backend column is "resource_category_id" (matches
            // the DB/Eloquent attribute name), but the rest of this
            // file's filtering logic (refreshTypeSelectForCategory)
            // reads "category_id" — map it here once, in one place,
            // instead of changing every reference throughout the file.
            state.types = types.map(t => ({
                id: t.id,
                category_id: t.resource_category_id,
                name: t.name
            }));
        } catch (err) {
            console.error('Failed to load /resource-types:', err);
        }

        try {
            const resources = await fetchJSON('/resource-list');
            state.resources = resources;
            refreshResourceSelects();
        } catch (err) {
            console.error('Failed to load /resource-list:', err);
        }

        try {
            const locations = await fetchJSON('/locations');
            state.locations = locations;
            refreshLocationSelects();
        } catch (err) {
            console.error('Failed to load /locations:', err);
        }

        if (editResourceId) {
            try {
                const resource = await fetchJSON(`/resources/${editResourceId}`);
                document.getElementById('resourceCategorySelect').value = resource.type?.resource_category_id || resource.type?.category?.id || '';
                refreshTypeSelectForCategory(document.getElementById('resourceCategorySelect').value);
                document.getElementById('resourceTypeSelect').value = resource.resource_type_id;
                document.getElementById('resourceLocationSelect').value = resource.location_id;
                document.getElementById('resourceNameModel').value = resource.name_model || '';
                document.getElementById('serialNumber').value = resource.serial_number || '';
                document.getElementById('resourceStatusSelect').value = resource.status || '';
                document.getElementById('saveResourceButton').textContent = 'Save Changes';
                bootstrap.Tab.getOrCreateInstance(document.getElementById('resource-tab')).show();
            } catch (error) {
                await Swal.fire({ icon: 'error', title: 'Could not load resource', text: error.message });
            }
        }

        // Resource Owner has no backend endpoint at all right now —
        // it's just a hardcoded name in the dropdown on Tab 3.
    }

    loadInitialData();
});
</script>
@endpush
