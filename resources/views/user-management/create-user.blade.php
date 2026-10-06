@extends('layouts.app')

@section('title', 'Create User')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=12">
@endpush

@section('content')
<div class="container-fluid py-4 cu-page">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <h1 class="cu-title text-white mb-2">Create User</h1>
            <p class="text-white-50 mb-0">Provision an account, assign roles, and review the access that comes with them.</p>
        </div>
        @if(auth()->user()->hasPermission('user.management'))
            <a href="{{ route('user.management') }}" class="btn btn-outline-light">Back to user list</a>
        @endif
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($employeeLookupMock)
        <div class="alert alert-info">Employee lookup is using development sample data because the SLT ERP API is only reachable on the intranet. Try employee ID <strong>010375</strong>. On the live server this will call the real API.</div>
    @endif

    <form method="POST" action="{{ route('create.user.store') }}" id="create-user-form" class="cu-card p-3 p-md-4">
        @csrf
        <section class="mb-4">
            <h2 class="h5 mb-3">Employee details</h2>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">SLT employee <span class="text-danger">*</span></label>
                    <div class="dropdown cu-select" data-cu-select>
                        <input type="hidden" name="slt_employee" id="slt_employee" value="{{ old('slt_employee') }}">
                        <button class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="js-select-label {{ old('slt_employee') ? '' : 'text-muted' }}">{{ old('slt_employee') === 'yes' ? 'Yes' : (old('slt_employee') === 'no' ? 'No' : 'Select an option') }}</span>
                        </button>
                        <ul class="dropdown-menu cu-dropdown-menu">
                            <li><button type="button" class="dropdown-item" data-value="" data-label="Select an option">Select an option</button></li>
                            <li><button type="button" class="dropdown-item" data-value="yes" data-label="Yes">Yes</button></li>
                            <li><button type="button" class="dropdown-item" data-value="no" data-label="No">No</button></li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="service_id" class="form-label">Employee ID <span id="employeeIdRequired" class="text-danger {{ old('slt_employee') === 'yes' ? '' : 'd-none' }}">*</span></label>
                    <div class="input-group flex-nowrap">
                        <input type="text" name="service_id" id="service_id" class="form-control" value="{{ old('slt_employee') === 'yes' ? old('service_id') : '' }}" placeholder="{{ old('slt_employee') === 'yes' ? 'e.g. 010375' : 'Enabled when SLT employee is Yes' }}" inputmode="numeric" autocomplete="off" @disabled(old('slt_employee') !== 'yes')>
                        <button id="lookupEmployee" class="btn btn-outline-primary cu-lookup d-none" type="button">Find employee</button>
                    </div>
                    <small id="lookupMessage" class="form-text"></small>
                </div>
                <div id="employeeDetails" class="col-12 cu-details">
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="fw-semibold mb-2">Directory record</div>
                        <div class="row g-2 small" id="employeeDetailsBody"></div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="Enter full name" required maxlength="100">
                </div>
                <div class="col-12 col-md-6">
                    <label for="nic" class="form-label">NIC <span class="text-danger">*</span></label>
                    <input type="text" name="nic" id="nic" class="form-control" value="{{ old('nic') }}" placeholder="12-digit NIC" required maxlength="12">
                </div>
                <div class="col-12 col-md-6">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" placeholder="name@example.com" required>
                </div>
                <div class="col-12 col-md-6">
                    <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" placeholder="Enter phone number" required>
                </div>
                <div class="col-12 col-md-6">
                    <label for="designation" class="form-label">Designation</label>
                    <input type="text" name="designation" id="designation" class="form-control" value="{{ old('designation') }}" placeholder="Enter designation">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Location <span class="text-danger">*</span></label>
                    <div class="dropdown cu-select" data-cu-select>
                        <input type="hidden" name="location" id="location" value="{{ old('location') }}">
                        <button class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="js-select-label {{ old('location') ? '' : 'text-muted' }}">{{ old('location') ?: 'Select location' }}</span>
                        </button>
                        <ul class="dropdown-menu cu-dropdown-menu">
                            <li><button type="button" class="dropdown-item" data-value="" data-label="Select location">Select location</button></li>
                            @foreach($locations as $location)
                                <li><button type="button" class="dropdown-item" data-value="{{ $location }}" data-label="{{ $location }}">{{ $location }}</button></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-4">
            <div class="row g-3 cu-align-fields">
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="rolesDropdown">Roles <span class="text-danger">*</span></label>
                    <p class="text-muted small cu-field-hint">Select one or more roles. Permissions follow the chosen roles.</p>
                    <div class="dropdown">
                        <button id="rolesDropdown" class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <span id="rolesSummary" class="{{ old('user_roles') ? '' : 'text-muted' }}">Select roles</span>
                        </button>
                        <div class="dropdown-menu cu-dropdown-menu">
                            @foreach($roles as $role)
                                <label class="dropdown-item cu-check-item">
                                    <input type="checkbox" name="user_roles[]" value="{{ $role->slug }}" class="form-check-input mt-0 js-user-role" @checked(in_array($role->slug, old('user_roles', []), true))>
                                    <span class="js-role-name">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div id="rolesError" class="invalid-feedback">Select at least one role.</div>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="permissionsDropdown">Permissions</label>
                    <p class="text-muted small cu-field-hint">Role access is locked. Extra access can be granted to this user only.</p>
                    <div class="dropdown">
                        <button id="permissionsDropdown" class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <span id="permissionsSummary" class="text-muted">Select extra permissions</span>
                        </button>
                        <div class="dropdown-menu cu-dropdown-menu">
                            @foreach($permissionGroups as $group)
                                <h6 class="dropdown-header">{{ $group['section'] }}</h6>
                                @foreach($group['actions'] as $action)
                                    <label class="dropdown-item cu-check-item js-perm-row" data-permission="{{ $action['slug'] }}">
                                        <input type="checkbox" name="extra_permissions[]" value="{{ $action['slug'] }}" class="form-check-input mt-0 js-extra-permission" @checked(in_array($action['slug'], old('extra_permissions', []), true))>
                                        <span>{{ $action['action'] }}</span>
                                        <small class="js-perm-source">Off</small>
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 cu-actions">
            <a href="{{ auth()->user()->hasPermission('user.management') ? route('user.management') : route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create user</button>
        </div>
    </form>
