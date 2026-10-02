<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:50', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['required', 'string', 'max:20'],
            'location' => ['required', 'string', 'max:100'],
            'designation' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }
}
