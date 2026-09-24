<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\DomainActionException;
use App\Models\AdminUser;
use App\Models\Intern;
use App\Services\CertificateIssuingService;
use App\Services\CertificateNumberService;
use App\Services\CertificatePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateIssuingService $issuingService,
        private readonly CertificateNumberService $numberService,
        private readonly CertificatePdfService $pdfService,
    ) {}

    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');
        $isSpv = $admin->role === 'spv_mentor';
        $tab = $request->query('tab', 'cert');

        $interns = Intern::query()
            ->with(['certificate', 'evaluation'])
            ->when($isSpv, fn ($q) => $q->where('mentor_id', $admin->id))
            ->where('status', 'completed')
            ->orderByDesc('end_date')
            ->get();

        return view('certificates.index', [
            'activeTab' => $tab,
            'interns' => $interns,
        ]);
    }

    public function generate(Request $request, Intern $intern): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');

        try {
            $this->issuingService->generate($intern, $admin);
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Sertifikat berhasil digenerate.');
    }

    public function regenerate(Request $request, Intern $intern): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');

        try {
            $this->issuingService->regenerate($intern, $admin);
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Sertifikat berhasil digenerate ulang.');
    }

    public function download(Request $request, Intern $intern)
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');

        if ($admin->role === 'spv_mentor' && $intern->mentor_id !== $admin->id) {
            abort(403);
        }

        try {
            $resolved = $this->issuingService->resolveDownload($intern);
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return Storage::disk('public')->download($resolved['path'], "Sertifikat-{$intern->nim}.pdf");
    }

    /**
     * Same rendering path as Api\CertificateController::preview() (dummy
     * number, no password, nothing persisted) — just delivered to a
     * session-authenticated browser tab instead of a Sanctum API client.
     */
    public function preview(Request $request, Intern $intern): Response
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');

        if ($admin->role === 'spv_mentor' && $intern->mentor_id !== $admin->id) {
            abort(403);
        }

        $intern->loadMissing(['mentor', 'evaluation']);

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
}
