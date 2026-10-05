<?php

namespace App\Http\Controllers;

use App\Models\LecturerPayable;
use Illuminate\Http\Request;

class LectureFeeController extends Controller
{
    public function index(Request $request)
    {
        $query = LecturerPayable::with([
            'lecturer',
            'lectureSession',
            'rateCard',
        ]);

        // Search lecturer name
        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('lecturer', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        // Department filter
        if ($request->filled('department')) {
            $query->whereHas('lectureSession', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        // Date filter
        if ($request->filled('date_from')) {
            $query->whereHas('lectureSession', function ($q) use ($request) {
                $q->whereDate('session_date', '>=', $request->date_from);
            });
        }

        if ($request->filled('date_to')) {
            $query->whereHas('lectureSession', function ($q) use ($request) {
                $q->whereDate('session_date', '<=', $request->date_to);
            });
        }

        $payables = $query
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        // Departments available in the existing lecture sessions
        $departments = LecturerPayable::query()
            ->join(
                'lecture_sessions',
                'lecturer_payables.lecture_session_id',
                '=',
                'lecture_sessions.id'
            )
            ->whereNotNull('lecture_sessions.department')
            ->where('lecture_sessions.department', '!=', '')
            ->distinct()
            ->orderBy('lecture_sessions.department')
            ->pluck('lecture_sessions.department');

        return view('payments.lecture-fees', compact(
            'payables',
            'departments'
        ));
    }
}