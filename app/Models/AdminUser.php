<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class AdminUser extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'title',
        'phone',
        'avatar_url',
        'division_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function mentoredInterns(): HasMany
    {
        return $this->hasMany(Intern::class, 'mentor_id');
    }

    /**
     * Display helpers for the Blade dashboard (topbar avatar/badge). Purely
     * presentational, added for Fase 5 — no effect on auth/API behavior.
     */
    public function initials(): string
    {
        $words = array_slice(preg_split('/\s+/', trim($this->name)), 0, 2);

        return strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), $words)));
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin_magang' => 'Admin Magang',
            'spv_mentor' => 'Spv Mentor',
            default => $this->role,
        };
    }
}
