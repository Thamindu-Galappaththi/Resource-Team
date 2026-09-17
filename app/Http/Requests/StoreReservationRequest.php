<?php

namespace App\Http\Requests;

use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'resource_category_id' => ['required', 'integer', Rule::exists('resource_categories', 'id')],
            'resource_id' => [
                'required',
                'integer',
                Rule::exists('resources', 'id')->where('location_id', $this->input('location_id')),
            ],
            'special_requirements' => ['nullable', 'string', 'max:2000'],
            'add_ons' => ['nullable', 'array'],
            'add_ons.*.resource_id' => ['required', 'integer', 'distinct', Rule::exists('resources', 'id')],
            'add_ons.*.special_remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}