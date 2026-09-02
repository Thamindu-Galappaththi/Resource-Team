<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;

class ResourceController extends Controller
{
    /**
     * GET /resource-list
     *
     * Returns all resources, each with its type (and, through that,
     * its category) and location eager-loaded. Used to populate Tab
     * 4's (Linked Resource) dropdowns.
     *
     * TEMPORARY: 'owner' is left out of with(...) below because the
     * resource_owners table doesn't exist yet — eager-loading a
     * relation that points at a nonexistent table throws a SQL error.
     * Add 'owner' back into both with(...) calls in this file once
     * that table exists (see StoreResourceRequest for the matching
     * TEMP note on validation).
     */
    public function index(): JsonResponse
    {
        $resources = Resource::with(['type.category', 'location'])
            ->orderBy('name_model')
            ->get();

        return response()->json($resources);
    }

    /**
     * POST /resources
     *
     * Creates a single resource. Note we only ever persist
     * resource_type_id — category_id arrives in the request purely so
     * StoreResourceRequest can double-check the type actually belongs
     * to that category, but it's discarded after validation (see the
     * model/migration comments for why there's no category column).
     */
    public function store(StoreResourceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $resource = Resource::create([
            'resource_type_id' => $validated['resource_type_id'],
            'location_id' => $validated['location_id'],
            // NOTE: no resource_owner_id here — that column doesn't
            // exist on "resources" yet (separate task). The Resource
            // Owner dropdown on Tab 3 is currently just a hardcoded
            // display name, nothing is sent to or stored by the
            // backend for it.
            'name_model' => $validated['name_model'],
            'serial_number' => $validated['serial_number'],
            'status' => $validated['status'],
        ]);

        // TEMPORARY: 'owner' left out of load(...) below — see the
        // note on index() above.
        return response()->json(
            $resource->load(['type.category', 'location']),
            201
        );
    }
    
    /**
     * POST /resources/{resource}/request-delete
     * Marks a resource as "pending_deletion". Does NOT delete the row.
     */
    public function requestDelete(Resource $resource): JsonResponse
    {
        $resource->update(['status' => 'pending_deletion']);

        return response()->json($resource->load(['type.category', 'location']));
    }

    /**
     * POST /resources/{resource}/approve-delete
     * Marks a resource as "deleted". Still does NOT delete the row —
     * the record stays in the database forever, just hidden from active use.
     */
    public function approveDelete(Resource $resource): JsonResponse
    {
        $resource->update(['status' => 'deleted']);

        return response()->json($resource->load(['type.category', 'location']));
    }

    /**
     * POST /resources/{resource}/reject-delete
     * Sends the resource back to "active" status.
     */
    public function rejectDelete(Resource $resource): JsonResponse
    {
        $resource->update(['status' => 'active']);

        return response()->json($resource->load(['type.category', 'location']));
    }

    
}