<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * GET /locations
     *
     * Returns every location, used to populate the "Location"
     * dropdown on Tab 3 (and window.__initialLocations on page load).
     * No store() method yet — there's no creation form for locations
     * in the UI. Rows are expected to be added via a seeder or
     * directly through phpMyAdmin/Tinker until a management screen
     * exists. Add a store() + form request here later the same way
     * ResourceCategoryController does, if/when that's needed.
     */
    public function index(): JsonResponse
    {
        $locations = Location::orderBy('name')->get();

        return response()->json($locations);
    }
}