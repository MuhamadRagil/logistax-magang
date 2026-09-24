<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Keputusan: proof_file WAJIB untuk status 'sakit' (surat/bukti medis
     * adalah bukti yang lazim & wajar diminta), tapi OPSIONAL untuk 'izin'
     * (keperluan pribadi, cukup dengan alasan di kolom notes). Lihat README
     * untuk detail keputusan ini.
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', 'in:izin,sakit'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof_file' => [
                'required_if:status,sakit',
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proof_file.required_if' => 'Bukti file wajib dilampirkan untuk pengajuan sakit.',
        ];
    }
}
