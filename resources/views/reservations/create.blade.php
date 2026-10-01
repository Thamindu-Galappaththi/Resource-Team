@extends('layouts.app')

@section('title', 'Create Reservation')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Create Reservations</h2>
            <p class="text-muted mb-0">Select a resource, date, and time. The system blocks overlapping bookings automatically.</p>
        </div>
        <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary">Existing Reservations</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('reservations.store') }}" id="reservationForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="location_id">Location</label>
                        <select class="form-select" id="location_id" name="location_id">
                            <option value="">All locations</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="category_id">Resource Category</label>
                        <select class="form-select" id="category_id">
                            <option value="">All categories</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="resource_id">Resource <span class="text-danger">*</span></label>
                        <select class="form-select @error('resource_id') is-invalid @enderror" id="resource_id" name="resource_id" required>
                            <option value="">Select a resource</option>
                        </select>
                        @error('resource_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12" id="linkedWrap" style="display:none;">
                        <label class="form-label">Add-on resources</label>
                        <div id="linkedResources" class="border rounded p-3 bg-light"></div>
                        <small class="text-muted">Linked equipment is booked in the same time slot, in one transaction.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="reservation_date">Reservation Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('reservation_date') is-invalid @enderror" id="reservation_date" name="reservation_date" value="{{ old('reservation_date', $prefillDate) }}" min="{{ now()->toDateString() }}" required>
                        @error('reservation_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="start_time">Start Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control @error('start_time') is-invalid @enderror" id="start_time" name="start_time" value="{{ old('start_time', $prefillStart) }}" required>
                        @error('start_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="end_time">End Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control @error('end_time') is-invalid @enderror" id="end_time" name="end_time" value="{{ old('end_time', $prefillEnd) }}" required>
                        @error('end_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="purpose">Purpose of Reservation <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('purpose') is-invalid @enderror" id="purpose" name="purpose" value="{{ old('purpose') }}" maxlength="500" placeholder="e.g. CCNA Batch 12 practical lab" required>
                        @error('purpose')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="attendee_count">Attendee Count</label>
                        <input type="number" min="1" class="form-control" id="attendee_count" name="attendee_count" value="{{ old('attendee_count') }}">
                    </div>
                    <div class="col-12">
                        <div id="availabilityAlert" class="alert alert-light border mb-0">Select a resource and time slot to check availability.</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary" id="submitReservation">Submit for approval</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lookupsUrl = @json(route('reservations.lookups'));
    const availabilityUrl = @json(route('reservations.availability'));
    const prefillResourceId = @json(old('resource_id', $prefillResourceId));
    const oldResourceIds = @json(old('resource_ids', []));

    const locationSelect = document.getElementById('location_id');
    const categorySelect = document.getElementById('category_id');
    const resourceSelect = document.getElementById('resource_id');
    const linkedWrap = document.getElementById('linkedWrap');
    const linkedBox = document.getElementById('linkedResources');
    const availabilityAlert = document.getElementById('availabilityAlert');
    const submitBtn = document.getElementById('submitReservation');

    let lookups = { locations: [], categories: [], resources: [] };

    function selectedResourceIds() {
        const ids = [];
        if (resourceSelect.value) ids.push(resourceSelect.value);
        linkedBox.querySelectorAll('input[name="resource_ids[]"]:checked').forEach((el) => ids.push(el.value));
        return ids;
    }

    function renderOptions(select, rows, labelKey, placeholder) {
        const current = select.value;
        select.innerHTML = `<option value="">${placeholder}</option>`;
        rows.forEach((row) => {
            const opt = document.createElement('option');
            opt.value = row.id;
            opt.textContent = row[labelKey];
            select.appendChild(opt);
        });
        if (current) select.value = current;
    }

    function filteredResources() {
        return lookups.resources.filter((resource) => {
            if (locationSelect.value && String(resource.location_id) !== String(locationSelect.value)) return false;
            if (categorySelect.value && String(resource.category_id) !== String(categorySelect.value)) return false;
            return true;
        });
    }

    function refreshResources() {
        const rows = filteredResources();
        resourceSelect.innerHTML = '<option value="">Select a resource</option>';
        rows.forEach((resource) => {
            const opt = document.createElement('option');
            opt.value = resource.id;
            opt.textContent = `${resource.name_model} (${resource.serial_number}) — ${resource.location_name ?? 'No location'}`;
            resourceSelect.appendChild(opt);
        });
        if (prefillResourceId) resourceSelect.value = prefillResourceId;
        renderLinked();
        checkAvailability();
    }

    function renderLinked() {
        const resource = lookups.resources.find((row) => String(row.id) === String(resourceSelect.value));
        linkedBox.innerHTML = '';
        if (!resource || !resource.linked_resources?.length) {
            linkedWrap.style.display = 'none';
            return;
        }
        linkedWrap.style.display = 'block';
        resource.linked_resources.filter((row) => row.status === 'active').forEach((linked) => {
            const wrap = document.createElement('div');
            wrap.className = 'form-check';
            const checked = oldResourceIds.map(String).includes(String(linked.id)) ? 'checked' : '';
            wrap.innerHTML = `<input class="form-check-input" type="checkbox" name="resource_ids[]" value="${linked.id}" id="linked-${linked.id}" ${checked}>
                <label class="form-check-label" for="linked-${linked.id}">${linked.name_model} (${linked.serial_number})</label>`;
            linkedBox.appendChild(wrap);
        });
        linkedBox.querySelectorAll('input').forEach((el) => el.addEventListener('change', checkAvailability));
    }

    async function checkAvailability() {
        const ids = selectedResourceIds();
        const date = document.getElementById('reservation_date').value;
        const start = document.getElementById('start_time').value;
        const end = document.getElementById('end_time').value;
        if (!ids.length || !date || !start || !end || end <= start) {
            availabilityAlert.className = 'alert alert-light border mb-0';
            availabilityAlert.textContent = 'Select a resource and time slot to check availability.';
            submitBtn.disabled = false;
            return;
        }

        const params = new URLSearchParams();
        ids.forEach((id) => params.append('resource_ids[]', id));
        params.set('reservation_date', date);
        params.set('start_time', start);
        params.set('end_time', end);

        try {
            const response = await fetch(`${availabilityUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (!data.available) {
                availabilityAlert.className = 'alert alert-danger mb-0';
                availabilityAlert.textContent = data.message || 'Selected slot unavailable';
                submitBtn.disabled = true;
                return;
            }
            availabilityAlert.className = 'alert alert-success mb-0';
            availabilityAlert.textContent = data.message;
            submitBtn.disabled = false;
        } catch (error) {
            availabilityAlert.className = 'alert alert-warning mb-0';
            availabilityAlert.textContent = 'Could not check availability right now. You can still submit and the server will re-check.';
            submitBtn.disabled = false;
        }
    }

    fetch(lookupsUrl, { headers: { 'Accept': 'application/json' } })
        .then((res) => res.json())
        .then((data) => {
            lookups = data;
            renderOptions(locationSelect, data.locations, 'name', 'All locations');
            renderOptions(categorySelect, data.categories, 'name', 'All categories');
            refreshResources();
        });

    ['location_id', 'category_id'].forEach((id) => {
        document.getElementById(id).addEventListener('change', refreshResources);
    });
    resourceSelect.addEventListener('change', function () {
        renderLinked();
        checkAvailability();
    });
    ['reservation_date', 'start_time', 'end_time'].forEach((id) => {
        document.getElementById(id).addEventListener('change', checkAvailability);
    });
});
</script>
@endpush
