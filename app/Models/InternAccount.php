<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class InternAccount extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'email',
        'password',
        'is_verified',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_verified' => 'boolean',
        ];
    }

    public function intern(): HasOne
    {
        return $this->hasOne(Intern::class);
    }
}
