<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory, HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'intern_id',
        'certificate_number',
        'issued_date',
        'issued_city',
        'pdf_url',
        'pdf_password',
        'download_count',
        'generated_by',
    ];

    protected $hidden = [
        'pdf_password',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
        ];
    }

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'generated_by');
    }
}
