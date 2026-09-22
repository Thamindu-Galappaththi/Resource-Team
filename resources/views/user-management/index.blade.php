@extends('layouts.app')

@section('title', 'User Management')

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
    .user-management-page .um-toolbar { border-bottom: 1px solid var(--um-border); }.user-management-page .um-search { min-width: min(100%, 360px); }
    .user-management-page .form-control, .user-management-page .form-select { border-color: #cbd3da; min-height: 42px; }.user-management-page .input-group-text { background: #fff; border-color: #cbd3da; color: #717980; }
    .user-management-page .table { --bs-table-hover-bg: #f5f9fd; }.user-management-page .table thead th { background: #f4f6f8; color: #505860; font-size: .73rem; letter-spacing: .03em; font-weight: 700; padding: 1.05rem 1.25rem; white-space: nowrap; border-bottom-width: 1px; }.user-management-page .table tbody td { padding: 1.1rem 1.25rem; border-color: var(--um-border); color: #4d5358; }
    .user-management-page .badge { border-radius: 99px; padding: .4rem .65rem; }.user-management-page .btn-sm { border-radius: .45rem; }.user-management-page .card-footer { border-top-color: var(--um-border); }
    @media (max-width: 767.98px) { .user-management-page .table thead th, .user-management-page .table tbody td { padding-left: .8rem; padding-right: .8rem; } }
</style>
<div class="container-fluid mt-4 user-management-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
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
                <select class="form-select w-auto" name="location" aria-label="Filter by location"><option value="">All Locations</option>@foreach($locations as $location)<option value="{{ $location }}" @selected(request('location') === $location)>{{ $location }}</option>@endforeach</select>
                <select class="form-select w-auto" name="role" aria-label="Filter by role"><option value="">All Roles</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected((string) request('role') === (string) $role->id)>{{ $role->name }}</option>@endforeach</select>
                <select class="form-select w-auto" name="status" aria-label="Filter by status"><option value="">All Statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select>
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
                            <th>Status</th>
                            <th>Deleted At</th>
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
                                <td>
                                    @if($managedUser->trashed())
                                        <span class="badge text-bg-danger">Deleted</span>
                                    @elseif($managedUser->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $managedUser->deleted_at?->format('M d, Y h:i A') ?? '—' }}</td>
                                <td class="text-end">
                                    @unless($managedUser->trashed())
                                        <form method="POST" action="{{ route('users.reset-password', $managedUser) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Reset password</button>
                                        </form>
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
            @if($users->hasPages()){{ $users->links() }}@endif
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
<script>
    const deleteUserModal = document.getElementById('deleteUserModal');
    const deleteUserForm = document.getElementById('deleteUserForm');
    const deleteUserName = document.getElementById('deleteUserName');

    deleteUserModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        deleteUserForm.action = button.dataset.deleteUrl;
        deleteUserName.textContent = button.dataset.userName;
    });
</script>
@endpush
@endsection
