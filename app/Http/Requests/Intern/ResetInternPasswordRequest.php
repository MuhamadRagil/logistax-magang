<?php

namespace App\Http\Requests\Intern;

use Illuminate\Foundation\Http\FormRequest;

class ResetInternPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:random,custom'],
            // nullable + required_if (not "sometimes"): mode=random must be
            // allowed to send no password / an empty one at all without
            // tripping min:8, while mode=custom still enforces it.
            'password' => ['nullable', 'required_if:mode,custom', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required_if' => 'Password wajib diisi untuk mode "Tentukan Sendiri".',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
