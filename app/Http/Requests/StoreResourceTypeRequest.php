<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResourceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Shape matches the Tab 2 JS payload:
     * { category_id, resource_type, features: [{ category_feature_id, value }] }
     */
    public function rules(): array
    {
        return [
            // Must reference an existing category — this is what
            // populates the "Select Category" dropdown in the first
            // place, so it should never be missing/invalid unless the
            // request was tampered with.
            'category_id' => ['required', 'integer', Rule::exists('resource_categories', 'id')],

            // The "Resource Type" text field. Uniqueness is enforced
            // per-category (not globally) — see the composite unique
            // index on the resource_types migration — so we mirror
            // that same scoping here for a friendlier validation error
            // instead of only relying on the DB-level unique constraint.
            'resource_type' => [
                'required',
                'string',
                'max:255',
                Rule::unique('resource_types', 'name')->where(
                    fn ($query) => $query->where('resource_category_id', $this->input('category_id'))
                ),
            ],

            // The "Description" textarea — optional per the form.
            'features' => ['nullable', 'array'],
            'features.*.category_feature_id' => [
                'required_with:features.*.value',
                'integer',
                Rule::exists('category_features', 'id')->where(
                    fn ($query) => $query->where('resource_category_id', $this->input('category_id'))
                ),
            ],
            'features.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'resource_type' => 'resource type',
            'features.*.value' => 'additional feature',
        ];
    }
}
