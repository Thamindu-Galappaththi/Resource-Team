document.addEventListener('DOMContentLoaded', () => {
    const config = window.RCCreate || {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const editResourceId = new URLSearchParams(window.location.search).get('edit');
    const swalFire = (options) => window.RMSwal.fire(options);
    let editingCategoryId = null;

    const state = { categories: [], types: [], resources: [], locations: [] };
    const nextTab = { categoryForm: 'type-tab', typeForm: 'resource-tab', resourceForm: 'linked-tab' };
    const existingCategoriesCard = document.getElementById('existingCategoriesCard');
    const featuresContainer = document.getElementById('featuresContainer');
    const editFeaturesContainer = document.getElementById('editFeaturesContainer');
    let featureIndex = 0;

    document.getElementById('resourceTabs')?.addEventListener('shown.bs.tab', (event) => {
        if (existingCategoriesCard) existingCategoriesCard.hidden = event.target.id !== 'category-tab';
    });

    document.getElementById('addFeatureBtn')?.addEventListener('click', () => addFeatureRow(featuresContainer));
    document.getElementById('editAddFeatureBtn')?.addEventListener('click', () => {
        addFeatureRow(editFeaturesContainer);
        toggleEditFeaturesEmpty();
    });
    document.getElementById('categoryForm')?.addEventListener('submit', submitCategory);
    document.getElementById('typeForm')?.addEventListener('submit', submitType);
    document.getElementById('resourceForm')?.addEventListener('submit', submitResource);
    document.getElementById('linkedForm')?.addEventListener('submit', submitLink);
    document.getElementById('typeCategorySelect')?.addEventListener('change', (event) => renderCategoryFeatures(event.target.value));
    document.getElementById('resourceCategorySelect')?.addEventListener('change', (event) => refreshTypeSelectForCategory(event.target.value));
    document.getElementById('existingCategoriesBody')?.addEventListener('click', onCategoryTableClick);

    ['categoryName', 'resourceTypeName', 'resourceNameModel', 'serialNumber'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', () => clearFieldError(id));
    });

    loadInitialData();

    function addFeatureRow(container = featuresContainer, feature = {}) {
        if (!container) return;
        const idx = featureIndex += 1;
        const row = document.createElement('div');
        row.className = 'rm-feature feature-row';
        if (feature.id) row.dataset.featureId = feature.id;
        const enabled = Boolean(feature.enabled);
        row.innerHTML = `
            <div>
                <label class="form-label" for="featureName${idx}">Feature name</label>
                <input type="text" class="form-control" id="featureName${idx}" placeholder="e.g., Brand" value="${escapeHtml(feature.name || '')}">
            </div>
            <div class="rm-feature-enabled">
                <span class="form-label">Enabled</span>
                <div class="form-check form-switch">
                    <input class="form-check-input feature-toggle" type="checkbox" id="featureToggle${idx}" role="switch" aria-label="Enable feature"${enabled ? ' checked' : ''}>
                </div>
            </div>
            <div>
                <label class="form-label" for="featureOptions${idx}">Options</label>
                <input type="text" class="form-control feature-options" id="featureOptions${idx}" placeholder="e.g., Dell, Lenovo" value="${escapeHtml(feature.options || '')}" ${enabled ? '' : 'disabled'}>
            </div>
            <div class="rm-feature-remove">
                <span class="form-label d-none d-lg-block">&nbsp;</span>
                <button type="button" class="btn btn-outline-danger rm-remove-btn remove-feature-btn" aria-label="Remove feature"><i class="ti ti-x"></i></button>
            </div>
        `;
        container.appendChild(row);
        const toggle = row.querySelector('.feature-toggle');
        const optionsInput = row.querySelector('.feature-options');
        toggle.addEventListener('change', () => {
            optionsInput.disabled = !toggle.checked;
            if (!toggle.checked) optionsInput.value = '';
        });
        row.querySelector('.remove-feature-btn').addEventListener('click', () => {
            row.remove();
            toggleEditFeaturesEmpty();
        });
    }

    function collectFeatures(container) {
        if (!container) return [];
        return Array.from(container.querySelectorAll('.feature-row')).map((row) => ({
            id: row.dataset.featureId ? Number(row.dataset.featureId) : null,
            name: row.querySelector('input[id^="featureName"]').value.trim(),
            enabled: row.querySelector('.feature-toggle').checked,
            options: row.querySelector('.feature-options').value.trim() || null,
        })).filter((feature) => feature.name);
    }

    function toggleEditFeaturesEmpty() {
        const empty = document.getElementById('editFeaturesEmpty');
        if (empty) empty.hidden = Boolean(editFeaturesContainer?.querySelector('.feature-row'));
    }

    async function submitCategory(event) {
        event.preventDefault();
        const categoryName = document.getElementById('categoryName').value.trim();
        if (!categoryName) return showFieldError('categoryName', 'Enter a category name.');

        const features = collectFeatures(featuresContainer);

        try {
            const result = await postJSON(config.categoriesUrl, { category_name: categoryName, features });
            state.categories.push({ id: result.id, name: result.name || categoryName, features: result.features || [] });
            refreshCategorySelects();
            event.target.reset();
            featuresContainer.replaceChildren();
            await success('Category created successfully.', 'categoryForm');
        } catch (error) {
            showRequestErrors(error, { category_name: 'categoryName' });
            await swalFire({ icon: 'error', title: 'Could not create category', text: error.message });
        }
    }

    async function submitType(event) {
        event.preventDefault();
        const categoryId = document.getElementById('typeCategorySelect').value;
        const typeName = document.getElementById('resourceTypeName').value.trim();
        if (!categoryId) return showFieldError('typeCategorySelect', 'Select a category.');
        if (!typeName) return showFieldError('resourceTypeName', 'Enter a type name.');

        try {
            const result = await postJSON(config.typesUrl, { category_id: categoryId, resource_type: typeName });
            state.types.push({ id: result.id, category_id: categoryId, name: typeName });
            event.target.reset();
            renderCategoryFeatures('');
            await success('Resource type created successfully.', 'typeForm');
        } catch (error) {
            await swalFire({ icon: 'error', title: 'Could not create type', text: error.message });
        }
    }

    async function submitResource(event) {
        event.preventDefault();
        clearResourceErrors();
        const payload = {
            category_id: document.getElementById('resourceCategorySelect').value,
            resource_type_id: document.getElementById('resourceTypeSelect').value,
            location_id: document.getElementById('resourceLocationSelect').value,
            name_model: document.getElementById('resourceNameModel').value.trim(),
            serial_number: document.getElementById('serialNumber').value.trim() || null,
            resource_owner: document.getElementById('resourceOwner')?.value.trim() || null,
            status: document.getElementById('resourceStatusSelect').value,
        };

        try {
            const result = editResourceId
                ? await requestJSON(`${config.resourceUrl}/${editResourceId}`, 'PUT', payload)
                : await postJSON(config.resourceUrl, payload);

            if (editResourceId) {
                await swalFire({ icon: 'success', title: 'Resource updated', text: 'Changes were saved.' });
                window.location.assign(config.indexUrl);
                return;
            }

            state.resources.push({ id: result.id, name_model: payload.name_model, serial_number: payload.serial_number || '' });
            refreshResourceSelects();
            window.RMSelect.setValue('resourceCategorySelect', '', 'Select a category (e.g., IT Equipment)');
            refreshTypeSelectForCategory('');
            window.RMSelect.setValue('resourceLocationSelect', '', 'Select a location (e.g., Block A, Floor 2)');
            window.RMSelect.setValue('resourceStatusSelect', '', 'Select status (e.g., Available, Under Maintenance)');
            document.getElementById('resourceNameModel').value = '';
            document.getElementById('serialNumber').value = '';
            const ownerField = document.getElementById('resourceOwner');
            if (ownerField) ownerField.value = '';
            await success('Resource created successfully.', 'resourceForm');
        } catch (error) {
            showRequestErrors(error, {
                category_id: 'resourceCategorySelect',
                resource_type_id: 'resourceTypeSelect',
                location_id: 'resourceLocationSelect',
                name_model: 'resourceNameModel',
                serial_number: 'serialNumber',
                resource_owner: 'resourceOwner',
                status: 'resourceStatusSelect',
            });
            await swalFire({ icon: 'error', title: 'Could not save resource', text: error.message });
        }
    }

    async function submitLink(event) {
        event.preventDefault();
        const resourceId = document.getElementById('primaryResourceSelect').value;
        const linkedResourceId = document.getElementById('linkedResourceSelect').value;
        if (!resourceId || !linkedResourceId) {
            await swalFire({ icon: 'warning', title: 'Select both resources', text: 'Choose a main resource and a related resource.' });
            return;
        }
        if (resourceId === linkedResourceId) {
            await swalFire({ icon: 'warning', title: 'Invalid link', text: 'A resource cannot be linked to itself.' });
            return;
        }

        try {
            await postJSON(config.linksUrl, { resource_id: resourceId, linked_resource_id: linkedResourceId });
            event.target.reset();
            await swalFire({ icon: 'success', title: 'Resources linked', text: 'The relationship was saved.' });
        } catch (error) {
            await swalFire({ icon: 'error', title: 'Could not link resources', text: error.message });
        }
    }

    async function onCategoryTableClick(event) {
        const editButton = event.target.closest('[data-edit-id]');
        const deleteButton = event.target.closest('[data-delete-id]');
        const id = editButton?.dataset.editId || deleteButton?.dataset.deleteId;
        if (!id) return;
        const category = state.categories.find((item) => String(item.id) === String(id));
        if (!category) return;

        if (editButton) {
            editingCategoryId = id;
            const nameInput = document.getElementById('editCategoryName');
            nameInput.value = category.name;
            nameInput.classList.remove('is-invalid');
            editFeaturesContainer?.replaceChildren();
            (category.features || []).forEach((feature) => addFeatureRow(editFeaturesContainer, feature));
            toggleEditFeaturesEmpty();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editCategoryModal')).show();
            return;
        }

        try {
            const check = await fetchJSON(`${config.categoriesUrl}/${id}/delete-check`);
            const warning = check.linked_resources ? ` This category has ${check.linked_resources} linked resource(s).` : '';
            const confirmation = await swalFire({
                icon: 'warning',
                title: 'Delete category?',
                text: `“${category.name}” will be hidden from active lists.${warning}`,
                showCancelButton: true,
                confirmButtonText: 'Delete',
                customClass: { ...window.RMSwal.base.customClass, confirmButton: 'btn btn-danger rm-swal-btn' },
            });
            if (!confirmation.isConfirmed) return;
            await requestJSON(`${config.categoriesUrl}/${id}`, 'DELETE');
            state.categories = state.categories.filter((item) => String(item.id) !== String(id));
            refreshCategorySelects();
            await swalFire({ icon: 'success', title: 'Deleted', text: 'Category removed from the list.', timer: 2000, showConfirmButton: false });
        } catch (error) {
            await swalFire({ icon: 'error', title: 'Delete failed', text: error.message });
        }
    }

    document.getElementById('editCategoryForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const nameInput = document.getElementById('editCategoryName');
        const name = nameInput.value.trim();
        if (!name) {
            nameInput.classList.add('is-invalid');
            return;
        }
        const category = state.categories.find((item) => String(item.id) === String(editingCategoryId));
        if (!category) return;
        try {
            const updated = await requestJSON(`${config.categoriesUrl}/${editingCategoryId}`, 'PUT', {
                category_name: name,
                features: collectFeatures(editFeaturesContainer),
            });
            category.name = updated.name;
            category.features = updated.features || [];
            refreshCategorySelects();
            const selectedTypeCategory = document.getElementById('typeCategorySelect')?.value;
            if (String(selectedTypeCategory) === String(category.id)) renderCategoryFeatures(category.id);
            bootstrap.Modal.getInstance(document.getElementById('editCategoryModal'))?.hide();
            await swalFire({ icon: 'success', title: 'Updated', text: 'Category details saved.', timer: 2000, showConfirmButton: false });
        } catch (error) {
            await swalFire({ icon: 'error', title: 'Update failed', text: error.message });
        }
    });

    function refreshCategorySelects() {
        const items = state.categories.map((category) => ({ value: category.id, label: category.name }));
        ['typeCategorySelect', 'resourceCategorySelect'].forEach((id) => {
            const current = window.RMSelect.getValue(id);
            const placeholder = window.RMSelect.wrapFor(id)?.dataset.placeholder || 'Select a category';
            window.RMSelect.setOptions(id, items, placeholder);
            const selected = items.find((item) => String(item.value) === String(current));
            if (selected) window.RMSelect.setValue(id, selected.value, selected.label);
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
        state.categories.forEach((category) => {
            const row = document.createElement('tr');
            const name = document.createElement('td');
            name.textContent = category.name;
            const features = document.createElement('td');
            features.textContent = (category.features || []).map((feature) => feature.name).filter(Boolean).join(', ') || '—';
            const actions = document.createElement('td');
            actions.className = 'text-end';
            actions.innerHTML = `<div class="d-inline-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary rm-icon-btn" data-edit-id="${category.id}" aria-label="Edit" title="Edit"><i class="ti ti-pencil"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger rm-icon-btn" data-delete-id="${category.id}" aria-label="Delete" title="Delete"><i class="ti ti-trash"></i></button>
            </div>`;
            row.append(name, features, actions);
            body.appendChild(row);
        });
    }

    function refreshResourceSelects() {
        const items = state.resources.map((resource) => ({
            value: resource.id,
            label: `${resource.name_model}${resource.serial_number ? ` (${resource.serial_number})` : ''}`,
        }));
        ['primaryResourceSelect', 'linkedResourceSelect'].forEach((id) => {
            const current = window.RMSelect.getValue(id);
            const placeholder = window.RMSelect.wrapFor(id)?.dataset.placeholder || 'Select a resource';
            window.RMSelect.setOptions(id, items, placeholder);
            const selected = items.find((item) => String(item.value) === String(current));
            if (selected) window.RMSelect.setValue(id, selected.value, selected.label);
        });
    }

    function refreshLocationSelects() {
        const items = state.locations.map((location) => ({ value: location.id, label: location.name }));
        const current = window.RMSelect.getValue('resourceLocationSelect');
        window.RMSelect.setOptions('resourceLocationSelect', items, 'Select a location (e.g., Block A, Floor 2)');
        const selected = items.find((item) => String(item.value) === String(current));
        if (selected) window.RMSelect.setValue('resourceLocationSelect', selected.value, selected.label);
    }

    function renderCategoryFeatures(categoryId) {
        const panel = document.getElementById('typeAdditionalFeatures');
        const category = state.categories.find((item) => String(item.id) === String(categoryId));
        if (!category) {
            panel.classList.add('text-muted');
            panel.textContent = 'Select a category above to view its additional features.';
            return;
        }
        const features = category.features || [];
        if (!features.length) {
            panel.classList.add('text-muted');
            panel.textContent = 'This category has no additional features.';
            return;
        }
        panel.classList.remove('text-muted');
        const list = document.createElement('ul');
        list.className = 'list-unstyled mb-0';
        features.forEach((feature) => {
            const item = document.createElement('li');
            const strong = document.createElement('strong');
            strong.textContent = feature.name || '(unnamed feature)';
            item.appendChild(strong);
            const extra = document.createElement('span');
            extra.className = 'text-muted';
            extra.textContent = feature.enabled
                ? (feature.options ? ` — options: ${feature.options}` : ' — enabled, no options set')
                : ' — disabled';
            item.appendChild(extra);
            list.appendChild(item);
        });
        panel.replaceChildren(list);
    }

    function refreshTypeSelectForCategory(categoryId) {
        if (!categoryId) {
            window.RMSelect.setOptions('resourceTypeSelect', [], 'Select a resource type (e.g., Laptop)', { disabled: true });
            return;
        }
        const filtered = state.types.filter((type) => String(type.category_id) === String(categoryId));
        window.RMSelect.setOptions(
            'resourceTypeSelect',
            filtered.map((type) => ({ value: type.id, label: type.name })),
            filtered.length ? 'Select a resource type (e.g., Laptop)' : 'No resource types available for this category',
            { disabled: false }
        );
    }

    async function success(message, formId) {
        const result = await swalFire({ icon: 'success', title: 'Saved', text: message, confirmButtonText: 'Continue' });
        if (result.isConfirmed && nextTab[formId]) {
            bootstrap.Tab.getOrCreateInstance(document.getElementById(nextTab[formId])).show();
        }
    }

    async function loadInitialData() {
        try {
            state.categories = await fetchJSON(config.categoriesUrl);
            refreshCategorySelects();
        } catch (error) { console.error(error); }

        try {
            const types = await fetchJSON(config.typesUrl);
            state.types = types.map((type) => ({ id: type.id, category_id: type.resource_category_id, name: type.name }));
        } catch (error) { console.error(error); }

        try {
            state.resources = await fetchJSON(config.listUrl);
            refreshResourceSelects();
        } catch (error) { console.error(error); }

        try {
            state.locations = await fetchJSON(config.locationsUrl);
            refreshLocationSelects();
        } catch (error) { console.error(error); }

        if (!editResourceId) return;
        try {
            const resource = await fetchJSON(`${config.resourceUrl}/${editResourceId}`);
            const category = state.categories.find((item) => String(item.id) === String(resource.type?.resource_category_id));
            if (category) window.RMSelect.setValue('resourceCategorySelect', category.id, category.name);
            const type = state.types.find((item) => String(item.id) === String(resource.resource_type_id));
            if (type) window.RMSelect.setValue('resourceTypeSelect', type.id, type.name);
            const location = state.locations.find((item) => String(item.id) === String(resource.location_id));
            if (location) window.RMSelect.setValue('resourceLocationSelect', location.id, location.name);
            window.RMSelect.setValue('resourceStatusSelect', resource.status || '', ({
                active: 'Active', inactive: 'Inactive', under_maintenance: 'Under Maintenance', decommissioned: 'Decommissioned',
            })[resource.status] || resource.status);
            document.getElementById('resourceNameModel').value = resource.name_model || '';
            document.getElementById('serialNumber').value = resource.serial_number || '';
            const ownerField = document.getElementById('resourceOwner');
            if (ownerField) ownerField.value = resource.resource_owner || '';
            document.getElementById('saveResourceButton').textContent = 'Save changes';
            bootstrap.Tab.getOrCreateInstance(document.getElementById('resource-tab')).show();
        } catch (error) {
            await swalFire({ icon: 'error', title: 'Could not load resource', text: error.message });
        }
    }

    async function fetchJSON(url) {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `Failed to load ${url}`);
        return payload;
    }

    async function postJSON(url, data) {
        return requestJSON(url, 'POST', data);
    }

    async function requestJSON(url, method, data) {
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: data ? JSON.stringify(data) : undefined,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Request failed');
            error.errors = payload.errors || {};
            throw error;
        }
        return payload;
    }

    function showRequestErrors(error, map) {
        Object.entries(map).forEach(([field, id]) => {
            if (error.errors?.[field]) showFieldError(id, error.errors[field][0]);
        });
    }

    function showFieldError(id, message) {
        const field = document.getElementById(id);
        if (!field) return;
        const wrap = field.closest('[data-cu-select]');
        (wrap?.querySelector('.cu-dropdown-toggle') || field).classList.add('is-invalid');
        const feedback = (wrap || field).parentElement.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = message;
            feedback.classList.add('d-block');
        }
    }

    function clearFieldError(id) {
        const field = document.getElementById(id);
        if (!field) return;
        const wrap = field.closest('[data-cu-select]');
        (wrap?.querySelector('.cu-dropdown-toggle') || field).classList.remove('is-invalid');
        (wrap || field).parentElement.querySelector('.invalid-feedback')?.classList.remove('d-block');
    }

    function clearResourceErrors() {
        ['resourceCategorySelect', 'resourceTypeSelect', 'resourceLocationSelect', 'resourceStatusSelect', 'resourceNameModel', 'serialNumber']
            .forEach(clearFieldError);
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[character]));
    }
});
