<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SriLankanNic implements ValidationRule
{
    public static function pattern(): string
    {
        return '/^(?:\d{12}|\d{9}[VX])$/';
    }

    public static function normalize(?string $nic): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', (string) $nic));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::pattern(), $value) !== 1) {
            $fail('Enter a valid NIC: 12 digits, or 9 digits followed by V or X.');
        }
    }
}
