<?php

namespace App\Http\Requests\Intern;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInternRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $intern = $this->route('intern');

        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'nim' => ['sometimes', 'string', 'max:50', Rule::unique('interns', 'nim')->ignore($intern)],
            'institution' => ['sometimes', 'string', 'max:255'],
            'major' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'photo_url' => ['nullable', 'string', 'max:2048'],
            'division_id' => ['sometimes', 'nullable', 'uuid', 'exists:divisions,id'],
            'mentor_id' => ['sometimes', 'nullable', 'uuid', 'exists:admin_users,id'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
        ];
    }
}
