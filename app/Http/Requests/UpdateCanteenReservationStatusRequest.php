<?php

namespace App\Http\Requests;

use App\Enums\CanteenReservationStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCanteenReservationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.implode(',', CanteenReservationStatus::values())],
            'approval_comments' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'approval_comments.required' => 'Approval comments are required when rejecting a reservation.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('approval_comments', 'required', function ($input) {
            return ($input['status'] ?? null) === CanteenReservationStatus::REJECTED->value;
        });
    }
}
