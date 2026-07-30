<?php

namespace App\Http\Controllers;

use App\Models\User;
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
            'name' => ['required', 'string', 'max:50'],
            'service_id' => ['nullable', 'string', 'max:20'],
            'nic' => ['required', 'string', 'min:12', 'max:12', 'unique:users,nic'],
            'email' => ['required', 'email', 'max:50', 'unique:users,email'],
            'location' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'designation' => ['nullable', 'string', 'max:20'],
            'user_type' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'service_id' => $validated['service_id'] ?? null,
            'nic' => $validated['nic'],
            'email' => $validated['email'],
            'location' => $validated['location'] ?? null,
            'password' => Hash::make($validated['password']),
            'designation' => $validated['designation'] ?? null,
            'user_type' => $validated['user_type'] ?? null,
        ]);

        return redirect()
            ->route('create.user')
            ->with('status', 'User created successfully for ' . $user->name . '.');
    }
}
