<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCanteenRequest;
use App\Models\Canteen;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CanteenController extends Controller
{
    public function create(Request $request)
    {
        $locations = Location::orderBy('name')->get();
        return view('canteens.create', compact('locations'));
    }

    public function store(StoreCanteenRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $canteen = Canteen::create($data);

        return redirect()->route('canteens.create')->with('status', 'Canteen created successfully.');
    }
}
