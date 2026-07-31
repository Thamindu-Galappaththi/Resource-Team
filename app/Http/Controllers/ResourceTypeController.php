<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceTypeRequest;
use App\Models\ResourceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResourceTypeController extends Controller
{
    /**
     * GET /resource-types
     * GET /resource-types?category_id=3
     *
     * Without a category_id: returns ALL types (used to populate
     * window.__initialTypes on page load, since Tab 3's JS keeps the
     * full type list in memory and filters it client-side).
     *
     * With a category_id: returns only that category's types. Not
     * strictly required by the current frontend (which filters
     * client-side from the already-loaded list), but handy if you'd
     * rather fetch types on-demand instead of loading all of them
     * up front — e.g. if the type list grows very large.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ResourceType::query();

        if ($request->filled('category_id')) {
            $query->where('resource_category_id', $request->integer('category_id'));
        }

        $types = $query->orderBy('name')->get();

        return response()->json($types);
    }

    /**
     * POST /resource-types
     *
     * Creates a single resource type under the given category.
     * Validation (including "must belong to an existing category" and
     * "name must be unique within that category") is handled entirely
     * by StoreResourceTypeRequest before this method body even runs.
     */
    public function store(StoreResourceTypeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $type = DB::transaction(function () use ($validated) {
            $type = ResourceType::create([
                'resource_category_id' => $validated['category_id'],
                'name' => $validated['resource_type'],
                'description' => null,
            ]);

            foreach ($validated['features'] ?? [] as $feature) {
                $type->featureValues()->create([
                    'category_feature_id' => $feature['category_feature_id'],
                    'value' => $feature['value'] ?? null,
                ]);
            }

            return $type;
        });

        // The frontend's typeForm submit handler only reads result.id
        // off this response, but we return the full model (plus its
        // parent category) in case you want to display it elsewhere
        // without a follow-up request.
        return response()->json(
            $type->load('category', 'featureValues.feature'),
            201
        );
    }
}
