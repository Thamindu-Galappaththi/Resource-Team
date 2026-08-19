<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function create(): View
    {
        return view('user-management.create-user');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slt_employee' => ['required', 'in:yes,no'],
            'name' => ['required', 'string', 'max:50'],
            'service_id' => ['nullable', 'required_if:slt_employee,yes', 'string', 'max:20'],
            'nic' => ['required', 'string', 'min:12', 'max:12', 'unique:users,nic'],
            'email' => ['required', 'email', 'max:50', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'location' => ['required', 'string', 'max:100'],
            'user_role' => ['required', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'service_id' => $validated['service_id'] ?? null,
            'slt_employee' => $validated['slt_employee'] === 'yes',
            'nic' => $validated['nic'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'location' => $validated['location'],
            // New accounts use the NIC as their initial password.
            'password' => Hash::make($validated['nic']),
            'user_role' => $validated['user_role'],
        ]);

        return redirect()
            ->route('create.user')
            ->with('status', 'User created successfully for '.$user->name.'.');
    }

    public function lookupSltEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:20'],
        ]);

        $employee = User::query()
            ->where('service_id', $validated['employee_id'])
            ->where('slt_employee', true)
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
