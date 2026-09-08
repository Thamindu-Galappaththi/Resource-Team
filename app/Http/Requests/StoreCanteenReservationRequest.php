<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCanteenReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('canteen.reservations.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'reservation_name' => ['required', 'string', 'max:255'],
            'canteen_id' => [
                'required',
                Rule::exists('canteens', 'id')->where('is_active', 1),
            ],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'meal_type' => ['required', 'string', Rule::in(['Breakfast','Morning Tea','Lunch','Evening Tea','Dinner','Other'])],
            'number_of_orders' => ['required', 'integer', 'min:1'],
            'order_details' => ['required', 'string'],
            'special_requirements' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
