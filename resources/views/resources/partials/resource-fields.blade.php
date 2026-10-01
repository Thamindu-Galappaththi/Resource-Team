<div class="row">
    <div class="col-md-6 mb-3">
        <label for="resourceCategorySelect" class="form-label">Category</label>
        <select class="form-select category-select" id="resourceCategorySelect" name="category_id" required>
            <option value="" selected disabled>Select a category (e.g., IT Equipment)</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="resourceTypeSelect" class="form-label">Type</label>
        <select class="form-select" id="resourceTypeSelect" name="resource_type_id" required disabled>
            <option value="" selected disabled>Select a resource type (e.g., Laptop)</option>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="resourceLocationSelect" class="form-label">Location</label>
        <select class="form-select location-select" id="resourceLocationSelect" name="location_id" required>
            <option value="" selected disabled>Select a location (e.g., Block A, Floor 2)</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="resourceOwnerSelect" class="form-label">Resource Owner</label>
        <input type="text" class="form-control" id="resourceOwnerSelect" value="" placeholder="e.g., Nadun">
    </div>
</div>

<div class="mb-3">
    <label for="resourceNameModel" class="form-label">Resource Name / Model</label>
    <input type="text" class="form-control" id="resourceNameModel" name="name_model" placeholder="e.g., Computer Lab 03" required>
</div>

<div class="mb-3">
    <label for="serialNumber" class="form-label">Serial Number <span class="text-muted">(Optional)</span></label>
    <input type="text" class="form-control" id="serialNumber" name="serial_number" maxlength="50" placeholder="e.g., SN-2024-00123">
</div>

<div class="mb-3">
    <label for="resourceStatusSelect" class="form-label">Status</label>
    <select class="form-select" id="resourceStatusSelect" name="status" required>
        <option value="" selected disabled>Select status (e.g., Available, Under Maintenance)</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="under_maintenance">Under Maintenance</option>
        <option value="decommissioned">Decommissioned</option>
    </select>
</div>
