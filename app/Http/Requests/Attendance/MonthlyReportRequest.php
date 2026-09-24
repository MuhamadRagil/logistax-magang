<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class MonthlyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'digits:4'],
            'division_id' => ['sometimes', 'uuid', 'exists:divisions,id'],
            'intern_id' => ['sometimes', 'uuid', 'exists:interns,id'],
        ];
    }
}
