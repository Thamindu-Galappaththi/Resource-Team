<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ResourceCalendarController extends Controller
{
    public function index()
    {
        return view('resources.calendar');
    }
}