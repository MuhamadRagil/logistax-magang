<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keputusan produk: sertifikat PDF tidak lagi dienkripsi dengan password
 * (sebelumnya password = NIM intern, lihat
 * CertificateIssuingService::issueCertificate() dan
 * CertificatePdfService::protectWithPassword()). certificates.pdf_password
 * sebelumnya NOT NULL (selalu diisi NIM); sertifikat baru sekarang menyimpan
 * NULL di kolom ini, jadi kolomnya harus nullable dulu.
 *
 * Kolom TIDAK dihapus — masih menyimpan pdf_password (NIM) milik sertifikat
 * lama yang di-generate sebelum keputusan ini, sampai masing-masing
 * di-regenerate. Tidak ada data yang diubah/dihapus di migration ini.
 * No-op kalau tabel/kolom tidak ada atau kolomnya sudah nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->columnExists() || $this->isNullable()) {
            return;
        }

        Schema::table('certificates', function (Blueprint $table) {
            $table->string('pdf_password')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! $this->columnExists() || ! $this->isNullable()) {
            return;
        }

        // Sertifikat yang digenerate/diregenerate setelah keputusan ini punya
        // pdf_password NULL (file-nya memang tidak dienkripsi) — tidak ada
        // nilai password valid untuk dikembalikan, jadi diisi string kosong
        // supaya rollback ke NOT NULL tidak gagal karena constraint.
        DB::table('certificates')->whereNull('pdf_password')->update(['pdf_password' => '']);

        Schema::table('certificates', function (Blueprint $table) {
            $table->string('pdf_password')->nullable(false)->change();
        });
    }

    private function columnExists(): bool
    {
        return Schema::hasTable('certificates') && Schema::hasColumn('certificates', 'pdf_password');
    }

    private function isNullable(): bool
    {
        $column = collect(Schema::getColumns('certificates'))->firstWhere('name', 'pdf_password');

        return $column === null || $column['nullable'];
    }
};
