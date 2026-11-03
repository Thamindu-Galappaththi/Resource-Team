<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    protected $errorBag = 'updatePassword';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'confirmed', Password::min(8)],
        ];

        if ($this->user()?->hasSetPassword()) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        return $rules;
    }

    protected function getRedirectUrl(): string
    {
        return route('user.profile', ['tab' => 'security']);
    }
}
