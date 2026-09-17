@extends('layouts.app')

@section('title', 'Create Reservation')

@section('content')
<style>
    .reservation-page { max-width: 1120px; margin: 1.5rem auto 2rem; }
    .reservation-hero, .reservation-panel { background: rgba(255, 255, 255, .93); border: 1px solid rgba(255,255,255,.65); border-radius: 14px; box-shadow: 0 16px 35px rgba(9, 30, 66, .18); }
    .reservation-hero { padding: 1.7rem 2rem; margin-bottom: 1.5rem; }
    .reservation-hero h1 { color: #09111f; font-size: clamp(1.8rem, 4vw, 2.45rem); margin: 0; font-weight: 700; }
    .reservation-hero p { color: #4e607f; margin: .35rem 0 0; }
    .reservation-panel { padding: 1.55rem 1.7rem; margin-bottom: 1.5rem; }
    .reservation-panel-heading { display: flex; align-items: center; gap: .7rem; margin-bottom: 1.45rem; }
    .reservation-panel-heading h2 { font-size: 1.25rem; margin: 0; color: #09111f; }
    .reservation-icon { display: inline-grid; place-items: center; width: 2rem; height: 2rem; border-radius: 50%; background: #dbeafe; color: #075985; }
    .reservation-label { color: #24324a; font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; margin-bottom: .4rem; }
    .reservation-page .form-control, .reservation-page .form-select { min-height: 42px; border-color: rgba(36, 50, 74, .22); border-radius: 8px; background-color: rgba(255,255,255,.65); }
    .reservation-page textarea.form-control { min-height: 100px; resize: vertical; }
    .reservation-page .form-control:focus, .reservation-page .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .14); }
    .resource-preview { display: none; margin-top: 1.25rem; padding: 1rem; border: 1px solid #dbe3f0; border-left: 4px solid #2563eb; border-radius: 9px; background: #f8fafc; }
    .resource-preview.is-visible { display: block; animation: reservation-enter 180ms ease-out; }
    .resource-preview-title { color: #102e55; font-size: 1.05rem; font-weight: 700; }
    .resource-preview-meta { color: #63758f; font-size: .88rem; }
    .reservation-status { min-height: 1.4rem; color: #526281; font-size: .88rem; }
    .addon-panel { display: none; margin-top: 1rem; padding: 1.2rem; border: 1px solid #dbe3f0; border-radius: 10px; background: #f8fafc; }
    .addon-panel.is-visible { display: block; animation: reservation-enter 180ms ease-out; }
    .addon-badge { border: 1px solid #cbd5e1; border-radius: 999px; color: #475569; font-size: .72rem; padding: .2rem .55rem; }
    .reservation-actions { display: flex; justify-content: flex-end; }
    .reservation-submit { min-width: 245px; min-height: 52px; border: 0; border-radius: 9px; background: #2563eb; box-shadow: 0 10px 20px rgba(37,99,235,.24); font-weight: 700; }
    .reservation-submit:hover { background: #1d4ed8; }
    @keyframes reservation-enter { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 576px) { .reservation-page { margin-top: .75rem; } .reservation-hero, .reservation-panel { padding: 1.15rem; } .reservation-actions { justify-content: stretch; } .reservation-submit { width: 100%; } }
</style>

<div class="container-fluid reservation-page">
    <section class="reservation-hero" aria-labelledby="reservation-title">
        <h1 id="reservation-title">Create New Reservation</h1>
        <div id="debugInfo" style="display: none; background: #fee; padding: 10px; margin-top: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;"></div>
    </section>

    <form id="reservationForm" action="{{ route('reservations.store') }}" method="POST" novalidate>
        @csrf
        <section class="reservation-panel" aria-labelledby="details-title">
            <div class="reservation-panel-heading">
                <span class="reservation-icon"><i class="ti ti-calendar-event" aria-hidden="true"></i></span>
                <h2 id="details-title">Reservation Details</h2>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="reservation-label" for="reservationDate">Reservation date</label>
                    <input class="form-control" type="date" id="reservationDate" name="reservation_date" min="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-4">
                    <label class="reservation-label" for="startTime">Start time</label>
                    <input class="form-control" type="time" id="startTime" name="start_time" required>
                </div>
                <div class="col-md-4">
                    <label class="reservation-label" for="endTime">End time</label>
                    <input class="form-control" type="time" id="endTime" name="end_time" required>
                </div>
                <div class="col-md-4">
                    <label class="reservation-label" for="locationSelect">Location</label>
                    <select class="form-select" id="locationSelect" name="location_id" required>
    <option value="">Select location</option>
    <option value="1">Welisara</option>
    <option value="2">Peradeniya</option>
    <option value="3">Moratuwa</option>
</select>
                </div>
                <div class="col-md-4">
                    <label class="reservation-label" for="categorySelect">Resource category</label>
                    <select class="form-select" id="categorySelect" name="resource_category_id" required>
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="reservation-label" for="resourceSelect">Available resource</label>
                    <select class="form-select" id="resourceSelect" name="resource_id" required disabled>
                        <option value="">Choose date, time, location and category first</option>
                    </select>
                </div>
            </div>

            <div id="availabilityStatus" class="reservation-status mt-3" aria-live="polite"></div>
            <div id="resourcePreview" class="resource-preview" aria-live="polite"></div>

            <div class="mt-4">
                <label class="reservation-label" for="specialRequirements">Special requirements</label>
                <textarea class="form-control" id="specialRequirements" name="special_requirements" maxlength="2000" "></textarea>
            </div>
        </section>

        <section class="reservation-panel" aria-labelledby="addons-title">
            <div class="reservation-panel-heading justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="reservation-icon"><i class="ti ti-puzzle" aria-hidden="true"></i></span>
                    <h2 id="addons-title">Resource Add-ons</h2>
                </div>
                <span class="addon-badge">Optional</span>
            </div>

            <button class="btn btn-outline-primary" type="button" id="toggleAddOn" aria-expanded="false" aria-controls="addOnPanel">
                <i class="ti ti-plus me-1" aria-hidden="true"></i>Add Resource Add-on
            </button>

            <div class="addon-panel" id="addOnPanel">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="reservation-label" for="addOnLocation">Location</label>
                        <select class="form-select" id="addOnLocation" disabled>
                            <option value="">Select location above</option>
                            <option value="1">Welisara</option>
                            <option value="2">Peradeniya</option>
                            <option value="3">Moratuwa</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="reservation-label" for="addOnResource">Select add-on</label>
                        <select class="form-select" id="addOnResource" name="add_ons[0][resource_id]" disabled>
                            <option value="">Select an available add-on</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="reservation-label" for="addOnSpecialRemarks">Add-on special remarks</label>
                        <textarea class="form-control" id="addOnSpecialRemarks" name="add_ons[0][special_remarks]" maxlength="1000" ></textarea>
                    </div>
                </div>
                <div id="addOnPreview" class="resource-preview" aria-live="polite"></div>
                <button class="btn btn-sm btn-outline-secondary mt-3" type="button" id="removeAddOn">Remove add-on</button>
            </div>
        </section>

        <div id="reservationFeedback" class="mb-3" aria-live="polite"></div>
        <div class="reservation-actions">
            <button class="btn btn-primary reservation-submit" type="submit" id="submitReservation" disabled>
                Complete Reservation <i class="ti ti-arrow-right ms-1" aria-hidden="true"></i>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const state = { categories: [], locations: [], resources: [] };
    const form = document.getElementById('reservationForm');
    const date = document.getElementById('reservationDate');
    const start = document.getElementById('startTime');
    const end = document.getElementById('endTime');
    const location = document.getElementById('locationSelect');
    const category = document.getElementById('categorySelect');
    const resource = document.getElementById('resourceSelect');
    const addOnLocation = document.getElementById('addOnLocation');
    const addOnResource = document.getElementById('addOnResource');
    const status = document.getElementById('availabilityStatus');
    const submit = document.getElementById('submitReservation');
    const debugInfo = document.getElementById('debugInfo');

    
    async function getJSON(url) {
        console.log('Fetching:', url);
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            console.log('Response status:', response.status, 'URL:', url);
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Response error:', errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            const data = await response.json();
            console.log('JSON response:', data);
            return data;
        } catch (error) {
            console.error('Fetch error:', error, 'URL:', url);
            throw error;
        }
    }

    function fillSelect(select, items, label) {
        select.innerHTML = `<option value="">${label}</option>`;
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name || item.name_model;
            select.appendChild(option);
        });
    }

    function resourceCategory(item) { return item.type?.resource_category_id || item.type?.category?.id; }
    function isAddOn(item) { return String(item.serial_number || '').startsWith('ADDON-'); }

    function renderPreview(target, item, addOn = false) {
        if (!item) {
            target.classList.remove('is-visible');
            target.innerHTML = '';
            return;
        }
        const categoryName = item.type?.category?.name || 'Resource';
        const typeName = item.type?.name || 'Asset';
        target.innerHTML = `<div class="resource-preview-title"><i class="ti ti-${addOn ? 'puzzle' : 'box'} me-1"></i>${item.name_model}</div>
            <div class="resource-preview-meta mt-1">${categoryName} / ${typeName} · ${item.location?.name || 'Assigned location'}</div>
            <div class="small text-success mt-2"><i class="ti ti-circle-check me-1"></i>Available for this reservation</div>`;
        target.classList.add('is-visible');
    }

    function validWindow() { return date.value && start.value && end.value && start.value < end.value && location.value && category.value; }

    async function refreshAvailability() {
        resource.disabled = true;
        submit.disabled = true;
        addOnLocation.value = location.value;
        renderPreview(document.getElementById('resourcePreview'), null);
        if (!validWindow()) {
            resource.innerHTML = '<option value="">Choose valid reservation details first</option>';
            status.textContent = start.value && end.value && start.value >= end.value ? 'End time must be later than start time.' : '';
            return;
        }
        status.textContent = 'Checking availability...';
        try {
            const params = new URLSearchParams({ reservation_date: date.value, start_time: start.value, end_time: end.value, location_id: location.value, resource_category_id: category.value });
            state.resources = await getJSON(`{{ route('reservations.available-resources') }}?${params}`);
            const primaryResources = state.resources.filter(item => !isAddOn(item));
            fillSelect(resource, primaryResources, primaryResources.length ? 'Select available resource' : 'No resources available');
            resource.disabled = !primaryResources.length;
            status.textContent = primaryResources.length ? `${primaryResources.length} resource${primaryResources.length === 1 ? '' : 's'} available.` : 'No resources match this date, time, location and category.';
            refreshAddOnResources();
        } catch (error) {
            status.textContent = 'Availability could not be checked. Please try again.';
        }
    }

    function refreshAddOnResources() {
        const selectedId = resource.value;
        const options = state.resources.filter(item => String(item.id) !== String(selectedId));
        fillSelect(addOnResource, options, options.length ? 'Select an available add-on' : 'No add-ons available');
        addOnResource.disabled = !selectedId || !options.length;
    }

    [date, start, end, category].forEach(input => input.addEventListener('change', refreshAvailability));
    location.addEventListener('change', function () {
        addOnLocation.value = location.value;
        refreshAvailability();
    });
    resource.addEventListener('change', function () {
        renderPreview(document.getElementById('resourcePreview'), state.resources.find(item => String(item.id) === String(resource.value)));
        submit.disabled = !resource.value;
        addOnLocation.value = location.value;
        refreshAddOnResources();
    });
    addOnResource.addEventListener('change', function () {
        renderPreview(document.getElementById('addOnPreview'), state.resources.find(item => String(item.id) === String(addOnResource.value)), true);
    });

    document.getElementById('toggleAddOn').addEventListener('click', function () {
        const panel = document.getElementById('addOnPanel');
        const visible = panel.classList.toggle('is-visible');
        this.setAttribute('aria-expanded', visible ? 'true' : 'false');
        addOnLocation.disabled = !visible || !location.value;
        if (visible) { addOnLocation.value = location.value; refreshAddOnResources(); }
    });
    document.getElementById('removeAddOn').addEventListener('click', function () {
        document.getElementById('addOnPanel').classList.remove('is-visible');
        document.getElementById('toggleAddOn').setAttribute('aria-expanded', 'false');
        addOnResource.value = '';
        document.getElementById('addOnSpecialRemarks').value = '';
        renderPreview(document.getElementById('addOnPreview'), null);
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!validWindow() || !resource.value) return;
        submit.disabled = true;
        document.getElementById('reservationFeedback').innerHTML = '<div class="alert alert-info">Submitting reservation...</div>';
        const payload = Object.fromEntries(new FormData(form).entries());
        payload.add_ons = addOnResource.value ? [{ resource_id: addOnResource.value, special_remarks: document.getElementById('addOnSpecialRemarks').value }] : [];
        try {
            const response = await fetch(form.action, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value }, body: JSON.stringify(payload) });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Please review the form and try again.');
            document.getElementById('reservationFeedback').innerHTML = '<div class="alert alert-success">Reservation submitted successfully! Redirecting to existing reservations...</div>';
            setTimeout(function() {
                window.location.replace('{{ route('reservations.index') }}?refresh=' + Date.now());
            }, 1500);
        } catch (error) {
            document.getElementById('reservationFeedback').innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
            submit.disabled = false;
        }
    });

   

});
</script>
@endpush
