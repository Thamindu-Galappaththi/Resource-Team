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
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="resourceCategorySelect" class="form-label">Category</label>
                                        <select class="form-select category-select" id="resourceCategorySelect" name="category_id" required>
                                            <option value="" selected disabled>-- Select Category --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="resourceTypeSelect" class="form-label">Type</label>
                                        <select class="form-select" id="resourceTypeSelect" name="resource_type_id" required disabled>
                                            <option value="" selected disabled>-- Select Category First --</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="resourceLocationSelect" class="form-label">Location</label>
                                        <select class="form-select location-select" id="resourceLocationSelect" name="location_id" required>
                                            <option value="" selected disabled>-- Select Location --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="resourceOwnerSelect" class="form-label">Resource Owner</label>
                                        {{--
                                            NOTE: Resource Owner management (its own table/CRUD) is a
                                            separate task being built by someone else. For now this is
                                            just a hardcoded display name — nothing here is validated
                                            or persisted by the backend (see StoreResourceRequest /
                                            ResourceController, which don't reference resource_owner_id
                                            at all right now). Swap the option text/value below for
                                            whatever placeholder name you want, or wire this up to a
                                            real endpoint later the same way Location was done.
                                        --}}
                                        <select class="form-select" id="resourceOwnerSelect">
                                            <option value="Nadun" selected>Nadun</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="resourceNameModel" class="form-label">Resource Name / Model</label>
                                    <input type="text" class="form-control" id="resourceNameModel" name="name_model" required>
                                </div>

                                <div class="mb-3">
                                    <label for="serialNumber" class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" id="serialNumber" name="serial_number" required>
                                </div>

                                <div class="mb-3">
                                    <label for="resourceStatusSelect" class="form-label">Status</label>
                                    {{-- Fixed, small option set — not database-driven like
                                         Location/Resource Owner, so the values are just
                                         hardcoded here. Keep this list in sync with the
                                         Resource::STATUSES constant on the backend. --}}
                                    <select class="form-select" id="resourceStatusSelect" name="status" required>
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="under_maintenance">Under Maintenance</option>
                                        <option value="decommissioned">Decommissioned</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary">Create Resource</button>
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
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        ?? document.querySelector('input[name="_token"]')?.value;

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
    }

    function refreshResourceSelects() {
        document.querySelectorAll('.resource-select').forEach(select => {
            const current = select.value;
            select.querySelectorAll('option[data-dynamic]').forEach(o => o.remove());
            state.resources.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = `${r.name_model} (${r.serial_number})`;
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
            addSelectOption(typeSelect, '', '-- Select Category First --');
            typeSelect.disabled = true;
            return;
        }

        const filtered = state.types.filter(t => String(t.category_id) === String(categoryId));
        typeSelect.disabled = false;
        addSelectOption(typeSelect, '', filtered.length ? '-- Select Type --' : '-- No Types For This Category --');
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

            this.reset();
            featuresContainer.innerHTML = '';
            addFeatureRow();
            alert('Category created successfully.');
        } catch (err) {
            alert('Failed to create category: ' + err.message);
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

            this.reset();
            renderCategoryFeatures(null);
            alert('Resource type created successfully.');
        } catch (err) {
            alert('Failed to create resource type: ' + err.message);
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
        const serialNumber = document.getElementById('serialNumber').value.trim();
        const status = document.getElementById('resourceStatusSelect').value;
        // Resource Owner is just a hardcoded display name right now —
        // nothing is read from it or sent to the backend for it.

        try {
            // Adjust to your actual route, e.g. route('resources.store')
            const result = await postJSON('/resources', {
                category_id: categoryId,
                resource_type_id: typeId,
                location_id: locationId,
                name_model: nameModel,
                serial_number: serialNumber,
                status
            });

            state.resources.push({ id: result.id, name_model: nameModel, serial_number: serialNumber });
            refreshResourceSelects();

            this.reset();
            refreshTypeSelectForCategory(null);
            alert('Resource created successfully.');
        } catch (err) {
            alert('Failed to create resource: ' + err.message);
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
            alert('A resource cannot be linked to itself.');
            return;
        }

        try {
            // Adjust to your actual route, e.g. route('resource-links.store')
            await postJSON('/resource-links', {
                resource_id: resourceId,
                linked_resource_id: linkedResourceId
            });

            this.reset();
            alert('Linked resource created successfully.');
        } catch (err) {
            alert('Failed to create linked resource: ' + err.message);
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

        // Resource Owner has no backend endpoint at all right now —
        // it's just a hardcoded name in the dropdown on Tab 3.
    }

    loadInitialData();
});
</script>
@endpush