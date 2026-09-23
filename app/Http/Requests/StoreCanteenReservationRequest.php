<?php

namespace App\Http\Requests;

use App\Enums\MealType;
use Illuminate\Foundation\Http\FormRequest;

class StoreCanteenReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reservation_name' => ['required', 'string', 'max:255'],
            'requested_by_user_id' => ['required', 'integer', 'exists:users,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'meal_type' => ['required', 'string', 'in:'.implode(',', MealType::values())],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'number_of_orders' => ['required', 'integer', 'min:1'],
            'order_details' => ['nullable', 'string', 'max:1000'],
            'special_remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
