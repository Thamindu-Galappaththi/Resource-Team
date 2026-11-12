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
        $categoryIds = collect($this->input('category_ids', []))
            ->when($this->input('category_id'), fn ($ids) => $ids->prepend($this->input('category_id')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->prepareSpecialApproval();
        $lecturer = $this->input('lecturer_name');
        $bookingType = $this->input('booking_type');
        $bookingTimes = [
            'full_day' => ['09:00', '17:00'],
            'half_day_morning' => ['09:00', '13:00'],
            'half_day_afternoon' => ['13:00', '17:00'],
        ];
        $this->merge([
            'resource_ids' => $resourceIds,
            'resource_id' => $resourceIds[0] ?? $this->input('resource_id'),
            'category_ids' => $categoryIds,
            'category_id' => $categoryIds[0] ?? $this->input('category_id'),
            'requester_id' => $this->input('requester_id', auth()->id()),
            'start_time' => $bookingTimes[$bookingType][0] ?? $this->normalizeTime($this->input('start_time')),
            'end_time' => $bookingTimes[$bookingType][1] ?? $this->normalizeTime($this->input('end_time')),
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
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', Rule::exists('resource_categories', 'id')],
            'requester_id' => ['required', 'integer', 'exists:users,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'category_id' => ['required', 'integer', 'exists:resource_categories,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'booking_type' => ['nullable', 'in:full_day,half_day_morning,half_day_afternoon'],
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
            $categoryIds = collect($this->input('category_ids', [$this->input('category_id')]))->filter()->map(fn ($id) => (int) $id);
            foreach ($categoryIds as $categoryId) {
                $category = ResourceCategory::query()->find($categoryId);
                if ($category?->isLocked()) {
                    $validator->errors()->add('category_id', 'Hostel rooms are booked from Hostel Reservations.');
                    break;
                }
            }
            $resourceIds = collect($this->input('resource_ids', [$this->input('resource_id')]))->filter()->unique();
            $resources = Resource::query()->with('type')->whereIn('id', $resourceIds)->get();
            foreach ($resources as $resource) {
                if ($resource->status !== 'active') {
                    $validator->errors()->add('resource_id', 'The selected resource is not available for booking.');
                }
                if ($this->filled('location_id') && (int) $resource->location_id !== (int) $this->input('location_id')) {
                    $validator->errors()->add('location_id', 'The resource does not belong to the selected location.');
                }
                if ($categoryIds->isNotEmpty() && ! $categoryIds->contains((int) $resource->type?->resource_category_id)) {
                    $validator->errors()->add('category_id', 'The resource does not belong to the selected category.');
                }
            }
        });
    }
}
