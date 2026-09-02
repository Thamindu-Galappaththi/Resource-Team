<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;


class ReservationController extends Controller
{
    public function calendar(Request $request)
    {
        $currentDate = Carbon::now();

        // If year and month are provided, use them
        if ($request->has('year') && $request->has('month')) {
            $currentDate = Carbon::create(
                $request->year,
                $request->month,
                1
            );
        }

        $year = $currentDate->year;
        $month = $currentDate->month;

        $daysInMonth = $currentDate->daysInMonth;

        $firstDayOfMonth = Carbon::create($year, $month, 1);

        $startingDayOfWeek = $firstDayOfMonth->dayOfWeek;

        return view('reservations.calendar', compact(
            'currentDate',
            'year',
            'month',
            'daysInMonth',
            'startingDayOfWeek'
        ));
    }
}