<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * total_score & grade sengaja TIDAK divalidasi/diterima di sini — selalu
     * dihitung server-side lewat EvaluationScoringService, apa pun yang
     * dikirim di body untuk field itu akan diabaikan (lihat EvaluationController).
     */
    public function rules(): array
    {
        return [
            'intern_id' => ['required', 'uuid', 'exists:interns,id'],
            'discipline_score' => ['required', 'numeric', 'between:0,100'],
            'performance_score' => ['required', 'numeric', 'between:0,100'],
            'attitude_score' => ['required', 'numeric', 'between:0,100'],
            'communication_score' => ['required', 'numeric', 'between:0,100'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