</div>

@push('scripts')
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script>
    const sltEmployee = document.getElementById('slt_employee');
    const employeeId = document.getElementById('service_id');
    const lookupButton = document.getElementById('lookupEmployee');
    const lookupMessage = document.getElementById('lookupMessage');
    const employeeDetails = document.getElementById('employeeDetails');
    const employeeDetailsBody = document.getElementById('employeeDetailsBody');
    const lookupUrl = @json(route('slt.employee.lookup'));
    const rolePermissions = @json($rolePermissions);
    const fillableFields = ['name', 'email', 'phone', 'designation'].map((id) => document.getElementById(id));

    function setSltEmployeeMode() {
        const isSltEmployee = sltEmployee.value === 'yes';
        employeeId.required = isSltEmployee;
        employeeId.disabled = !isSltEmployee;
        employeeId.placeholder = isSltEmployee ? 'e.g. 010375' : 'Enabled when SLT employee is Yes';
        document.getElementById('employeeIdRequired').classList.toggle('d-none', !isSltEmployee);
        lookupButton.classList.toggle('d-none', !isSltEmployee);
        fillableFields.forEach((field) => { field.readOnly = isSltEmployee; });

        if (!isSltEmployee) {
            employeeId.value = '';
            lookupMessage.textContent = '';
            lookupMessage.className = 'form-text';
            employeeDetails.classList.remove('is-visible');
            fillableFields.forEach((field) => { field.readOnly = false; });
        }
    }

    function markDropdownInvalid(toggle, isInvalid) {
        toggle.classList.toggle('is-invalid', isInvalid);
    }

    function renderEmployeeDetails(details) {
        const labels = {
            organization: 'Organization',
            section: 'Section',
            division: 'Division',
            grade: 'Grade',
            cost_centre: 'Cost centre',
            work_location: 'Work location',
        };
        employeeDetailsBody.innerHTML = Object.entries(labels).map(([key, label]) => {
            const value = details?.[key] || '—';
            return `<div class="col-6 col-md-4"><div class="text-muted">${label}</div><div class="fw-medium">${value}</div></div>`;
        }).join('');
        employeeDetails.classList.add('is-visible');
    }

    async function lookupEmployee() {
        if (!employeeId.value.trim()) {
            lookupMessage.textContent = 'Enter an Employee ID first.';
            lookupMessage.className = 'form-text text-danger';
            return;
        }

        lookupButton.disabled = true;
        lookupMessage.textContent = 'Looking up employee details...';
        lookupMessage.className = 'form-text text-muted';

        try {
            const response = await fetch(`${lookupUrl}?employee_id=${encodeURIComponent(employeeId.value.trim())}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Employee lookup failed.');

            ['name', 'email', 'phone', 'designation'].forEach((id) => {
                if (data[id]) document.getElementById(id).value = data[id];
            });
            if (data.service_id) employeeId.value = data.service_id;
            if (data.nic) document.getElementById('nic').value = data.nic;
            renderEmployeeDetails(data.details || {});
            lookupMessage.textContent = data.mock
                ? 'Development employee data loaded. The live ERP API will be used in production.'
                : 'Employee details loaded from SLT ERP.';
            lookupMessage.className = 'form-text text-success';
        } catch (error) {
            lookupMessage.textContent = error.message;
            lookupMessage.className = 'form-text text-danger';
        } finally {
            lookupButton.disabled = false;
        }
    }

    function bindSelectDropdowns() {
        document.querySelectorAll('[data-cu-select]').forEach((wrap) => {
            const input = wrap.querySelector('select, input[type="hidden"]');
            const label = wrap.querySelector('.js-select-label');
            wrap.querySelectorAll('[data-value]').forEach((item) => {
                item.addEventListener('click', () => {
                    input.value = item.dataset.value;
                    label.textContent = item.dataset.label || item.textContent.trim();
                    label.classList.toggle('text-muted', !input.value);
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    wrap.querySelector('.cu-dropdown-toggle')?.classList.remove('is-invalid');
                });
            });
        });
    }

    function grantedByRoles() {
        const selected = [...document.querySelectorAll('.js-user-role:checked')].map((input) => input.value);
        return new Set(selected.flatMap((slug) => rolePermissions[slug] || []));
    }

    function updateRoleSummary() {
        const names = [...document.querySelectorAll('.js-user-role:checked')]
            .map((input) => input.closest('label')?.querySelector('.js-role-name')?.textContent.trim())
            .filter(Boolean);
        const summary = document.getElementById('rolesSummary');
        summary.textContent = names.length ? names.join(', ') : 'Select roles';
        summary.classList.toggle('text-muted', names.length === 0);
        document.getElementById('rolesDropdown').classList.remove('is-invalid');
        document.getElementById('rolesError').classList.remove('d-block');
    }

    function updatePermissionSummary() {
        const granted = document.querySelectorAll('.js-extra-permission:checked').length;
        const extra = [...document.querySelectorAll('.js-extra-permission')]
            .filter((checkbox) => checkbox.checked && !checkbox.disabled).length;
        const summary = document.getElementById('permissionsSummary');
        if (!granted) {
            summary.textContent = 'Select extra permissions';
            summary.classList.add('text-muted');
            return;
        }
        summary.textContent = extra
            ? `${granted} selected (${extra} extra)`
            : `${granted} from selected roles`;
        summary.classList.remove('text-muted');
    }

    function refreshPermissions() {
        const granted = grantedByRoles();
        const extras = new Set([...document.querySelectorAll('.js-extra-permission')]
            .filter((checkbox) => checkbox.checked && !checkbox.disabled)
            .map((checkbox) => checkbox.value));

        document.querySelectorAll('.js-perm-row').forEach((row) => {
            const slug = row.dataset.permission;
            const checkbox = row.querySelector('.js-extra-permission');
            const source = row.querySelector('.js-perm-source');
            const fromRole = granted.has(slug);

            if (fromRole) {
                checkbox.checked = true;
                checkbox.disabled = true;
                source.textContent = 'Role';
            } else {
                checkbox.disabled = false;
                checkbox.checked = extras.has(slug);
                source.textContent = checkbox.checked ? 'Extra' : 'Off';
            }

            row.classList.toggle('is-granted', fromRole);
            row.classList.toggle('is-extra', !fromRole && checkbox.checked);
        });

        updateRoleSummary();
        updatePermissionSummary();
    }

    sltEmployee.addEventListener('change', setSltEmployeeMode);
    lookupButton.addEventListener('click', lookupEmployee);
    employeeId.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookupEmployee();
        }
    });
    document.querySelectorAll('.js-user-role, .js-extra-permission').forEach((input) => {
        input.addEventListener('change', refreshPermissions);
    });
    document.getElementById('create-user-form').addEventListener('submit', (event) => {
        const missingSlt = !sltEmployee.value;
        const missingLocation = !document.getElementById('location').value;
        const missingRole = !document.querySelector('.js-user-role:checked');

        if (missingSlt || missingLocation || missingRole) {
            event.preventDefault();
            markDropdownInvalid(sltEmployee.closest('.dropdown').querySelector('.cu-dropdown-toggle'), missingSlt);
            markDropdownInvalid(document.getElementById('location').closest('.dropdown').querySelector('.cu-dropdown-toggle'), missingLocation);
            markDropdownInvalid(document.getElementById('rolesDropdown'), missingRole);
            document.getElementById('rolesError').classList.toggle('d-block', missingRole);
            (missingSlt ? sltEmployee.closest('.dropdown').querySelector('.cu-dropdown-toggle') : (missingLocation ? document.getElementById('location').closest('.dropdown').querySelector('.cu-dropdown-toggle') : document.getElementById('rolesDropdown'))).focus();
            return;
        }

        document.querySelectorAll('.js-extra-permission').forEach((checkbox) => { checkbox.disabled = false; });
    });

    bindSelectDropdowns();
    setSltEmployeeMode();
    refreshPermissions();
</script>
@endpush
@endsection
