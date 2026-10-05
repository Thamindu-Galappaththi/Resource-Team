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

        return view('dashboards.show', $this->dashboards->for($user));
    }
}
