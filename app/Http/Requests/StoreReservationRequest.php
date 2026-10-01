<?php

namespace App\Http\Requests;

use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
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

        $this->merge([
            'resource_ids' => $resourceIds,
            'requester_id' => $this->input('requester_id', auth()->id()),
            'start_time' => $this->normalizeTime($this->input('start_time')),
            'end_time' => $this->normalizeTime($this->input('end_time')),
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
            'resource_ids' => ['required', 'array', 'min:1'],
            'resource_ids.*' => ['integer', Rule::exists('resources', 'id')],
            'requester_id' => ['required', 'integer', 'exists:users,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose' => ['required', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:180'],
            'attendee_count' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'resource_id' => 'resource',
            'reservation_date' => 'reservation date',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'purpose' => 'purpose of reservation',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $resource = Resource::query()->find($this->input('resource_id'));
            if ($resource && $resource->status !== 'active') {
                $validator->errors()->add('resource_id', 'The selected resource is not available for booking.');
            }
        });
    }
}
