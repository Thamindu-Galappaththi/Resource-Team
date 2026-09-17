<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with(['role', 'roles'])
            ->latest()
            ->paginate(15);

        return view('user-management.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('user-management.create-user', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slt_employee' => ['required', 'in:yes,no'],
            'name' => ['required', 'string', 'max:50'],
            'service_id' => ['nullable', 'required_if:slt_employee,yes', 'prohibited_unless:slt_employee,yes', 'string', 'max:20'],
            'nic' => ['required', 'string', 'size:12', 'unique:users,nic'],
            'email' => ['required', 'email', 'max:50', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'location' => ['required', 'string', 'max:100'],
            'designation' => ['nullable', 'string', 'max:100'],
            'user_roles' => ['required', 'array', 'min:1'],
            'user_roles.*' => ['required', 'string', 'distinct', Rule::exists('roles', 'slug')->where('is_active', true)],
        ]);

        $roles = Role::query()->whereIn('slug', $validated['user_roles'])->get();
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
            'password' => $validated['nic'],
            'role_id' => $primaryRole->id,
            'user_role' => $primaryRole->slug,
            'is_active' => true,
        ]);

        $user->roles()->sync($roles->modelKeys());

        return redirect()
            ->route('create.user')
            ->with('status', 'User account created successfully for '.$user->name.'.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['status' => 'You cannot change the status of your own account.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $message = $user->is_active
            ? 'User account activated successfully!'
            : 'User account deactivated successfully!';

        return back()->with('status', $message);
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $temporaryPassword = $user->nic ?: 'Password@123';
        $user->update(['password' => $temporaryPassword]);

        return back()->with('status', 'Password reset successfully! Temporary password is the user NIC.');
    }

    public function lookupSltEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:20'],
        ]);

        $employee = User::query()
            ->where('service_id', $validated['employee_id'])
            ->first();

        if (! $employee) {
            return response()->json(['message' => 'No SLT employee was found for that Employee ID.'], 404);
        }

        return response()->json([
            'name' => $employee->name,
            'nic' => $employee->nic,
            'email' => $employee->email,
            'phone' => $employee->phone,
        ]);
    }
}
