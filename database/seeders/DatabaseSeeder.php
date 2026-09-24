<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Hanya akun admin_magang contoh untuk login testing dashboard —
     * idempotent (updateOrCreate) supaya aman dijalankan berulang kali tanpa
     * membuat duplikat. Tidak menyentuh data intern/attendance/dsb; itu
     * urusan seeder terpisah kalau nanti dibutuhkan.
     */
    public function run(): void
    {
        AdminUser::updateOrCreate(
            ['email' => 'admin@logistax.test'],
            [
                'name' => 'Admin Utama',
                'password' => Hash::make('password123'),
                'role' => 'admin_magang',
                'is_active' => true,
            ]
        );
    }
}
