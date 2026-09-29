<?php

namespace App\Http\Requests\Intern;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteInternRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'intern_ids' => ['required', 'array', 'min:1'],
            'intern_ids.*' => ['required', 'uuid', 'distinct', 'exists:interns,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'intern_ids.required' => 'Pilih minimal satu intern untuk dihapus.',
            'intern_ids.*.exists' => 'Salah satu intern yang dipilih tidak ditemukan (mungkin sudah dihapus).',
        ];
    }
}
