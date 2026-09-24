<?php

namespace App\Http\Requests\Intern;

use Illuminate\Foundation\Http\FormRequest;

class ApproveInternRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $intern = $this->route('intern');

        return [
            'division_id' => [
                $intern && $intern->division_id ? 'sometimes' : 'required',
                'uuid',
                'exists:divisions,id',
            ],
            'mentor_id' => [
                $intern && $intern->mentor_id ? 'sometimes' : 'required',
                'uuid',
                'exists:admin_users,id',
            ],
        ];
    }
}
