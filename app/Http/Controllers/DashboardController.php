<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboards) {}

    public function index(): View
    {
        $user = auth()->user()->loadMissing('role');

        if ($user->hasRole('slt_employee')) {
            return view('dashboards.slt-employee', $this->dashboards->sltHub($user));
        }

        return view('dashboards.show', $this->dashboards->for($user));
    }
}
