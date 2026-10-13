<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Category</label>
        <x-rm-select id="resourceCategorySelect" name="category_id" class="category-select" required
            placeholder="Select a category (e.g., IT Equipment)"
            :options="[['value' => '', 'label' => 'Select a category (e.g., IT Equipment)']]" />
        <div class="invalid-feedback" id="resourceCategoryError"></div>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Type</label>
        <x-rm-select id="resourceTypeSelect" name="resource_type_id" required disabled
            placeholder="Select a resource type (e.g., Laptop)"
            :options="[['value' => '', 'label' => 'Select a resource type (e.g., Laptop)']]" />
        <div class="invalid-feedback" id="resourceTypeError"></div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Location</label>
        <x-rm-select id="resourceLocationSelect" name="location_id" class="location-select" required
            placeholder="Select a location (e.g., Block A, Floor 2)"
            :options="[['value' => '', 'label' => 'Select a location (e.g., Block A, Floor 2)']]" />
        <div class="invalid-feedback" id="resourceLocationError"></div>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Status</label>
        <x-rm-select id="resourceStatusSelect" name="status" required
            placeholder="Select status (e.g., Available, Under Maintenance)"
            :options="[
                ['value' => '', 'label' => 'Select status (e.g., Available, Under Maintenance)'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'under_maintenance', 'label' => 'Under Maintenance'],
                ['value' => 'decommissioned', 'label' => 'Decommissioned'],
            ]" />
        <div class="invalid-feedback" id="resourceStatusError"></div>
    </div>
</div>

<div class="mb-3">
    <label for="resourceNameModel" class="form-label">Resource Name / Model</label>
    <input type="text" class="form-control" id="resourceNameModel" name="name_model" placeholder="e.g., Computer Lab 03" required>
    <div class="invalid-feedback" id="resourceNameError"></div>
</div>

<div class="mb-3">
    <label for="serialNumber" class="form-label">Serial Number <span class="text-muted">(Optional)</span></label>
    <input type="text" class="form-control" id="serialNumber" name="serial_number" maxlength="50" placeholder="e.g., SN-2024-00123">
    <div class="invalid-feedback" id="serialNumberError"></div>
</div>
