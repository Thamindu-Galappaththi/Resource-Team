<?php

namespace App\Http\Requests;

use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Shape matches the Tab 3 JS payload:
     * { category_id, resource_type_id, location_id, resource_owner_id,
     *   name_model, serial_number }
     *
     * Note: category_id arrives from the frontend (it drove the type
     * dropdown filtering) but is NOT persisted — see the note on the
     * Resource model / migration. We still validate it here so we can
     * cross-check that the chosen type actually belongs to the chosen
     * category (defends against a stale/tampered dropdown value).
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('resource_categories', 'id')],

            'resource_type_id' => [
                'required',
                'integer',
                // Not just "does this type exist", but "does it exist
                // AND belong to the category that was submitted".
                Rule::exists('resource_types', 'id')->where(
                    fn ($query) => $query->where('resource_category_id', $this->input('category_id'))
                ),
            ],

            // Must reference an existing row in the locations table
            // (populates the "Location" dropdown).
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')],

            // ================================================================
            // NOTE: "Resource Owner" is currently just a hardcoded name
            // shown in the dropdown on Tab 3 — there's no
            // resource_owners table and no resource_owner_id column on
            // "resources" at all right now (that's a separate task).
            // Nothing is validated or persisted for it. If a
            // resource_owner_id column gets added later, add a rule
            // back here.
            // ================================================================

            'name_model' => ['required', 'string', 'max:255'],

            // Unique across ALL resources, matching the DB unique
            // constraint on serial_number.
            'serial_number' => ['required', 'string', 'max:255', Rule::unique('resources', 'serial_number')],

            // Must be one of the fixed keys defined on Resource::STATUSES
            // (e.g. "active", "under_maintenance") — keeps the frontend
            // dropdown and backend validation reading from one source
            // of truth instead of two separate hardcoded lists.
            'status' => ['required', 'string', Rule::in(array_keys(Resource::STATUSES))],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'resource_type_id' => 'type',
            'location_id' => 'location',
            'name_model' => 'resource name/model',
            'serial_number' => 'serial number',
            'status' => 'status',
        ];
    }
}