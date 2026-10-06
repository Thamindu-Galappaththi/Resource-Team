<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AdminResetPasswordNotification;
use App\Rules\SriLankanNic;
use App\Services\SltEmployeeDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'integer', 'exists:roles,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $with = ['role.permissions', 'roles.permissions'];
        if (Schema::hasTable('permission_user')) {
            $with[] = 'extraPermissions';
        }

        $usersQuery = User::query()
            ->with($with)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('nic', 'like', "%{$search}%")
                        ->orWhere('service_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['location'] ?? null, function ($query, string $location) {
                $campus = trim((string) strrchr($location, '-')) ?: $location;
                $campus = ltrim($campus, '- ');
                $query->where(function ($locationQuery) use ($location, $campus) {
                    $locationQuery->where('location', $location)
                        ->orWhere('location', 'like', '%'.$campus.'%');
                });
            })
            ->when($filters['role'] ?? null, fn ($query, int $roleId) => $query->where('role_id', $roleId))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('is_active', $status === 'active'));

        $users = $usersQuery->latest('id')->paginate(10)->withQueryString();
        $permissionGroups = config('rbac.permission_groups', []);

        if ($request->ajax()) {
            return view('user-management._users-table', [
                'users' => $users,
                'permissionGroups' => $permissionGroups,
            ]);
        }

        $statistics = $this->userStatistics();
        $locations = $this->campusLocations();
        $roles = Role::query()->where('is_active', true)->with('permissions')->orderBy('sort_order')->get();

        return view('user-management.index', [
            'users' => $users,
            'statistics' => $statistics,
            'locations' => $locations,
            'roles' => $roles,
            'permissionGroups' => $permissionGroups,
            'rolePermissions' => $this->rolePermissionMap($roles),
        ]);
    }

    public function create(): View
    {
        $roles = Role::query()
            ->where('is_active', true)
            ->with('permissions')
            ->orderBy('sort_order')
            ->get();

        return view('user-management.create-user', [
            'roles' => $roles,
            'permissionGroups' => config('rbac.permission_groups', []),
            'rolePermissions' => $this->rolePermissionMap($roles),
            'locations' => $this->campusLocations(),
            'employeeLookupMock' => app(SltEmployeeDirectory::class)->usesMock(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateManagedUser($request);

        $roles = Role::query()
            ->with('permissions')
            ->whereIn('slug', $validated['user_roles'])
            ->get();
        $primaryRole = $roles->firstWhere('slug', $validated['user_roles'][0]);

        $user = User::create([
            'name' => $validated['name'],
            'service_id' => $validated['service_id'] ?? null,
            'slt_employee' => $validated['slt_employee'] === 'yes',
            'nic' => $validated['nic'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'location' => $validated['location'],
            'designation' => $validated['designation'] ?? null,
            'password' => Str::random(40),
            'role_id' => $primaryRole->id,
            'user_role' => $primaryRole->slug,
            'is_active' => true,
        ]);

        $user->roles()->sync($roles->modelKeys());
        $this->syncExtraPermissions($user, $roles, $validated['extra_permissions'] ?? []);

        $status = Password::sendResetLink([
            'email' => $user->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return redirect()
                ->route('create.user')
                ->withErrors([
                    'email' => 'The user account was created, but the password setup email could not be sent.',
                ]);
        }

        return redirect()
            ->route('create.user')
            ->with('status', 'User account created successfully. A password setup link was sent to '.$user->email.'.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        try {
            $validated = $this->validateManagedUser($request, $user);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('user.management')
                ->withErrors($exception->validator)
                ->withInput()
                ->with('edit_user_id', $user->id)
                ->with('edit_update_url', route('users.update', $user));
        }

        $roles = Role::query()
            ->with('permissions')
            ->whereIn('slug', $validated['user_roles'])
            ->where('is_active', true)
            ->get();
        $primaryRole = $roles->firstWhere('slug', $validated['user_roles'][0]);

        $user->update([
            'name' => $validated['name'],
            'service_id' => $validated['service_id'] ?? null,
            'slt_employee' => $validated['slt_employee'] === 'yes',
            'nic' => $validated['nic'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'location' => $validated['location'],
            'designation' => $validated['designation'] ?? null,
            'role_id' => $primaryRole->id,
            'user_role' => $primaryRole->slug,
        ]);

        $user->roles()->sync($roles->modelKeys());
        $this->syncExtraPermissions($user, $roles, $validated['extra_permissions'] ?? []);

        return redirect()
            ->route('user.management')
            ->with('status', 'User updated successfully!');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ($user->is(auth()->user())) {
            $message = 'You cannot change the status of your own account.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['status' => $message]);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $message = $user->is_active
            ? 'User account activated successfully!'
            : 'User account deactivated successfully!';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $message,
                'is_active' => $user->is_active,
                'statistics' => $this->userStatistics(),
            ]);
        }

        return back()->with('status', $message);
    }

    public function resetPassword(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'The passwords do not match.',
            'password.min' => 'Use at least 8 characters.',
        ]);

        $user->update(['password' => $validated['password']]);

        try {
            $user->notify(new AdminResetPasswordNotification($validated['password']));
        } catch (\Throwable $exception) {
            report($exception);

            $message = 'The password was updated, but the email could not be sent.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['password' => $message]);
        }

        $message = 'Password reset successfully and emailed to '.$user->email.'.';

        if ($request->expectsJson()) {
            return response()->json(['status' => $message]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ($user->is(auth()->user())) {
            $message = 'You cannot delete your own account.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('user.management')->withErrors(['status' => $message]);
        }

        $deletedUserName = $user->name;
        $user->delete();
        $message = 'User account deleted successfully for '.$deletedUserName.'.';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $message,
                'statistics' => $this->userStatistics(),
            ]);
        }

        return redirect()->route('user.management')->with('status', $message);
    }

    public function lookupSltEmployee(Request $request, SltEmployeeDirectory $directory): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:20'],
        ]);

        try {
            return response()->json($directory->lookup($validated['employee_id']));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateManagedUser(Request $request, ?User $user = null): array
    {
        $request->merge([
            'nic' => SriLankanNic::normalize($request->input('nic')),
        ]);

        return $request->validate([
            'slt_employee' => ['required', 'in:yes,no'],
            'name' => ['required', 'string', 'max:100'],
            'service_id' => ['nullable', 'required_if:slt_employee,yes', 'prohibited_unless:slt_employee,yes', 'string', 'max:20'],
            'nic' => [
                'required',
                'string',
                'max:12',
                new SriLankanNic,
                Rule::unique('users', 'nic')->ignore($user?->id),
            ],
            'email' => [
                'required',
                'email',
                'max:50',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'location' => ['required', 'string', 'max:100'],
            'designation' => ['nullable', 'string', 'max:100'],
            'user_roles' => ['required', 'array', 'min:1'],
            'user_roles.*' => ['required', 'string', 'distinct', Rule::exists('roles', 'slug')->where('is_active', true)],
            'extra_permissions' => ['nullable', 'array'],
            'extra_permissions.*' => ['string', Rule::exists('permissions', 'slug')],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Role>  $roles
     * @param  array<int, string>  $extraSlugs
     */
    private function syncExtraPermissions(User $user, $roles, array $extraSlugs): void
    {
        if (! Schema::hasTable('permission_user')) {
            return;
        }

        $grantedByRoles = $roles->flatMap(function (Role $role) {
            if (in_array($role->slug, ['developer', 'super_admin'], true)) {
                return array_keys(config('rbac.permissions', []));
            }

            return $role->permissions->pluck('slug');
        })->unique()->all();

        $extraIds = Permission::query()
            ->whereIn('slug', $extraSlugs)
            ->whereNotIn('slug', $grantedByRoles)
            ->pluck('id');

        $user->extraPermissions()->sync($extraIds);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Role>  $roles
     * @return array<string, list<string>>
     */
    private function rolePermissionMap($roles): array
    {
        $allPermissionSlugs = array_keys(config('rbac.permissions', []));

        return $roles->mapWithKeys(function (Role $role) use ($allPermissionSlugs) {
            if (in_array($role->slug, ['developer', 'super_admin'], true)) {
                return [$role->slug => $allPermissionSlugs];
            }

            $fromRole = $role->permissions->pluck('slug');
            $fromConfig = collect(config('rbac.role_permissions.'.$role->slug, []))
                ->reject(fn (string $slug) => $slug === '*');

            return [$role->slug => $fromRole->merge($fromConfig)->unique()->values()->all()];
        })->all();
    }

    /**
     * @return array{total: int, active: int, inactive: int}
     */
    private function userStatistics(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];
    }

    /**
     * @return list<string>
     */
    private function campusLocations(): array
    {
        return [
            'Nebula Institute of Technology - Welisara',
            'Nebula Institute of Technology - Moratuwa',
            'Nebula Institute of Technology - Peradeniya',
        ];
    }
}
