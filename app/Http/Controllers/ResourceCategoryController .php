<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceCategoryRequest;
use App\Models\ResourceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ResourceCategoryController extends Controller
{
    /**
     * GET /resource-categories
     *
     * Returns the full list of categories. Used to:
     *  - inject window.__initialCategories on page load, and/or
     *  - power an optional "refresh" button on the frontend.
     *
     * eager-loads "features" so the response includes each category's
     * additional features without triggering an N+1 query problem if
     * you ever loop over categories and touch ->features on each one.
     */
    public function index(): JsonResponse
    {
        $categories = ResourceCategory::with('features')->orderBy('name')->get();

        return response()->json($categories);
    }

    /**
     * POST /resource-categories
     *
     * Creates a category AND its "Additional Features" rows together.
     * The frontend sends both in a single request (see categoryForm's
     * submit handler), so we save both in a single DB transaction:
     * if creating any feature row fails, the category insert is rolled
     * back too — you never end up with a category that has no features
     * because half the request silently failed.
     */
    public function store(StoreResourceCategoryRequest $request): JsonResponse
    {
        // $request->validated() only returns fields that passed the
        // rules defined in StoreResourceCategoryRequest — safer than
        // trusting the raw request input directly.
        $validated = $request->validated();

        $category = DB::transaction(function () use ($validated) {
            $category = ResourceCategory::create([
                'name' => $validated['category_name'],
            ]);

            // "features" is optional, so default to an empty array if
            // the user didn't add any rows before submitting.
            foreach ($validated['features'] ?? [] as $feature) {
                $category->features()->create([
                    'name' => $feature['name']?? null,
                    // Cast explicitly to bool: an unchecked checkbox
                    // means this key may be completely absent from the
                    // payload, so default it to false rather than null.
                    'enabled' => (bool) ($feature['enabled'] ?? false),
                    'options' => $feature['options'] ?? null,
                ]);
            }

            return $category;
        });

        // Reload with the features relationship so the JSON response
        // includes everything that was just created — the frontend's
        // postJSON(...) call reads result.id from this response to
        // append the new category into its local dropdown state.
        return response()->json(
            $category->load('features'),
            201 // HTTP 201 Created
        );
    }
}