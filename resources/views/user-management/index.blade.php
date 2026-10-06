@extends('layouts.app')

@section('title', 'User Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=3">
@endpush

@section('content')
<style>
    .user-management-page { --um-blue: #1769c2; --um-border: #dfe5ea; }
    .user-management-page .um-title { color: var(--um-blue); font-size: clamp(1.65rem, 2.5vw, 2.25rem); font-weight: 700; letter-spacing: -.04em; }
    .user-management-page .um-subtitle { color: #59636d; }
    .user-management-page .um-create { border-radius: .55rem; background: #1769c2; border-color: #1769c2; box-shadow: 0 .25rem .75rem rgba(23,105,194,.2); padding: .72rem 1.25rem; }
    .user-management-page .um-stat { min-height: 160px; background: rgba(255,255,255,.97); border: 1px solid var(--um-border); border-top: 4px solid var(--stat-color); border-radius: .9rem; box-shadow: 0 .25rem .8rem rgba(24,39,75,.08); }
    .user-management-page .um-stat-icon { width: 45px; height: 45px; border-radius: .65rem; display: inline-flex; align-items: center; justify-content: center; background: var(--stat-icon-bg); color: var(--stat-color); font-size: 1.4rem; }
    .user-management-page .um-stat-label { color: #4f555b; font-size: .92rem; }.user-management-page .um-stat-number { font-size: 2rem; font-weight: 700; line-height: 1.05; }
    .user-management-page .um-panel { border: 1px solid var(--um-border) !important; border-radius: .9rem; overflow: hidden; box-shadow: 0 .3rem 1rem rgba(24,39,75,.09) !important; background: rgba(255,255,255,.98); }
    .user-management-page .um-header {background: #fff; border: 1px solid var(--um-border); border-radius: .9rem; padding: 1.5rem 1.75rem; box-shadow: 0 .3rem 1rem rgba(24,39,75,.09);}
    .user-management-page .um-toolbar { border-bottom: 1px solid var(--um-border); }
    .user-management-page .um-search { width: 280px; min-width: 280px; }
    .user-management-page .form-control, .user-management-page .form-select { border-color: #cbd3da; min-height: 42px; }.user-management-page .input-group-text { background: #fff; border-color: #cbd3da; color: #717980; }
    .user-management-page .table { --bs-table-hover-bg: #f5f9fd; }.user-management-page .table thead th { background: #f4f6f8; color: #505860; font-size: .73rem; letter-spacing: .03em; font-weight: 700; padding: 1.05rem 1.25rem; white-space: nowrap; border-bottom-width: 1px; }.user-management-page .table tbody td { padding: 1.1rem 1.25rem; border-color: var(--um-border); color: #4d5358; }
    .user-management-page .badge { border-radius: 99px; padding: .4rem .65rem; }.user-management-page .btn-sm { border-radius: .45rem; }.user-management-page .card-footer { border-top-color: var(--um-border); }
    .user-management-page .pagination { margin-bottom: 0; }
    .user-management-page .card-footer nav > div.d-none > div:first-child { display: none; }
    .user-management-page .table-responsive { min-height: 0; }
    .user-management-page .um-access { min-width: 9rem; }
    .user-management-page .cu-dropdown-toggle { min-height: 42px; min-width: 10.5rem; }
    @media (max-width: 767.98px) {
        .user-management-page .um-search,
        .user-management-page .cu-dropdown-toggle { width: 100%; min-width: 0; }
    }
</style>
<div class="container-fluid mt-4 user-management-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 um-header">
        <div>
            <h1 class="um-title mb-1">User Management</h1>
            <p class="um-subtitle mb-0">Control access, roles, and profiles for Nebula RRS users.</p>
        </div>
        @if(auth()->user()->hasPermission('user.create'))
            <a href="{{ route('create.user') }}" class="btn btn-primary um-create"><i class="ti ti-user-plus me-2"></i>Create User</a>
        @endif
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-4"><div class="um-stat p-4" style="--stat-color:#1769c2;--stat-icon-bg:#e8f0fa"><div class="d-flex justify-content-between"><span class="um-stat-icon"><i class="ti ti-users"></i></span><small class="text-muted">All accounts</small></div><div class="um-stat-label mt-3">Total Users</div><div class="um-stat-number">{{ number_format($statistics['total']) }}</div></div></div>
        <div class="col-12 col-sm-6 col-xl-4"><div class="um-stat p-4" style="--stat-color:#087da4;--stat-icon-bg:#e6f4f7"><div class="d-flex justify-content-between"><span class="um-stat-icon"><i class="ti ti-shield-check"></i></span><small class="text-muted">Active now</small></div><div class="um-stat-label mt-3">Active Users</div><div class="um-stat-number">{{ number_format($statistics['active']) }}</div></div></div>
        <div class="col-12 col-sm-6 col-xl-4"><div class="um-stat p-4" style="--stat-color:#737b80;--stat-icon-bg:#f0f1f2"><div class="d-flex justify-content-between"><span class="um-stat-icon"><i class="ti ti-user-off"></i></span><small class="text-muted">Access disabled</small></div><div class="um-stat-label mt-3">Inactive Users</div><div class="um-stat-number">{{ number_format($statistics['inactive']) }}</div></div></div>
    </div>

    <div class="card border-0 shadow-sm um-panel">
        <form method="GET" action="{{ route('user.management') }}" class="um-toolbar p-4">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="input-group um-search me-auto"><span class="input-group-text border-end-0"><i class="ti ti-search"></i></span><input class="form-control border-start-0 ps-0" name="search" value="{{ request('search') }}" placeholder="Search by name, ID or email..." aria-label="Search users"></div>
                <div class="dropdown cu-select" data-cu-select>
                    <input type="hidden" name="location" value="{{ request('location') }}">
                    <button class="btn cu-dropdown-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-label="Filter by location">
                        <span class="js-select-label">{{ request('location') ?: 'All Locations' }}</span>
                    </button>
                    <ul class="dropdown-menu cu-dropdown-menu">
                        <li><button type="button" class="dropdown-item" data-value="" data-label="All Locations">All Locations</button></li>
                        @foreach($locations as $location)
                            <li><button type="button" class="dropdown-item text-wrap" data-value="{{ $location }}" data-label="{{ $location }}">{{ $location }}</button></li>
                        @endforeach
                    </ul>
                </div>
                <div class="dropdown cu-select" data-cu-select>
                    <input type="hidden" name="role" value="{{ request('role') }}">
                    <button class="btn cu-dropdown-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-label="Filter by role">
                        <span class="js-select-label">{{ optional($roles->firstWhere('id', (int) request('role')))->name ?? 'All Roles' }}</span>
                    </button>
                    <ul class="dropdown-menu cu-dropdown-menu">
                        <li><button type="button" class="dropdown-item" data-value="" data-label="All Roles">All Roles</button></li>
                        @foreach($roles as $role)
                            <li><button type="button" class="dropdown-item" data-value="{{ $role->id }}" data-label="{{ $role->name }}">{{ $role->name }}</button></li>
                        @endforeach
                    </ul>
                </div>
                <div class="dropdown cu-select" data-cu-select>
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <button class="btn cu-dropdown-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-label="Filter by status">
                        <span class="js-select-label">{{ request('status') === 'active' ? 'Active' : (request('status') === 'inactive' ? 'Inactive' : 'All Statuses') }}</span>
                    </button>
                    <ul class="dropdown-menu cu-dropdown-menu">
                        <li><button type="button" class="dropdown-item" data-value="" data-label="All Statuses">All Statuses</button></li>
                        <li><button type="button" class="dropdown-item" data-value="active" data-label="Active">Active</button></li>
                        <li><button type="button" class="dropdown-item" data-value="inactive" data-label="Inactive">Inactive</button></li>
                    </ul>
                </div>
                <button class="btn btn-outline-primary px-3" type="submit"><i class="ti ti-adjustments-horizontal me-1"></i>Filter</button>
                @if(request()->hasAny(['search', 'location', 'role', 'status']))<a class="btn btn-link text-decoration-none" href="{{ route('user.management') }}">Clear</a>@endif
            </div>
        </form>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>NIC / Employee ID</th>
                            <th>Email</th>
                            <th>Location</th>
                            <th>Role</th>
                            <th>Access</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $managedUser)
                            <tr>
                                <td class="fw-semibold text-dark"><i class="ti ti-user-circle text-primary me-2"></i>{{ $managedUser->name }}</td>
                                <td>{{ $managedUser->nic ?: '—' }}</td>
                                <td>{{ $managedUser->email }}</td>
                                <td>{{ $managedUser->location ?: '—' }}</td>
                                <td><span class="badge text-bg-primary-subtle text-primary">{{ strtoupper($managedUser->role->name ?? $managedUser->user_role ?? 'Unassigned') }}</span></td>
                                <td class="um-access">
                                    @php
                                        $granted = $managedUser->grantedPermissionSlugs();
                                    @endphp
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            {{ count($granted) }} permissions
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end cu-dropdown-menu">
                                            @if(count($granted) === 0)
                                                <div class="dropdown-item-text text-muted">No permissions assigned</div>
                                            @else
                                                @foreach($permissionGroups as $group)
                                                    @php
                                                        $actions = collect($group['actions'])->filter(fn ($action) => in_array($action['slug'], $granted, true));
                                                    @endphp
                                                    @continue($actions->isEmpty())
                                                    <div class="dropdown-item-text small">{{ $group['section'] }}: {{ $actions->pluck('action')->join(', ') }}</div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($managedUser->trashed())
                                        <span class="badge text-bg-danger">Deleted</span>
                                    @elseif($managedUser->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @unless($managedUser->trashed())

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUserModal"

                                            data-user-id="{{ $managedUser->id }}"
                                            data-user-name="{{ $managedUser->name }}"
                                            data-user-nic="{{ $managedUser->nic }}"
                                            data-user-email="{{ $managedUser->email }}"
                                            data-user-phone="{{ $managedUser->phone }}"
                                            data-user-location="{{ $managedUser->location }}"
                                            data-user-designation="{{ $managedUser->designation }}"
                                            data-user-service-id="{{ $managedUser->service_id }}"
                                            data-user-role="{{ $managedUser->role?->slug }}"
                                            data-user-extras="{{ e(json_encode($managedUser->relationLoaded('extraPermissions') ? $managedUser->extraPermissions->pluck('slug')->values() : [])) }}">

                                            <i class="ti ti-edit me-1"></i>Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#resetPasswordModal"
                                            data-reset-url="{{ route('users.reset-password', $managedUser) }}"
                                            data-user-name="{{ $managedUser->name }}">
                                            Reset password
                                        </button>
                                        <form method="POST" action="{{ route('users.toggle-active', $managedUser) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                {{ $managedUser->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger d-block ms-auto mt-1" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-delete-url="{{ route('users.destroy', $managedUser) }}" data-user-name="{{ $managedUser->name }}">
                                            <i class="ti ti-trash me-1"></i>Delete
                                        </button>
                                    @else
                                        <span class="text-muted">No actions available</span>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 px-3 px-md-4">
            <small class="text-muted">Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }} users</small>
            @if($users->hasPages())
                <div>{{ $users->onEachSide(1)->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    Edit User
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                </button>
            </div>

            <form method="POST" id="editUserForm">
                @csrf
                @method('PUT')

                <div class="modal-body">

                    <div class="row g-3">

                        <!-- Name -->
                        <div class="col-md-6">
                            <label for="editName" class="form-label">
                                Name
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="editName"
                                name="name"
                                required>
                        </div>

                        <!-- NIC -->
                        <div class="col-md-6">
                            <label for="editNic" class="form-label">
                                NIC
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="editNic"
                                name="nic"
                                required>
                        </div>

                        <!-- Employee ID -->
                        <div class="col-md-6">
                            <label for="editServiceId" class="form-label">
                                Employee ID
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="editServiceId"
                                name="service_id">
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="editEmail" class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                id="editEmail"
                                name="email"
                                required>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label for="editPhone" class="form-label">
                                Phone
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="editPhone"
                                name="phone"
                                required>
                        </div>

                        <!-- Location -->
                        <div class="col-md-6">
                            <label for="editLocation" class="form-label">
                                Location
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="editLocation"
                                name="location"
                                required>
                        </div>

                        <!-- Designation -->
                        <div class="col-md-6">
                            <label for="editDesignation" class="form-label">
                                Designation
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="editDesignation"
                                name="designation">
                        </div>

                        <!-- Role -->
                        <div class="col-md-6">
                            <label for="editRole" class="form-label">
                                Role
                            </label>
                            <div class="dropdown cu-select" data-cu-select>
                                <input type="hidden" name="user_role" id="editRole" required>
                                <button class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="js-select-label" id="editRoleLabel">Select role</span>
                                </button>
                                <ul class="dropdown-menu cu-dropdown-menu w-100">
                                    @foreach($roles as $role)
                                        <li>
                                            <button type="button" class="dropdown-item js-edit-role-option" data-value="{{ $role->slug }}" data-label="{{ $role->name }}">
                                                {{ $role->name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="editPermissionsDropdown">Permissions</label>
                            <p class="small text-muted mb-2">Role access is locked; extra access can be granted to this user only.</p>
                            <div class="dropdown">
                                <button id="editPermissionsDropdown" class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <span id="editPermissionsSummary" class="text-muted">Select extra permissions</span>
                                </button>
                                <div class="dropdown-menu cu-dropdown-menu w-100">
                                    @foreach($permissionGroups as $group)
                                        <h6 class="dropdown-header">{{ $group['section'] }}</h6>
                                        @foreach($group['actions'] as $action)
                                            <label class="dropdown-item cu-check-item js-edit-perm-row" data-permission="{{ $action['slug'] }}">
                                                <input type="checkbox" name="extra_permissions[]" value="{{ $action['slug'] }}" class="form-check-input mt-0 js-edit-extra-permission">
                                                <span>{{ $action['action'] }}</span>
                                                <small class="js-edit-perm-source">Off</small>
                                            </label>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>
                        Save Changes
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="resetPasswordForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset user password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Set a new password for <strong id="resetPasswordUserName"></strong>. It will be emailed to the user.</p>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New password</label>
                        <input type="password" class="form-control" id="newPassword" name="password" minlength="8" autocomplete="new-password" required>
                        <div class="form-text">Must be at least 8 characters.</div>
                    </div>
                    <div>
                        <label for="newPasswordConfirmation" class="form-label">Confirm new password</label>
                        <input type="password" class="form-control" id="newPasswordConfirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save and email password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Confirm user deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to permanently delete <strong id="deleteUserName"></strong>?</p>
                <small class="text-danger">This action cannot be undone.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteUserForm">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="ti ti-trash me-1"></i>Yes, delete user</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/cu-dropdowns.js') }}?v=2"></script>
<script>

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
                });
            });
        });
    }

    const editUserModal = document.getElementById('editUserModal');
    const editUserForm = document.getElementById('editUserForm');
    const rolePermissions = @json($rolePermissions);

    function updateEditPermissionSummary() {
        const granted = document.querySelectorAll('.js-edit-extra-permission:checked').length;
        const extra = [...document.querySelectorAll('.js-edit-extra-permission')]
            .filter((checkbox) => checkbox.checked && !checkbox.disabled).length;
        const summary = document.getElementById('editPermissionsSummary');
        if (!granted) {
            summary.textContent = 'Select extra permissions';
            summary.classList.add('text-muted');
            return;
        }
        summary.textContent = extra
            ? `${granted} selected (${extra} extra)`
            : `${granted} from selected role`;
        summary.classList.remove('text-muted');
    }

    function refreshEditPermissions(roleSlug, extras) {
        const granted = new Set(rolePermissions[roleSlug] || []);
        const extraSet = new Set(extras || []);

        document.querySelectorAll('.js-edit-perm-row').forEach((row) => {
            const slug = row.dataset.permission;
            const checkbox = row.querySelector('.js-edit-extra-permission');
            const source = row.querySelector('.js-edit-perm-source');
            const fromRole = granted.has(slug);

            checkbox.checked = fromRole || extraSet.has(slug);
            checkbox.disabled = fromRole;
            row.classList.toggle('is-granted', fromRole);
            row.classList.toggle('is-extra', !fromRole && checkbox.checked);
            source.textContent = fromRole
                ? 'Role'
                : (checkbox.checked ? 'Extra' : 'Off');
        });

        updateEditPermissionSummary();
    }

    editUserModal.addEventListener('show.bs.modal', (event) => {

        const button = event.relatedTarget;

        const userId = button.dataset.userId;
        const name = button.dataset.userName;
        const nic = button.dataset.userNic;
        const email = button.dataset.userEmail;
        const phone = button.dataset.userPhone;
        const location = button.dataset.userLocation;
        const designation = button.dataset.userDesignation;
        const serviceId = button.dataset.userServiceId;
        const roleSlug = button.dataset.userRole;
        const extras = JSON.parse(button.dataset.userExtras || '[]');

        document.getElementById('editName').value = name || '';
        document.getElementById('editNic').value = nic || '';
        document.getElementById('editEmail').value = email || '';
        document.getElementById('editPhone').value = phone || '';
        document.getElementById('editLocation').value = location || '';
        document.getElementById('editDesignation').value = designation || '';
        document.getElementById('editServiceId').value = serviceId || '';

        document.getElementById('editRole').value = roleSlug || '';
        const roleOption = document.querySelector(`.js-edit-role-option[data-value="${roleSlug}"]`);
        const roleLabel = document.getElementById('editRoleLabel');
        roleLabel.textContent = roleOption?.dataset.label || 'Select role';
        roleLabel.classList.toggle('text-muted', !roleSlug);
        refreshEditPermissions(roleSlug, extras);

        editUserForm.action = `/user-management/${userId}`;

    });

    document.getElementById('editRole').addEventListener('change', (event) => {
        const extras = [...document.querySelectorAll('.js-edit-extra-permission')]
            .filter((checkbox) => checkbox.checked && !checkbox.disabled)
            .map((checkbox) => checkbox.value);
        refreshEditPermissions(event.target.value, extras);
    });

    document.querySelectorAll('.js-edit-extra-permission').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const extras = [...document.querySelectorAll('.js-edit-extra-permission')]
                .filter((item) => item.checked && !item.disabled)
                .map((item) => item.value);
            refreshEditPermissions(document.getElementById('editRole').value, extras);
        });
    });

    editUserForm.addEventListener('submit', () => {
        document.querySelectorAll('.js-edit-extra-permission').forEach((checkbox) => { checkbox.disabled = false; });
    });


    const deleteUserModal = document.getElementById('deleteUserModal');
    const deleteUserForm = document.getElementById('deleteUserForm');
    const deleteUserName = document.getElementById('deleteUserName');

    deleteUserModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        deleteUserForm.action = button.dataset.deleteUrl;
        deleteUserName.textContent = button.dataset.userName;
    });

    const resetPasswordModal = document.getElementById('resetPasswordModal');
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    const resetPasswordUserName = document.getElementById('resetPasswordUserName');

    resetPasswordModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        resetPasswordForm.action = button.dataset.resetUrl;
        resetPasswordUserName.textContent = button.dataset.userName;
        resetPasswordForm.reset();
    });

    bindSelectDropdowns();
</script>
@endpush
@endsection
