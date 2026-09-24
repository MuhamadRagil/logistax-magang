<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'intern_id',
        'evaluated_by',
        'discipline_score',
        'performance_score',
        'attitude_score',
        'communication_score',
        'total_score',
        'grade',
        'comments',
        'edited_by_admin',
        'last_edited_by',
        'needs_certificate_regeneration',
    ];

    protected function casts(): array
    {
        return [
            'discipline_score' => 'decimal:2',
            'performance_score' => 'decimal:2',
            'attitude_score' => 'decimal:2',
            'communication_score' => 'decimal:2',
            'total_score' => 'decimal:2',
            'edited_by_admin' => 'boolean',
            'needs_certificate_regeneration' => 'boolean',
        ];
    }

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'evaluated_by');
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'last_edited_by');
    }
}
