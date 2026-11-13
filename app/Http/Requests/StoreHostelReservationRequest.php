<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SpecialApprovalFields;
use App\Models\HostelStayDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHostelReservationRequest extends FormRequest
{
    use SpecialApprovalFields;

    public const MAX_GUESTS = 20;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSpecialApproval();
        if (is_string($this->input('guest_phone'))) {
            $this->merge(['guest_phone' => trim($this->input('guest_phone'))]);
        }
        $ids = $this->input('employee_ids');
        if (is_array($ids)) {
            $this->merge([
                'employee_ids' => array_values(array_map(
                    fn ($id) => is_string($id) ? trim($id) : $id,
                    $ids,
                )),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'guest_title' => ['required', Rule::in(HostelStayDetail::GUEST_TITLES)],
            'guest_name' => ['required', 'string', 'max:150'],
            'guest_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^\+?[0-9][0-9\s\-()]{7,18}$/',
            ],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_date' => ['required', 'date', 'after_or_equal:check_in_date'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'number_of_guests' => ['required', 'integer', 'min:1', 'max:'.self::MAX_GUESTS],
            'bed_group' => ['required', 'string', Rule::in(array_keys(config('hostel.bed_groups', [])))],
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/', 'distinct:ignore_case'],
            'special_requirements' => ['nullable', 'string', 'max:1000'],
        ] + $this->specialApprovalRules();
    }

    public function messages(): array
    {
        return [
            'guest_phone.regex' => 'Enter a valid phone number, for example +94 77 123 4567.',
            'employee_ids.*.regex' => 'Enter numbers only for the employee ID.',
        ] + $this->specialApprovalMessages();
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $guests = (int) $this->input('number_of_guests');
                $ids = $this->input('employee_ids');
                if ($guests >= 1 && is_array($ids) && count($ids) !== $guests) {
                    $validator->errors()->add(
                        'employee_ids',
                        $guests === 1
                            ? 'Enter the employee ID for the guest.'
                            : "Enter {$guests} employee IDs, one for each guest."
                    );
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'guest_title' => 'title',
            'guest_name' => 'guest name',
            'guest_phone' => 'guest phone number',
            'check_in_date' => 'check-in date',
            'check_in_time' => 'check-in time',
            'check_out_date' => 'check-out date',
            'check_out_time' => 'check-out time',
            'location_id' => 'hostel location',
            'number_of_guests' => 'number of guests',
            'bed_group' => 'bed group',
            'employee_ids' => 'employee IDs',
            'employee_ids.*' => 'employee ID',
        ] + $this->specialApprovalAttributes();
    }
}
