<?php

namespace App\Http\Requests;

use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Services\HostelBedCapacity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreResourceRequest extends FormRequest
{
    private ?bool $hostel = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Shape matches the Tab 3 JS payload:
     * { category_id, resource_type_id, location_id, resource_owner,
     *   name_model, serial_number, bed_counts: { permanent, female, male } }
     *
     * Note: category_id arrives from the frontend (it drove the type
     * dropdown filtering) but is NOT persisted — see the note on the
     * Resource model / migration. We still validate it here so we can
     * cross-check that the chosen type actually belongs to the chosen
     * category (defends against a stale/tampered dropdown value).
     */
    public function rules(): array
    {
        $resource = $this->route('resource');
        $hostel = $this->isHostel();

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

            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->whereIn('name', config('resources.locations', [])),
            ],
            'resource_owner' => ['nullable', 'string', 'max:255'],
            'linked_resource_ids' => ['nullable', 'array'],
            'linked_resource_ids.*' => ['integer', 'distinct', 'exists:resources,id'],

            'name_model' => [$hostel ? 'nullable' : 'required', 'string', 'max:120'],

            // Optional serials are normalized to null before validation.
            'serial_number' => [
                'nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('resources', 'serial_number')->ignore($resource instanceof Model ? $resource->getKey() : null),
            ],

            // Must be one of the fixed keys defined on Resource::STATUSES
            // (e.g. "active", "under_maintenance") — keeps the frontend
            // dropdown and backend validation reading from one source
            // of truth instead of two separate hardcoded lists.
            'status' => ['required', 'string', Rule::in(array_keys(Resource::STATUSES))],

        ] + ($hostel ? $this->bedRules() : ['bed_counts' => ['nullable']]);
    }

    /**
     * @return array<string, list<string>>
     */
    private function bedRules(): array
    {
        $rules = ['bed_counts' => ['required', 'array']];
        $max = app(HostelBedCapacity::class)->maxPerGroup();
        foreach (array_keys(config('hostel.bed_groups', [])) as $key) {
            $rules["bed_counts.{$key}"] = ['required', 'integer', 'min:0', 'max:'.$max];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->isHostel() || $validator->errors()->has('location_id')) {
                    return;
                }
                $hostelCampuses = config('hostel.locations', []);
                $campus = Location::query()->find($this->input('location_id'))?->name;
                if (! in_array($campus, $hostelCampuses, true)) {
                    $validator->errors()->add('location_id', "{$campus} has no hostel. Hostels can only be added at ".implode(' or ', $hostelCampuses).'.');

                    return;
                }
                $resource = $this->route('resource');
                $duplicate = Resource::query()
                    ->hostelBedInventory()
                    ->where('location_id', $this->input('location_id'))
                    ->when($resource instanceof Model, fn ($query) => $query->whereKeyNot($resource->getKey()))
                    ->exists();
                if ($duplicate) {
                    $location = Location::query()->find($this->input('location_id'))?->name;
                    $validator->errors()->add('location_id', "{$location} already has a hostel record. Edit that record to change its beds.");
                }

                $counts = (array) $this->input('bed_counts', []);
                if (! $validator->errors()->has('bed_counts') && array_sum(array_map('intval', $counts)) < 1) {
                    $validator->errors()->add('bed_counts', 'Add at least one bed.');
                }

                if ($resource instanceof Resource) {
                    $this->assertBedsCoverBookings($validator, $resource, $counts);
                }
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $counts
     */
    private function assertBedsCoverBookings(Validator $validator, Resource $resource, array $counts): void
    {
        $beds = app(HostelBedCapacity::class);
        foreach ($beds->groups() as $key => $group) {
            if ($validator->errors()->has("bed_counts.{$key}")) {
                continue;
            }
            $booked = $beds->peakBookedBeds($resource->id, $key, now());
            if ((int) ($counts[$key] ?? 0) < $booked) {
                $validator->errors()->add("bed_counts.{$key}", "{$booked} {$group['label']} ".($booked === 1 ? 'bed is' : 'beds are').' already booked on upcoming dates. Keep at least that many.');
            }
        }
    }

    public function isHostel(): bool
    {
        if ($this->hostel === null) {
            $category = ResourceCategory::query()->find($this->input('category_id'));
            $this->hostel = $category?->isLocked() ?? false;
        }

        return $this->hostel;
    }

    protected function prepareForValidation(): void
    {
        $serialNumber = $this->input('serial_number');
        if (is_string($serialNumber)) {
            $serialNumber = trim($serialNumber);
            $this->merge(['serial_number' => $serialNumber === '' ? null : $serialNumber]);
        }
        $name = $this->input('name_model');
        if (is_string($name)) {
            $this->merge(['name_model' => trim($name) === '' ? null : trim($name)]);
        }
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'resource_type_id' => 'type',
            'location_id' => 'location',
            'resource_owner' => 'resource owner',
            'linked_resource_ids' => 'linked resources',
            'name_model' => 'resource name/model',
            'serial_number' => 'serial number',
            'status' => 'status',
            'bed_counts' => 'bed counts',
        ] + collect(config('hostel.bed_groups', []))
            ->mapWithKeys(fn (array $group, string $key) => ["bed_counts.{$key}" => $group['label'].' beds'])
            ->all();
    }

    public function messages(): array
    {
        return [
            'location_id.exists' => 'Resources can only be added at '.$this->campusList().'.',
        ];
    }

    private function campusList(): string
    {
        $names = array_values(config('resources.locations', []));
        if ($names === []) {
            return 'an allowed campus';
        }
        if (count($names) === 1) {
            return $names[0];
        }
        $last = array_pop($names);

        return implode(', ', $names).' or '.$last;
    }
}
