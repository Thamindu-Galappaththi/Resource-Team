<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <x-rm-select id="resourceCategorySelect" name="category_id" class="category-select" required
            placeholder="Select a category (e.g., IT Equipment)"
            :options="[['value' => '', 'label' => 'Select a category (e.g., IT Equipment)']]" />
        <div class="invalid-feedback" id="resourceCategoryError"></div>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Type <span class="text-danger">*</span></label>
        <x-rm-select id="resourceTypeSelect" name="resource_type_id" required disabled
            placeholder="Select a resource type (e.g., Laptop)"
            :options="[['value' => '', 'label' => 'Select a resource type (e.g., Laptop)']]" />
        <div class="invalid-feedback" id="resourceTypeError"></div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Location <span class="text-danger">*</span></label>
        <x-rm-select id="resourceLocationSelect" name="location_id" class="location-select" required
            placeholder="Select a location (Welisara, Moratuwa or Peradeniya)"
            :options="[['value' => '', 'label' => 'Select a location (Welisara, Moratuwa or Peradeniya)']]" />
        <div class="invalid-feedback" id="resourceLocationError"></div>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Status <span class="text-danger">*</span></label>
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

@php
    $bedGroups = config('hostel.bed_groups', []);
    $bedDefault = array_sum(array_column($bedGroups, 'default'));
    $bedMax = (int) config('hostel.max_beds_per_group', 1000);
@endphp
<section class="rm-hostel-beds mb-3" id="hostelBedFields" hidden
    data-hostel-category="{{ config('hostel.category_name') }}"
    data-hostel-locations="{{ implode('|', config('hostel.locations', [])) }}"
    data-bed-type="{{ config('hostel.bed_type_name', 'Bed') }}">
    <div class="rm-hostel-head">
        <span class="rm-hostel-icon" aria-hidden="true"><i class="ti ti-bed"></i></span>
        <div>
            <h3 class="rm-hostel-title">Hostel beds</h3>
            <p class="rm-hostel-sub mb-0">One record per location holds every bed group. When new beds are added, edit this record and raise the count.</p>
        </div>
        <span class="rm-hostel-total" aria-live="polite"><strong data-bed-total>{{ $bedDefault }}</strong> beds</span>
    </div>
    <div class="row g-3">
        @foreach($bedGroups as $key => $group)
            <div class="col-md-4">
                <label for="resourceBeds_{{ $key }}" class="form-label">{{ $group['label'] }} <span class="text-danger">*</span></label>
                <div class="input-group rm-bed-input has-validation">
                    <input type="number" class="form-control" id="resourceBeds_{{ $key }}" data-bed-group="{{ $key }}" data-bed-default="{{ $group['default'] }}"
                        min="0" max="{{ $bedMax }}" step="1" inputmode="numeric" value="{{ $group['default'] }}" required>
                    <span class="input-group-text">beds</span>
                    <div class="invalid-feedback" id="resourceBeds_{{ $key }}Error"></div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<div class="mb-3">
    <label for="resourceNameModel" class="form-label">Resource Name / Model <span class="text-danger" data-name-required>*</span><span class="text-muted d-none" data-name-optional>(Optional for hostel)</span></label>
    <input type="text" class="form-control" id="resourceNameModel" name="name_model" maxlength="120" placeholder="e.g., Computer Lab 03" required>
    <div class="form-text">Maximum 120 characters.</div>
    <div class="invalid-feedback" id="resourceNameError"></div>
</div>

<div class="mb-3">
    <label for="serialNumber" class="form-label">Serial Number <span class="text-muted">(Optional)</span></label>
    <input type="text" class="form-control" id="serialNumber" name="serial_number" maxlength="50" placeholder="e.g., SN-2024-00123">
    <div class="invalid-feedback" id="serialNumberError"></div>
</div>

<div class="mb-3">
    <label for="resourceOwner" class="form-label">Resource Owner <span class="text-muted">(Optional)</span></label>
    <input type="text" class="form-control" id="resourceOwner" name="resource_owner" maxlength="255" placeholder="e.g., Nadun">
    <div class="invalid-feedback" id="resourceOwnerError"></div>
</div>
