<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SpecialApprovalFields;
use App\Models\Resource;
use App\Models\ResourceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    use SpecialApprovalFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $resourceIds = collect($this->input('resource_ids', []))
            ->when($this->input('resource_id'), fn ($ids) => $ids->prepend($this->input('resource_id')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->prepareSpecialApproval();
        $lecturer = $this->input('lecturer_name');
        $this->merge([
            'resource_ids' => $resourceIds,
            'requester_id' => $this->input('requester_id', auth()->id()),
            'start_time' => $this->normalizeTime($this->input('start_time')),
            'end_time' => $this->normalizeTime($this->input('end_time')),
            'title' => is_string($this->input('title')) ? trim($this->input('title')) : $this->input('title'),
            'lecturer_name' => is_string($lecturer) && trim($lecturer) !== '' ? trim($lecturer) : null,
        ]);
    }

    private function normalizeTime(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return substr($value, 0, 5);
    }

    public function rules(): array
    {
        return [
            'resource_id' => ['required', 'integer', Rule::exists('resources', 'id')],
            'resource_ids' => ['array'],
            'resource_ids.*' => ['integer', Rule::exists('resources', 'id')],
            'requester_id' => ['required', 'integer', 'exists:users,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'category_id' => ['required', 'integer', 'exists:resource_categories,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'title' => ['required', 'string', 'max:180'],
            'lecturer_name' => ['nullable', 'string', 'max:150'],
            'purpose' => ['required', 'string', 'max:500'],
            'attendee_count' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
        ] + $this->specialApprovalRules();
    }

    public function attributes(): array
    {
        return [
            'resource_id' => 'resource',
            'resource_ids' => 'resource',
            'location_id' => 'location',
            'category_id' => 'resource category',
            'reservation_date' => 'reservation date',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'title' => 'reservation name',
            'lecturer_name' => 'lecturer name',
            'purpose' => 'purpose of reservation',
        ] + $this->specialApprovalAttributes();
    }

    public function messages(): array
    {
        return [
            'resource_id.required' => 'Select a resource.',
            'resource_id.exists' => 'The selected resource could not be found.',
            'location_id.required' => 'Select a location.',
            'category_id.required' => 'Select a resource category.',
            'title.required' => 'Enter a reservation name.',
        ] + $this->specialApprovalMessages();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = ResourceCategory::query()->find($this->input('category_id'));
            if ($category?->isLocked()) {
                $validator->errors()->add('category_id', 'Hostel rooms are booked from Hostel Reservations.');
            }
            $resource = Resource::query()->with('type')->find($this->input('resource_id'));
            if ($resource && $resource->status !== 'active') {
                $validator->errors()->add('resource_id', 'The selected resource is not available for booking.');
            }
            if ($resource && $this->filled('location_id') && (int) $resource->location_id !== (int) $this->input('location_id')) {
                $validator->errors()->add('location_id', 'The resource does not belong to the selected location.');
            }
            if ($resource && $this->filled('category_id') && (int) $resource->type?->resource_category_id !== (int) $this->input('category_id')) {
                $validator->errors()->add('category_id', 'The resource does not belong to the selected category.');
            }
        });
    }
}
