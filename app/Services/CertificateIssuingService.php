<?php

namespace App\Services;

use App\Exceptions\DomainActionException;
use App\Jobs\SendCertificateReadyNotification;
use App\Models\AdminUser;
use App\Models\Certificate;
use App\Models\Intern;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Orchestrates certificate generate/regenerate (number + PDF render +
 * storage + DB row). Extracted out of Api\CertificateController so the web
 * "Sertifikat" page can trigger the exact same validated generate/regenerate
 * flow.
 *
 * Keputusan produk: PDF tidak lagi dienkripsi dengan password (sebelumnya
 * password = NIM intern, lewat CertificatePdfService::protectWithPassword()).
 * issueCertificate() sekarang menyimpan hasil Browsershot mentah dan menulis
 * NULL ke pdf_password. protectWithPassword() masih ada dan
 * setasign/fpdi-tcpdf belum di-uninstall — hanya sudah tidak dipanggil dari
 * sini; pembersihan dependency-nya menyusul terpisah.
 */
class CertificateIssuingService
{
    private const STORAGE_DIR = 'certificates';

    public function __construct(
        private readonly CertificateNumberService $numberService,
        private readonly CertificatePdfService $pdfService,
    ) {}

    public function generate(Intern $intern, AdminUser $admin): Certificate
    {
        $this->guardFailedStatus($intern);

        if ($intern->status !== 'completed') {
            throw new DomainActionException('Sertifikat hanya bisa dibuat untuk intern dengan status completed.', 400);
        }

        if (! $this->hasCompleteEvaluation($intern)) {
            throw new DomainActionException('Evaluasi untuk intern ini belum ada atau belum lengkap, sertifikat tidak bisa dibuat.', 400);
        }

        $existing = Certificate::where('intern_id', $intern->id)->first();

        if ($existing && ! $intern->evaluation->needs_certificate_regeneration) {
            throw new DomainActionException('Sertifikat sudah pernah digenerate. Gunakan endpoint regenerate kalau ingin membuat ulang.', 400);
        }

        $certificate = $this->issueCertificate($intern, $admin, $existing);

        // Only the FIRST issue notifies the intern. generate() also re-issues
        // an existing certificate when needs_certificate_regeneration is set;
        // that (like regenerate() and preview) sends nothing.
        if (! $existing) {
            SendCertificateReadyNotification::dispatchAfterResponse($intern->id);
        }

        return $certificate;
    }

    /**
     * Keputusan: endpoint ini TIDAK dibatasi hanya saat
     * needs_certificate_regeneration = true — admin_magang boleh regenerate
     * kapan saja selama certificate sudah pernah dibuat (mis. untuk perbaikan
     * data lain seperti issued_city yang salah ketik, bukan cuma karena nilai
     * berubah). Lihat README untuk penjelasan lengkap.
     */
    public function regenerate(Intern $intern, AdminUser $admin): Certificate
    {
        $this->guardFailedStatus($intern);

        $existing = Certificate::where('intern_id', $intern->id)->first();

        if (! $existing) {
            throw new DomainActionException('Sertifikat belum pernah dibuat, gunakan endpoint generate.', 400);
        }

        if (! $this->hasCompleteEvaluation($intern)) {
            throw new DomainActionException('Evaluasi untuk intern ini belum ada atau belum lengkap, sertifikat tidak bisa dibuat.', 400);
        }

        return $this->issueCertificate($intern, $admin, $existing);
    }

    public function hasCompleteEvaluation(Intern $intern): bool
    {
        $evaluation = $intern->evaluation;

        if (! $evaluation) {
            return false;
        }

        return $evaluation->discipline_score !== null
            && $evaluation->performance_score !== null
            && $evaluation->attitude_score !== null
            && $evaluation->communication_score !== null;
    }

    public function guardFailedStatus(Intern $intern): void
    {
        if ($intern->status === 'failed') {
            throw new DomainActionException('Sertifikat tidak dapat diproses untuk intern dengan status gagal.', 400);
        }
    }

    public function filenameFor(string $certificateNumber): string
    {
        return $this->pdfService->filenameFor($certificateNumber);
    }

    public function storagePathFor(string $certificateNumber): string
    {
        return self::STORAGE_DIR.'/'.$this->filenameFor($certificateNumber);
    }

    /**
     * Resolve + validate a certificate for download (status/existence/file
     * checks), then bump download_count. Returns the storage disk path the
     * caller should stream back — API and web controllers both call this so
     * the "who can download when" rule lives in exactly one place.
     */
    public function resolveDownload(Intern $intern): array
    {
        $this->guardFailedStatus($intern);

        $certificate = Certificate::where('intern_id', $intern->id)->first();

        if (! $certificate) {
            throw new DomainActionException('Sertifikat belum tersedia.', 404);
        }

        $path = $this->storagePathFor($certificate->certificate_number);

        if (! Storage::disk('public')->exists($path)) {
            throw new DomainActionException('File sertifikat tidak ditemukan di storage.', 404);
        }

        $certificate->increment('download_count');

        return ['certificate' => $certificate, 'path' => $path];
    }

    /**
     * Keputusan: certificate_number TETAP SAMA saat regenerate (tidak
     * mengambil nomor urut baru). Nomor sertifikat adalah identitas resmi
     * dokumen untuk intern tsb; regenerate karena revisi nilai/data bukan
     * berarti itu menjadi sertifikat yang berbeda secara hukum — hanya
     * kontennya yang diperbarui. Ini juga menghindari "membakar" slot counter
     * tahunan untuk dokumen yang sebenarnya sama. issued_date tetap diupdate
     * ke waktu regenerate (tanggal re-issue terbaru).
     */
    private function issueCertificate(Intern $intern, AdminUser $admin, ?Certificate $existing): Certificate
    {
        $issuedDate = Carbon::today();
        $issuedCity = $existing->issued_city ?? 'Tangerang';

        $certificateNumber = $existing
            ? $existing->certificate_number
            : $this->numberService->generate($issuedDate);

        // Nama mentor di-snapshot saat terbit pertama kali; regenerate memakai
        // snapshot itu, bukan relasi, supaya rename/hapus mentor tidak mengubah sertifikat.
        $mentorName = $existing?->mentor_name ?: $intern->mentor?->name;

        $html = $this->pdfService->renderHtml($intern, $intern->evaluation, $certificateNumber, $issuedDate, $issuedCity, $mentorName);
        $pdfBinary = $this->pdfService->renderPdf($html);

        $path = $this->storagePathFor($certificateNumber);
        Storage::disk('public')->put($path, $pdfBinary);
        $pdfUrl = Storage::disk('public')->url($path);

        $attributes = [
            'intern_id' => $intern->id,
            'certificate_number' => $certificateNumber,
            'issued_date' => $issuedDate,
            'issued_city' => $issuedCity,
            'mentor_name' => $mentorName,
            'pdf_url' => $pdfUrl,
            // NULL, bukan NIM lagi (lihat docblock kelas ini) — di-set eksplisit
            // (bukan sekadar dihilangkan dari array) supaya regenerate juga
            // membersihkan pdf_password lama pada certificate yang sudah ada.
            'pdf_password' => null,
            'generated_by' => $admin->id,
        ];

        if ($existing) {
            $existing->update($attributes);
            $certificate = $existing->fresh();
        } else {
            $certificate = Certificate::create($attributes);
        }

        if ($intern->evaluation && $intern->evaluation->needs_certificate_regeneration) {
            $intern->evaluation->update(['needs_certificate_regeneration' => false]);
        }

        return $certificate;
    }
}
