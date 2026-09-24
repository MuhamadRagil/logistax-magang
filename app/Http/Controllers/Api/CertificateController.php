<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DomainActionException;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Certificate;
use App\Models\Intern;
use App\Models\InternAccount;
use App\Services\CertificateIssuingService;
use App\Services\CertificateNumberService;
use App\Services\CertificatePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateNumberService $numberService,
        private readonly CertificatePdfService $pdfService,
        private readonly CertificateIssuingService $issuingService,
    ) {}

    public function generate(Request $request, string $internId)
    {
        /** @var AdminUser $admin */
        $admin = $request->user();
        $intern = Intern::with(['mentor', 'evaluation'])->findOrFail($internId);

        $existedBefore = Certificate::where('intern_id', $intern->id)->exists();

        try {
            $certificate = $this->issuingService->generate($intern, $admin);
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return $this->success(
            $certificate,
            $existedBefore ? 'Sertifikat berhasil digenerate ulang.' : 'Sertifikat berhasil digenerate.',
            $existedBefore ? 200 : 201
        );
    }

    /**
     * Keputusan: endpoint ini TIDAK dibatasi hanya saat
     * needs_certificate_regeneration = true — admin_magang boleh regenerate
     * kapan saja selama certificate sudah pernah dibuat (mis. untuk perbaikan
     * data lain seperti issued_city yang salah ketik, bukan cuma karena nilai
     * berubah). Lihat README untuk penjelasan lengkap.
     */
    public function regenerate(Request $request, string $internId)
    {
        /** @var AdminUser $admin */
        $admin = $request->user();
        $intern = Intern::with(['mentor', 'evaluation'])->findOrFail($internId);

        try {
            $certificate = $this->issuingService->regenerate($intern, $admin);
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return $this->success($certificate, 'Sertifikat berhasil digenerate ulang.');
    }

    public function preview(Request $request, string $internId): Response
    {
        $intern = Intern::with(['mentor', 'evaluation'])->findOrFail($internId);

        abort_if($intern->status === 'failed', 400, 'Sertifikat tidak dapat diproses untuk intern dengan status gagal.');
        abort_if(! $this->issuingService->hasCompleteEvaluation($intern), 400, 'Evaluasi untuk intern ini belum ada atau belum lengkap, sertifikat tidak bisa dipreview.');

        $issuedDate = Carbon::today();
        $certificateNumber = $this->numberService->previewNumber($issuedDate);

        $html = $this->pdfService->renderHtml($intern, $intern->evaluation, $certificateNumber, $issuedDate, 'Tangerang');
        $pdfBinary = $this->pdfService->renderPdf($html);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview-sertifikat.pdf"',
        ]);
    }

    public function show(Request $request, string $internId)
    {
        /** @var AdminUser $admin */
        $admin = $request->user();
        $intern = Intern::findOrFail($internId);

        if ($admin->role === 'spv_mentor' && $intern->mentor_id !== $admin->id) {
            return $this->error('Anda bukan mentor dari intern ini.', 403);
        }

        return $this->certificateMetadataResponse($intern);
    }

    public function myCertificate(Request $request)
    {
        /** @var InternAccount $account */
        $account = $request->user();
        $intern = $account->intern;

        if (! $intern) {
            return $this->error('Data intern tidak ditemukan untuk akun ini.', 404);
        }

        return $this->certificateMetadataResponse($intern);
    }

    public function download(Request $request, string $internId)
    {
        /** @var AdminUser $admin */
        $admin = $request->user();
        $intern = Intern::findOrFail($internId);

        if ($admin->role === 'spv_mentor' && $intern->mentor_id !== $admin->id) {
            return $this->error('Anda bukan mentor dari intern ini.', 403);
        }

        return $this->downloadCertificateFor($intern);
    }

    public function downloadMyCertificate(Request $request)
    {
        /** @var InternAccount $account */
        $account = $request->user();
        $intern = $account->intern;

        if (! $intern) {
            return $this->error('Data intern tidak ditemukan untuk akun ini.', 404);
        }

        return $this->downloadCertificateFor($intern);
    }

    private function certificateMetadataResponse(Intern $intern)
    {
        $certificate = Certificate::where('intern_id', $intern->id)->first();

        if (! $certificate) {
            return $this->success(null, 'Sertifikat belum tersedia.');
        }

        return $this->success($certificate);
    }

    private function downloadCertificateFor(Intern $intern)
    {
        try {
            $resolved = $this->issuingService->resolveDownload($intern);
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return Storage::disk('public')->download($resolved['path'], "Sertifikat-{$intern->nim}.pdf");
    }
}
