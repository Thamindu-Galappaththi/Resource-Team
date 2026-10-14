<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourceController extends Controller
{
    /** GET /resources: inventory page with dropdown lookups for the edit modal. */
    public function inventoryPage(): View
    {
        $canManage = auth()->user()?->hasPermission('resources.create') ?? false;

        return view('resources.index', [
            'lookups' => $canManage ? [
                'categories' => ResourceCategory::query()->orderBy('name')->get(['id', 'name']),
                'types' => ResourceType::query()->orderBy('name')->get(['id', 'name', 'resource_category_id']),
                'locations' => Location::query()->ordered()->get(['id', 'name']),
            ] : [
                'categories' => [],
                'types' => [],
                'locations' => [],
            ],
        ]);
    }

    /**
     * GET /resource-list
     *
     * Returns all resources, each with its type (and, through that,
     * its category) and location eager-loaded.
     */
    public function index(): JsonResponse
    {
        $resources = Resource::with(['type.category', 'location', 'linkedResources'])
            ->orderBy('name_model')
            ->get();

        return response()->json($resources);
    }

    /** GET /resources/{resource}: details for the read-only view. */
    public function show(Resource $resource): JsonResponse
    {
        $resource->load([
            'type.category.features',
            'type.featureValues.feature',
            'location',
            'linkedResources.type.category',
            'linkedResources.location',
            'linkedFrom.type.category',
            'linkedFrom.location',
        ]);

        $payload = $resource->toArray();
        $payload['linked_resources'] = $resource->linkedResources
            ->concat($resource->linkedFrom)
            ->unique('id')
            ->values()
            ->map(fn (Resource $item) => [
                'id' => $item->id,
                'name_model' => $item->name_model,
                'serial_number' => $item->serial_number,
                'status' => $item->status,
                'location' => $item->location ? [
                    'id' => $item->location->id,
                    'name' => $item->location->name,
                ] : null,
            ])
            ->all();

        return response()->json($payload);
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
            'resource_owner' => $validated['resource_owner'] ?? null,
            'name_model' => $validated['name_model'],
            'serial_number' => $validated['serial_number'] ?? null,
            'status' => $validated['status'],
        ]);

        return response()->json(
            $resource->load(['type.category', 'location']),
            201
        );
    }

    /** PUT /resources/{resource}: update using the Create Resource form payload. */
    public function update(StoreResourceRequest $request, Resource $resource): JsonResponse
    {
        $validated = $request->validated();
        $resource->update([
            'resource_type_id' => $validated['resource_type_id'],
            'location_id' => $validated['location_id'],
            'resource_owner' => $validated['resource_owner'] ?? null,
            'name_model' => $validated['name_model'],
            'serial_number' => $validated['serial_number'] ?? null,
            'status' => $validated['status'],
        ]);

        $linkedIds = collect($validated['linked_resource_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $resource->id)
            ->unique()
            ->values()
            ->all();
        $resource->linkedResources()->sync($linkedIds);

        return response()->json($resource->fresh()->load(['type.category', 'location', 'linkedResources']));
    }

    /**
     * POST /resource-links
     * Links one resource to another.
     */
    public function storeLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_id' => ['required', 'integer', 'exists:resources,id'],
            'linked_resource_id' => ['required', 'integer', 'different:resource_id', 'exists:resources,id'],
        ]);

        $resource = Resource::findOrFail($validated['resource_id']);
        $resource->linkedResources()->syncWithoutDetaching([$validated['linked_resource_id']]);

        return response()->json([
            'resource_id' => $resource->id,
            'linked_resource_id' => (int) $validated['linked_resource_id'],
        ], 201);
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

    /** DELETE /resources/{resource}: soft delete without removing the row. */
    public function destroy(Resource $resource): JsonResponse
    {
        $resource->update([
            'is_deleted' => true,
            'deleted_at' => now(),
        ]);

        return response()->json(['message' => 'Resource soft deleted.']);
    }
}
