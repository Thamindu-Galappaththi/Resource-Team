<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Role-based dashboard shell. Widgets are placeholders from config/rbac.php
     * until each dashboard is fully implemented.
     */
    public function index(): View
    {
        $user = auth()->user()->loadMissing('role');
        $role = $user->role;
        $dashboard = $role?->dashboard() ?? [
            'title' => 'Dashboard',
            'subtitle' => 'No dashboard is assigned for this role yet.',
            'widgets' => [],
        ];

        return view('dashboards.show', [
            'user' => $user,
            'role' => $role,
            'dashboard' => $dashboard,
        ]);
    }
}
