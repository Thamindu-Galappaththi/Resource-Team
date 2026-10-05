<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHostelReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reservation_name' => ['required', 'string', 'max:180'],
            'guest_name' => ['required', 'string', 'max:150'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'guest_identity_number' => ['nullable', 'string', 'max:100'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'room_type_id' => [
                'required',
                'integer',
                Rule::exists('resource_types', 'id'),
            ],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'number_of_guests' => ['required', 'integer', 'min:1'],
            'special_requirements' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reservation_name' => 'reservation name',
            'guest_name' => 'guest name',
            'guest_phone' => 'guest phone number',
            'guest_identity_number' => 'guest ID or passport number',
            'check_in_date' => 'check-in date',
            'check_out_date' => 'check-out date',
            'room_type_id' => 'room category',
            'location_id' => 'hostel location',
            'number_of_guests' => 'number of guests',
        ];
    }
}
