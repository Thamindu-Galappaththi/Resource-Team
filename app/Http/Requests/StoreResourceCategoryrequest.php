<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResourceCategoryRequest extends FormRequest
{
    /**
     * Anyone allowed to hit this form is authorized. Swap this for a
     * real policy/permission check (e.g. auth()->user()->can(...))
     * if category creation should be restricted to certain roles.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules. Shape matches exactly what the Tab 1 JS sends:
     * {
     *   category_name: "string",
     *   features: [
     *     { name: "string", enabled: true/false, options: "string|null" },
     *     ...
     *   ]
     * }
     */
    public function rules(): array
    {
        return [
            // Must be unique (case-sensitive at DB level depending on
            // collation) so the same category can't be created twice.
            'category_name' => ['required', 'string', 'max:255', Rule::unique('resource_categories', 'name')],

            // "features" itself is optional — a category can be created
            // with zero additional features (user just doesn't click
            // "Add Feature" / removes all rows).
            'features' => ['nullable', 'array'],

            // Validate EACH feature row individually using dot notation.
            // "features.*.name" means "the 'name' key of every element
            // inside the features array".
            'features.*.name' => ['nullable', 'string', 'max:255'],

            // The toggle switch. Cast as boolean; not required because
            // an unchecked HTML checkbox simply isn't sent at all, and
            // we default it to false in the controller.
            'features.*.enabled' => ['nullable', 'boolean'],

            // Options is only meaningful when enabled = true, but we
            // don't hard-require that here since the frontend already
            // disables the input when the toggle is off — we just cap
            // the length as a sanity check.
            'features.*.options' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Friendlier field names for error messages, e.g.
     * "The category name field is required." instead of "The category_name field..."
     */
    public function attributes(): array
    {
        return [
            'category_name' => 'category name',
            'features.*.name' => 'feature name',
        ];
    }
}