<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMealMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('canteen.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('meal_menus', 'name')->ignore($this->route('menu'))],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
