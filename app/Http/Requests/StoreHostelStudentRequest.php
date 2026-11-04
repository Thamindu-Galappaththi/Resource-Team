<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreHostelStudentRequest extends FormRequest
{
    private const PHONE = 'regex:/^\+?[0-9][0-9\s\-()]{7,18}$/';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect($this->only([
            'student_name', 'student_id', 'home_town', 'phone', 'emergency_contact_name', 'emergency_contact_phone',
        ]))->map(fn ($value) => is_string($value) ? trim($value) : $value)->all());
    }

    public function rules(): array
    {
        return [
            'student_name' => ['required', 'string', 'max:150'],
            'student_id' => ['required', 'string', 'max:50'],
            'check_in_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:check_in_date'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'home_town' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', self::PHONE],
            'emergency_contact_name' => ['required', 'string', 'max:150'],
            'emergency_contact_phone' => ['required', 'string', self::PHONE],
            'website' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['check_in_date', 'check_in_time', 'check_out_date', 'check_out_time'])) {
                    return;
                }
                $tz = config('reservations.display_timezone', 'Asia/Colombo');
                $checkIn = Carbon::parse($this->input('check_in_date').' '.$this->input('check_in_time'), $tz);
                $checkOut = Carbon::parse($this->input('check_out_date').' '.$this->input('check_out_time'), $tz);

                if ($checkIn->lessThanOrEqualTo(now($tz))) {
                    $validator->errors()->add('check_in_time', 'Check-in must be in the future.');
                } elseif ($checkOut->lessThanOrEqualTo($checkIn)) {
                    $validator->errors()->add('check_out_time', 'Check-out must be after check-in.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number, for example 077 123 4567.',
            'emergency_contact_phone.regex' => 'Enter a valid emergency contact number, for example 077 123 4567.',
        ];
    }

    public function attributes(): array
    {
        return [
            'student_name' => 'student name',
            'student_id' => 'student ID',
            'check_in_date' => 'check-in date',
            'check_in_time' => 'check-in time',
            'check_out_date' => 'check-out date',
            'check_out_time' => 'check-out time',
            'home_town' => 'home town',
            'phone' => 'phone number',
            'emergency_contact_name' => 'emergency contact name',
            'emergency_contact_phone' => 'emergency contact number',
        ];
    }
}
