<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCanteenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('canteen.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'exists:locations,id'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $opening = $this->input('opening_time');
            $closing = $this->input('closing_time');

            if ($opening && $closing) {
                try {
                    $o = \DateTime::createFromFormat('H:i', $opening);
                    $c = \DateTime::createFromFormat('H:i', $closing);
                    if ($o && $c && $c <= $o) {
                        $validator->errors()->add('closing_time', 'The closing time must be later than the opening time.');
                    }
                } catch (\Throwable $e) {
                    // let date_format rules report errors
                }
            }
        });
    }
}
