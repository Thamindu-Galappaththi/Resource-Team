@extends('layouts.app')

@section('title', 'User Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=13">
<link rel="stylesheet" href="{{ asset('css/user-management.css') }}?v=10">
@endpush

@section('content')
<div class="container-fluid mt-4 user-management-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 um-header">
        <div>
            <h1 class="um-title mb-1">User Management</h1>
            <p class="um-subtitle mb-0">Control access, roles, and profiles for Nebula RRS users.</p>
        </div>
        @if(auth()->user()->hasPermission('user.create'))
        <a href="{{ route('create.user') }}" class="btn btn-primary um-create"><i
                class="ti ti-user-plus me-2"></i>Create User</a>
        @endif
    </div>

    @if(session('status'))
    <div class="alert alert-success um-auto-alert">{{ session('status') }}</div>
    @endif
    @if($errors->has('status'))
    <div class="alert alert-danger um-auto-alert">{{ $errors->first('status') }}</div>
    @elseif($errors->any() && ! session('edit_user_id'))
    <div class="alert alert-danger um-auto-alert">{{ $errors->first() }}</div>
    @endif
    <div id="um-flash" class="alert um-auto-alert d-none" role="status"></div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="um-stat p-4" style="--stat-color:#1769c2;--stat-icon-bg:#e8f0fa">
                <div class="d-flex justify-content-between"><span class="um-stat-icon"><i
                            class="ti ti-users"></i></span><small class="text-muted">All accounts</small></div>
                <div class="um-stat-label mt-3">Total Users</div>
                <div class="um-stat-number" id="um-stat-total">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="um-stat p-4" style="--stat-color:#087da4;--stat-icon-bg:#e6f4f7">
                <div class="d-flex justify-content-between"><span class="um-stat-icon"><i
                            class="ti ti-shield-check"></i></span><small class="text-muted">Active now</small></div>
                <div class="um-stat-label mt-3">Active Users</div>
                <div class="um-stat-number" id="um-stat-active">{{ number_format($statistics['active']) }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="um-stat p-4" style="--stat-color:#737b80;--stat-icon-bg:#f0f1f2">
                <div class="d-flex justify-content-between"><span class="um-stat-icon"><i
                            class="ti ti-user-off"></i></span><small class="text-muted">Access disabled</small></div>
                <div class="um-stat-label mt-3">Inactive Users</div>
                <div class="um-stat-number" id="um-stat-inactive">{{ number_format($statistics['inactive']) }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm um-panel">
        <form method="GET" action="{{ route('user.management') }}" id="um-filters" class="um-toolbar p-3 p-md-4"
            data-url="{{ route('user.management') }}">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="input-group um-search">
                    <span class="input-group-text border-end-0"><i class="ti ti-search"></i></span>
                    <input class="form-control border-start-0 ps-0" id="um-search" name="search"
                        value="{{ request('search') }}" placeholder="Search by name, ID or email..."
                        aria-label="Search users" autocomplete="off">
                </div>
                <div class="dropdown cu-select" data-cu-select>
                    <input type="hidden" name="location" id="um-location" value="{{ request('location') }}">
                    <button class="btn cu-dropdown-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-label="Filter by location">
                        <span class="js-select-label">{{ request('location') ?: 'All Locations' }}</span>
                    </button>
                    <ul class="dropdown-menu cu-dropdown-menu">
                        <li><button type="button" class="dropdown-item" data-value="" data-label="All Locations">All
                                Locations</button></li>
                        @foreach($locations as $location)
                        <li><button type="button" class="dropdown-item" data-value="{{ $location }}"
                                data-label="{{ $location }}">{{ $location }}</button></li>
                        @endforeach
                    </ul>
                </div>
                <div class="dropdown cu-select" data-cu-select>
                    <input type="hidden" name="role" id="um-role" value="{{ request('role') }}">
                    <button class="btn cu-dropdown-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-label="Filter by role">
                        <span
                            class="js-select-label">{{ optional($roles->firstWhere('id', (int) request('role')))->name ?? 'All Roles' }}</span>
                    </button>
                    <ul class="dropdown-menu cu-dropdown-menu">
                        <li><button type="button" class="dropdown-item" data-value="" data-label="All Roles">All
                                Roles</button></li>
                        @foreach($roles as $role)
                        <li><button type="button" class="dropdown-item" data-value="{{ $role->id }}"
                                data-label="{{ $role->name }}">{{ $role->name }}</button></li>
                        @endforeach
                    </ul>
                </div>
                <div class="dropdown cu-select" data-cu-select>
                    <input type="hidden" name="status" id="um-status" value="{{ request('status') }}">
                    <button class="btn cu-dropdown-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-label="Filter by status">
                        <span
                            class="js-select-label">{{ request('status') === 'active' ? 'Active' : (request('status') === 'inactive' ? 'Inactive' : 'All Statuses') }}</span>
                    </button>
                    <ul class="dropdown-menu cu-dropdown-menu">
                        <li><button type="button" class="dropdown-item" data-value="" data-label="All Statuses">All
                                Statuses</button></li>
                        <li><button type="button" class="dropdown-item" data-value="active"
                                data-label="Active">Active</button></li>
                        <li><button type="button" class="dropdown-item" data-value="inactive"
                                data-label="Inactive">Inactive</button></li>
                    </ul>
                </div>
                <div class="um-toolbar-btns">
                    <button class="btn btn-outline-secondary px-3" type="button" id="um-clear" @disabled(!
                        request()->hasAny(['search', 'location', 'role', 'status']))>Clear</button>
                </div>
            </div>
        </form>
        <div id="um-results">
            @include('user-management._users-table')
        </div>
    </div>
</div>

<div class="modal fade um-modal cu-page" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="viewUserModalLabel">View user</h5>
                    <small class="text-muted">Account profile, roles, and access</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <section class="mb-4">
                    <h2 class="h5 mb-3">Employee details</h2>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">SLT employee</label>
                            <input type="text" class="form-control" id="viewSltEmployee" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" id="viewServiceId" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" id="viewName" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">NIC</label>
                            <input type="text" class="form-control" id="viewNic" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" id="viewEmail" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" id="viewPhone" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Designation</label>
                            <input type="text" class="form-control" id="viewDesignation" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" id="viewLocation" readonly>
                        </div>
                    </div>
                </section>
                <section>
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Roles</label>
                            <input type="text" class="form-control" id="viewRoles" readonly>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Permissions</label>
                            <div class="form-control um-view-permissions" id="viewPermissions" readonly></div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade um-modal cu-page" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="editUserModalLabel">Edit user</h5>
                    <small class="text-muted">Update profile, roles, and extra access</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editUserForm" class="um-modal-form">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <section class="mb-4">
                        <h2 class="h5 mb-3">Employee details</h2>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">SLT employee <span class="text-danger">*</span></label>
                                <div class="dropdown cu-select" data-cu-select>
                                    <input type="hidden" name="slt_employee" id="edit_slt_employee">
                                    <button class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="js-select-label text-muted" id="editSltLabel">Select an
                                            option</span>
                                    </button>
                                    <ul class="dropdown-menu cu-dropdown-menu">
                                        <li><button type="button" class="dropdown-item" data-value=""
                                                data-label="Select an option">Select an option</button></li>
                                        <li><button type="button" class="dropdown-item" data-value="yes"
                                                data-label="Yes">Yes</button></li>
                                        <li><button type="button" class="dropdown-item" data-value="no"
                                                data-label="No">No</button></li>
                                    </ul>
                                </div>
                                <div id="editSltEmployeeError" class="invalid-feedback">Select whether this person is an
                                    SLT employee.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="edit_service_id" class="form-label">Employee ID <span
                                        id="editEmployeeIdRequired" class="text-danger d-none">*</span></label>
                                <div class="input-group flex-nowrap">
                                    <input type="text" name="service_id" id="edit_service_id" class="form-control"
                                        placeholder="Enabled when SLT employee is Yes" inputmode="numeric"
                                        autocomplete="off" disabled>
                                    <button id="editLookupEmployee" class="btn btn-outline-primary cu-lookup d-none"
                                        type="button">Find employee</button>
                                </div>
                                <div id="editServiceIdError" class="invalid-feedback">Enter an employee ID.</div>
                                <small id="editLookupMessage" class="form-text"></small>
                            </div>
                            <div id="editEmployeeDetails" class="col-12 cu-details">
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="fw-semibold mb-2">Directory record</div>
                                    <div class="row g-2 small" id="editEmployeeDetailsBody"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="editName" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="editName" class="form-control"
                                    placeholder="Enter full name" required maxlength="100">
                                <div class="invalid-feedback" id="editNameError"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="editNic" class="form-label">NIC <span class="text-danger">*</span></label>
                                <input type="text" name="nic" id="editNic" class="form-control"
                                    placeholder="e.g. 962664303V or 199012345678" required maxlength="12">
                                <div class="invalid-feedback" id="editNicError"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="editEmail" class="form-label">Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" name="email" id="editEmail" class="form-control"
                                    placeholder="name@example.com" required>
                                <div class="invalid-feedback" id="editEmailError"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="editPhone" class="form-label">Phone <span
                                        class="text-danger">*</span></label>
                                <input type="tel" name="phone" id="editPhone" class="form-control"
                                    placeholder="Enter phone number" required>
                                <div class="invalid-feedback" id="editPhoneError"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="editDesignation" class="form-label">Designation</label>
                                <input type="text" name="designation" id="editDesignation" class="form-control"
                                    placeholder="Enter designation">
                                <div class="invalid-feedback" id="editDesignationError"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                <div class="dropdown cu-select" data-cu-select>
                                    <input type="hidden" name="location" id="editLocation" required>
                                    <button class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="js-select-label text-muted" id="editLocationLabel">Select
                                            location</span>
                                    </button>
                                    <ul class="dropdown-menu cu-dropdown-menu">
                                        <li><button type="button" class="dropdown-item" data-value=""
                                                data-label="Select location">Select location</button></li>
                                        @foreach($locations as $location)
                                        <li>
                                            <button type="button" class="dropdown-item js-edit-location-option"
                                                data-value="{{ $location }}" data-label="{{ $location }}">
                                                {{ $location }}
                                            </button>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div id="editLocationError" class="invalid-feedback">Select a location.</div>
                            </div>
                        </div>
                    </section>
                    <section>
                        <div class="row g-3 cu-align-fields">
                            <div class="col-12 col-lg-6">
                                <label class="form-label" for="editRolesDropdown">Roles <span
                                        class="text-danger">*</span></label>
                                <p class="text-muted small cu-field-hint">Select one or more roles. Permissions follow
                                    the chosen roles.</p>
                                <div class="dropdown">
                                    <button id="editRolesDropdown" class="btn cu-dropdown-toggle dropdown-toggle w-100"
                                        type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                        aria-expanded="false">
                                        <span id="editRolesSummary" class="text-muted">Select roles</span>
                                    </button>
                                    <div class="dropdown-menu cu-dropdown-menu">
                                        @foreach($roles->unique('id') as $role)
                                        <label class="dropdown-item cu-check-item">
                                            <input type="checkbox" name="user_roles[]" value="{{ $role->slug }}"
                                                class="form-check-input mt-0 js-edit-user-role">
                                            <span class="js-edit-role-name">{{ $role->name }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div id="editRolesError" class="invalid-feedback">Select at least one role.</div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <label class="form-label" for="editPermissionsDropdown">Permissions</label>
                                <p class="text-muted small cu-field-hint">Role access is locked. Extra access can be
                                    granted to this user only.</p>
                                <div class="dropdown">
                                    <button id="editPermissionsDropdown"
                                        class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                        <span id="editPermissionsSummary" class="text-muted">Select extra
                                            permissions</span>
                                    </button>
                                    <div class="dropdown-menu cu-dropdown-menu">
                                        @foreach($permissionGroups as $group)
                                        <h6 class="dropdown-header">{{ $group['section'] }}</h6>
                                        @foreach($group['actions'] as $action)
                                        <label class="dropdown-item cu-check-item js-edit-perm-row"
                                            data-permission="{{ $action['slug'] }}">
                                            <input type="checkbox" name="extra_permissions[]"
                                                value="{{ $action['slug'] }}"
                                                class="form-check-input mt-0 js-edit-extra-permission">
                                            <span>{{ $action['action'] }}</span>
                                            <small class="js-edit-perm-source">Off</small>
                                        </label>
                                        @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade um-modal cu-page" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="resetPasswordForm">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="resetPasswordModalLabel">Reset password</h5>
                        <small class="text-muted">The new password will be emailed to this user</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="um-reset-user">
                        <div class="um-reset-user-label">Account</div>
                        <div class="fw-semibold" id="resetPasswordUserName"></div>
                        <div class="text-muted small" id="resetPasswordUserEmail"></div>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New password</label>
                        <div class="input-group um-password-field">
                            <input type="password" class="form-control" id="newPassword" name="password" minlength="8"
                                autocomplete="new-password" required placeholder="At least 8 characters">
                            <button class="btn btn-outline-secondary um-password-toggle" type="button" data-password-toggle="newPassword" aria-label="Show password">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                        <div id="newPasswordError" class="invalid-feedback">Use at least 8 characters.</div>
                    </div>
                    <div>
                        <label for="newPasswordConfirmation" class="form-label">Re-enter password</label>
                        <div class="input-group um-password-field">
                            <input type="password" class="form-control" id="newPasswordConfirmation"
                                name="password_confirmation" minlength="8" autocomplete="new-password" required placeholder="Repeat the new password">
                            <button class="btn btn-outline-secondary um-password-toggle" type="button" data-password-toggle="newPasswordConfirmation" aria-label="Show password">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                        <div id="newPasswordConfirmationError" class="invalid-feedback">The passwords do not match.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="resetPasswordSubmit">Save and email password</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script>
window.UM = {
    rolePermissions: @json($rolePermissions),
    lookupUrl: @json(route('slt.employee.lookup')),
    editUpdateUrl: @json(session('edit_update_url')),
    oldInput: @json(session('edit_user_id') ? old() : null),
    errors: @json(session('edit_user_id') ? $errors->toArray() : []),
};
</script>
<script src="{{ asset('js/user-management.js') }}?v=11"></script>
@endpush
@endsection