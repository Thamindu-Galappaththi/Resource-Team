<div class="um-table-wrap">
    <table class="table table-striped table-hover align-middle mb-0 um-table">
        <thead>
            <tr>
                <th class="um-col-id">ID</th>
                <th>Name</th>
                <th>NIC / ID</th>
                <th>Email</th>
                <th>Location</th>
                <th>Role</th>
                <th>Access</th>
                <th>Status</th>
                <th class="um-col-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $managedUser)
                @php
                    $granted = $managedUser->grantedPermissionSlugs();
                    $extras = $managedUser->relationLoaded('extraPermissions')
                        ? $managedUser->extraPermissions->pluck('slug')->values()
                        : collect();
                    $assignedRoles = $managedUser->assignedRoles();
                    $roleSlugs = $assignedRoles->pluck('slug')->filter()->values();
                    $roleNames = $assignedRoles->pluck('name')->filter()->values();
                    if ($roleNames->isEmpty()) {
                        $roleNames = collect([$managedUser->user_role ?? 'Unassigned']);
                    }
                    $permissionLabels = [];
                    foreach ($permissionGroups as $group) {
                        $actions = collect($group['actions'])->filter(fn ($action) => in_array($action['slug'], $granted, true));
                        if ($actions->isNotEmpty()) {
                            $permissionLabels[] = $group['section'].': '.$actions->pluck('action')->join(', ');
                        }
                    }
                    $userPayload = [
                        'id' => $managedUser->id,
                        'name' => $managedUser->name,
                        'nic' => $managedUser->nic,
                        'email' => $managedUser->email,
                        'phone' => $managedUser->phone,
                        'location' => $managedUser->location,
                        'designation' => $managedUser->designation,
                        'service_id' => $managedUser->service_id,
                        'slt_employee' => $managedUser->slt_employee ? 'yes' : 'no',
                        'role_slugs' => $roleSlugs->values()->all(),
                        'role_names' => $roleNames->values()->all(),
                        'extras' => $extras->values()->all(),
                        'permissions' => $permissionLabels,
                        'is_active' => (bool) $managedUser->is_active,
                        'update_url' => route('users.update', $managedUser),
                        'toggle_url' => route('users.toggle-active', $managedUser),
                        'delete_url' => route('users.destroy', $managedUser),
                        'reset_url' => route('users.reset-password', $managedUser),
                        'resend_setup_url' => route('users.resend-password-setup', $managedUser),
                        'has_set_password' => $managedUser->hasSetPassword(),
                    ];
                @endphp
                <tr class="um-row" data-user="{{ json_encode($userPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">
                    <td class="um-col-id">{{ $managedUser->id }}</td>
                    <td>
                        <div class="um-name" title="{{ $managedUser->name }}">{{ \Illuminate\Support\Str::limit($managedUser->name, 30) }}</div>
                        @if($managedUser->designation)
                            <div class="um-meta">{{ $managedUser->designation }}</div>
                        @endif
                    </td>
                    <td>
                        <div>{{ $managedUser->nic ?: '—' }}</div>
                        @if($managedUser->service_id)
                            <div class="um-meta">{{ $managedUser->service_id }}</div>
                        @endif
                    </td>
                    <td>{{ $managedUser->email }}</td>
                    <td>{{ $managedUser->location ?: '—' }}</td>
                    <td>
                        <span class="badge text-bg-primary-subtle text-primary um-role">{{ $roleNames->join(', ') }}</span>
                    </td>
                    <td class="um-access">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light um-perm-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ count($granted) }}
                            </button>
                            <div class="dropdown-menu dropdown-menu-end um-perm-menu">
                                @if(count($granted) === 0)
                                    <div class="dropdown-item-text text-muted">No permissions assigned</div>
                                @else
                                    @foreach($permissionGroups as $group)
                                        @php
                                            $actions = collect($group['actions'])->filter(fn ($action) => in_array($action['slug'], $granted, true));
                                        @endphp
                                        @continue($actions->isEmpty())
                                        <div class="um-perm-group">
                                            <div class="um-perm-section">{{ $group['section'] }}</div>
                                            <div>{{ $actions->pluck('action')->join(', ') }}</div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($managedUser->trashed())
                            <span class="badge rounded-pill text-bg-danger">Deleted</span>
                        @elseif($managedUser->is_active)
                            <span class="badge rounded-pill text-bg-success">Active</span>
                        @else
                            <span class="badge rounded-pill text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="um-col-actions">
                        @unless($managedUser->trashed())
                            <div class="um-actions">
                                <button type="button" class="um-icon-btn js-view-user" title="View" aria-label="View {{ $managedUser->name }}" data-bs-toggle="modal" data-bs-target="#viewUserModal">
                                    <i class="ti ti-eye"></i>
                                </button>
                                <button type="button" class="um-icon-btn js-edit-user" title="Edit" aria-label="Edit {{ $managedUser->name }}" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                    <i class="ti ti-pencil"></i>
                                </button>
                                <button type="button" class="um-icon-btn js-resend-setup" title="{{ $managedUser->hasSetPassword() ? 'Password already set' : 'Send password setup email' }}" aria-label="{{ $managedUser->hasSetPassword() ? 'Password already set for '.$managedUser->name : 'Send password setup email to '.$managedUser->name }}" @disabled($managedUser->hasSetPassword())>
                                    <i class="ti ti-mail"></i>
                                </button>
                                <button type="button" class="um-icon-btn js-reset-user" title="Reset password" aria-label="Reset password for {{ $managedUser->name }}" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                                    <i class="ti ti-key"></i>
                                </button>
                                <button type="button" class="um-icon-btn js-toggle-user" title="{{ $managedUser->is_active ? 'Deactivate' : 'Activate' }}" aria-label="{{ $managedUser->is_active ? 'Deactivate' : 'Activate' }} {{ $managedUser->name }}">
                                    <i class="ti {{ $managedUser->is_active ? 'ti-user-off' : 'ti-user-check' }}"></i>
                                </button>
                                <button type="button" class="um-icon-btn is-danger js-delete-user" title="Delete" aria-label="Delete {{ $managedUser->name }}">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">No users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 px-3 px-md-4">
    <small class="text-muted">Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }} users</small>
    @if($users->hasPages())
        <div class="um-pagination">{{ $users->onEachSide(1)->links() }}</div>
    @endif
</div>
