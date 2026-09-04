@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">User Management</h4>
            <p class="text-muted mb-0">Create, activate, and reset access for Resource Reservation System users.</p>
        </div>
        @if(auth()->user()->hasPermission('user.create'))
            <a href="{{ route('create.user') }}" class="btn btn-primary">Create User</a>
        @endif
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>NIC / Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $managedUser)
                            <tr>
                                <td class="fw-semibold">{{ $managedUser->name }}</td>
                                <td>{{ $managedUser->nic ?: '—' }}</td>
                                <td>{{ $managedUser->email }}</td>
                                <td>{{ $managedUser->role->name ?? $managedUser->user_role }}</td>
                                <td>{{ $managedUser->location ?: '—' }}</td>
                                <td>
                                    @if($managedUser->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection
