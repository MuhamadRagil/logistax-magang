<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * PATCH bersifat partial — hanya field yang dikirim yang divalidasi.
     * total_score & grade tetap tidak menerima input dari body, selalu
     * dihitung ulang server-side (lihat EvaluationController::update()).
     */
    public function rules(): array
    {
        return [
            'discipline_score' => ['sometimes', 'numeric', 'between:0,100'],
            'performance_score' => ['sometimes', 'numeric', 'between:0,100'],
            'attitude_score' => ['sometimes', 'numeric', 'between:0,100'],
            'communication_score' => ['sometimes', 'numeric', 'between:0,100'],
            'comments' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
