<?php

namespace App\Http\Requests\Intern;

use Illuminate\Foundation\Http\FormRequest;

class StoreInternRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:intern_accounts,email'],
            'full_name' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:50', 'unique:interns,nim'],
            'institution' => ['required', 'string', 'max:255'],
            'major' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'division_id' => ['required', 'uuid', 'exists:divisions,id'],
            'mentor_id' => ['required', 'uuid', 'exists:admin_users,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
