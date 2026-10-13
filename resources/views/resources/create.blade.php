@extends('layouts.app')

@section('title', 'Create Resources')

@section('content')
@push('styles')
<style>
    .resource-create-page { --rc-ink:#152238; --rc-muted:#65738a; --rc-line:#dfe5ee; --rc-blue:#315fe8; color:var(--rc-ink); }
    .resource-create-page .page-heading { font-weight:700; letter-spacing:-.035em; color:var(--rc-ink); }
    .resource-create-page .page-subtitle, .resource-create-page .step-hint { color:var(--rc-muted); }
    .resource-create-page .workflow-card { border:1px solid #dce3ed; border-radius:16px; background:#fff; box-shadow:0 10px 30px rgba(19,35,58,.09); overflow:hidden; }
    .resource-create-page .workflow-card > .card-body { background:linear-gradient(180deg,#fff 0%,#fbfcfe 100%); }
    .resource-create-page .workflow-tabs { display:flex; flex-wrap:nowrap; gap:.45rem; padding:.6rem 1rem; border:1px solid #d8e0eb; border-radius:17px; background:#edf1f7; overflow-x:auto; }
    .resource-create-page .workflow-tabs .nav-link { display:flex; align-items:center; gap:.65rem; border:1px solid transparent; border-radius:13px; padding:1rem 1.15rem; color:#4e5c71; white-space:nowrap; transition:background .18s ease,color .18s ease,box-shadow .18s ease; }
    .resource-create-page .workflow-tabs .nav-link:hover { background:#fff; color:var(--rc-ink); }
    .resource-create-page .workflow-tabs .nav-link.active { background:#fff; border-color:#e4e9f1; color:#204bc9; box-shadow:0 4px 12px rgba(19,35,58,.1); font-weight:650; }
    .resource-create-page .workflow-content { padding:clamp(1rem,3vw,2rem); }
    .resource-create-page .step-panel { max-width:1000px; margin:0 auto; padding:clamp(1.25rem,3vw,2.25rem); background:#fff; border:1px solid #dfe5ee; border-radius:16px; box-shadow:0 9px 26px rgba(19,35,58,.075); }
    .resource-create-page .step-title { font-size:1.45rem; font-weight:650; letter-spacing:-.025em; }
    .resource-create-page .form-label { color:#202e43; font-weight:600; margin-bottom:.5rem; }
    .resource-create-page .form-control, .resource-create-page .form-select { min-height:46px; border-color:#cfd8e5; border-radius:9px; background-color:#fff; color:#18263a; box-shadow:0 1px 2px rgba(19,35,58,.035); }
    .resource-create-page .form-control::placeholder { color:#8591a3; }
    .resource-create-page .form-control:focus, .resource-create-page .form-select:focus { border-color:#5578e8; box-shadow:0 0 0 .2rem rgba(49,95,232,.15); }
    .resource-create-page .btn-primary { --bs-btn-bg:#315fe8; --bs-btn-border-color:#315fe8; --bs-btn-hover-bg:#244ecb; --bs-btn-hover-border-color:#244ecb; --bs-btn-active-bg:#1e43b1; --bs-btn-active-border-color:#1e43b1; border-radius:9px; padding:.65rem 1.15rem; font-weight:600; box-shadow:0 4px 10px rgba(49,95,232,.2); }
    .resource-create-page .feature-heading { border-top:1px solid var(--rc-line); padding-top:1.25rem; }
    .resource-create-page #featuresContainer .row { align-items:center; row-gap:.75rem; margin:0 0 .75rem; padding:1rem; border:1px solid #e0e6ef; border-radius:12px; background:#f7f9fc; }
    .resource-create-page .existing-card { border:1px solid #dce3ed; border-radius:16px; background:#fff; box-shadow:0 9px 26px rgba(19,35,58,.08); overflow:hidden; }
    .resource-create-page .existing-card .card-header { background:#edf1f7; color:var(--rc-ink); }
    @media (max-width:575.98px) { .resource-create-page .workflow-tabs { margin:0 -1rem; border-radius:0; } .resource-create-page .workflow-content { padding:1rem 0 0; } .resource-create-page .step-panel { padding:1.15rem; border-radius:12px; } }
</style>
@endpush

<div class="container-fluid mt-4 resource-create-page">
    <div class="row">
        <div class="col-12">
            <div class="resource-page-banner">
                <h1 class="page-heading">Create resources</h1>
                <p class="page-subtitle">Set up a category, define its types, then add and link your resources.</p>
            </div>
            <div class="card workflow-card">
                <div class="card-body">
                    {{-- ===================== NAV TABS ===================== --}}
                    <ul class="nav nav-tabs workflow-tabs" id="resourceTabs" role="tablist" aria-label="Resource creation steps">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="category-tab" data-bs-toggle="tab"
                                data-bs-target="#category-pane" type="button" role="tab">
                                <span>Category</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="type-tab" data-bs-toggle="tab"
                                data-bs-target="#type-pane" type="button" role="tab">
                                <span>Resource type</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="resource-tab" data-bs-toggle="tab"
                                data-bs-target="#resource-pane" type="button" role="tab">
                                <span>Resource</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="linked-tab" data-bs-toggle="tab"
                                data-bs-target="#linked-pane" type="button" role="tab">
                                <span>Link resources</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content workflow-content" id="resourceTabsContent">

                        {{-- ===================== TAB 1: RESOURCE CATEGORY ===================== --}}
                        <div class="tab-pane fade show active" id="category-pane" role="tabpanel">
                            <section class="step-panel">
                                <div class="mb-4"><h2 class="step-title mb-1">Create a resource category</h2><p class="step-hint mb-0">Group similar items together and add any details you want to track.</p></div>
                                <form id="categoryForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="categoryName" class="form-label">Category name</label>
                                    <input type="text" class="form-control" id="categoryName" name="category_name" required>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between align-items-center mb-3 feature-heading">
                                    <div><h3 class="h6 fw-semibold mb-1">Additional features <span class="text-muted fw-normal">(optional)</span></h3><p class="step-hint small mb-0">Add fields such as brand, capacity, or purchase date.</p></div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addFeatureBtn">
                                        <i class="bi bi-plus-lg"></i> Add Feature
                                    </button>
                                </div>

                                <div id="featuresContainer">
                                    {{-- feature rows injected here by JS --}}
                                </div>

                                <div class="mt-4"><button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Create category</button></div>
                                </form>
                            </section>
                        </div>

                        {{-- ===================== TAB 2: RESOURCE TYPE ===================== --}}
                        <div class="tab-pane fade" id="type-pane" role="tabpanel">
                            <section class="step-panel"><div class="mb-4"><h2 class="step-title mb-1">Add a resource type</h2><p class="step-hint mb-0">Choose a category and give this type a clear name.</p></div><form id="typeForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="typeCategorySelect" class="form-label">Category</label>
                                    <select class="form-select category-select" id="typeCategorySelect" name="category_id" required>
                                        <option value="" selected disabled>-- Select Category --</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="resourceTypeName" class="form-label">Type name</label>
                                    <input type="text" class="form-control" id="resourceTypeName" name="resource_type" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Additional Features</label>
                                    <div id="typeAdditionalFeatures" class="border rounded p-3 bg-light text-muted small" aria-live="polite">
                                        Select a category above to view its additional features.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Create Resource Type</button>
                            </form></section>
                        </div>

                        {{-- ===================== TAB 3: RESOURCE ===================== --}}
                        <div class="tab-pane fade" id="resource-pane" role="tabpanel">
                            <section class="step-panel"><div class="mb-4"><h2 class="step-title mb-1">Add a resource</h2><p class="step-hint mb-0">Enter the key details so it can be found and managed later.</p></div><form id="resourceForm">
                                @csrf
                                @include('resources.partials.resource-fields')

                                <button type="submit" class="btn btn-primary" id="saveResourceButton">Create Resource</button>
                            </form></section>
                        </div>

                        {{-- ===================== TAB 4: LINKED RESOURCE ===================== --}}
                        <div class="tab-pane fade" id="linked-pane" role="tabpanel">
                            <section class="step-panel"><div class="mb-4"><h2 class="step-title mb-1">Link resources</h2><p class="step-hint mb-0">Connect related items to make their relationship easy to find.</p></div><form id="linkedForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="primaryResourceSelect" class="form-label">Main resource</label>
                                    <select class="form-select resource-select" id="primaryResourceSelect" name="resource_id" required>
                                        <option value="" selected disabled>-- Select Resource --</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="linkedResourceSelect" class="form-label">Related resource</label>
                                    <select class="form-select resource-select" id="linkedResourceSelect" name="linked_resource_id" required>
                                        <option value="" selected disabled>-- Select Resource To Link --</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary">Create Linked Resource</button>
                            </form></section>
                        </div>

                    </div>
                </div>
            </div>
            <div class="card existing-card mt-4" id="existingCategoriesCard">
                <div class="card-header border-0 px-4 py-3"><h2 class="h6 mb-0"><i class="ti ti-list me-2" aria-hidden="true"></i>Existing categories</h2></div>
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
        row.className = 'row feature-row';
        row.innerHTML = `
            <div class="col-lg-5 col-md-6">
                <label class="form-label" for="featureName${idx}">Feature name</label>
                <input type="text" class="form-control" id="featureName${idx}" placeholder="e.g., Brand"
                    name="features[${idx}][name]">
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="form-check form-switch mt-4">
                    <input class="form-check-input feature-toggle" type="checkbox"
                        name="features[${idx}][enabled]" id="featureToggle${idx}">
                    <label class="form-check-label" for="featureToggle${idx}">Enabled</label>
                </div>
            </div>
            <div class="col-lg-3 col-md-10 col-9">
                <label class="form-label" for="featureOptions${idx}">Options</label>
                <input type="text" class="form-control feature-options" id="featureOptions${idx}" placeholder="e.g., Dell, Lenovo"
                    name="features[${idx}][options]" disabled>
            </div>
            <div class="col-lg-1 col-md-2 col-3 text-end">
                <button type="button" class="btn btn-outline-danger remove-feature-btn mt-4" aria-label="Remove feature">&times;</button>
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
