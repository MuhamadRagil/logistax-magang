<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Intern extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'intern_account_id',
        'full_name',
        'nim',
        'institution',
        'major',
        'phone',
        'photo_url',
        'division_id',
        'mentor_id',
        'start_date',
        'end_date',
        'original_end_date',
        'status',
        'rejection_reason',
        'failed_reason',
        'registered_via',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'original_end_date' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(InternAccount::class, 'intern_account_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'mentor_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    public function extensionLogs(): HasMany
    {
        return $this->hasMany(ExtensionLog::class);
    }
}
