<?php

namespace App\Services;

use App\Models\Evaluation;
use App\Models\Intern;
use Carbon\CarbonInterface;
use setasign\Fpdi\Tcpdf\Fpdi;
use Spatie\Browsershot\Browsershot;

class CertificatePdfService
{
    public function renderHtml(
        Intern $intern,
        Evaluation $evaluation,
        string $certificateNumber,
        CarbonInterface $issuedDate,
        string $issuedCity,
    ): string {
        return view('certificates.template', [
            'intern' => $intern,
            'evaluation' => $evaluation,
            'certificateNumber' => $certificateNumber,
            'issuedDate' => $issuedDate,
            'issuedCity' => $issuedCity,
            'logoBase64' => $this->logoBase64(),
        ])->render();
    }

    /**
     * The logo is embedded as a base64 data URI (rather than referenced by a
     * URL/relative path) so it always renders regardless of Browsershot's
     * working directory or network access — Browsershot::html() renders a
     * raw HTML string with no base URL to resolve relative asset paths against.
     *
     * Uses the trimmed PNG (public/images/logo-logistax-trimmed.png), a
     * whitespace-cropped copy of the original public/images/logo-logistax.jpeg
     * asset — same logo, just without the large blank canvas margin baked
     * into the source file, which otherwise forced an oversized bounding box
     * that overlapped the "SERTIFIKAT" title beneath it.
     */
    private function logoBase64(): string
    {
        return base64_encode(file_get_contents(public_path('images/logo-logistax-trimmed.png')));
    }

    /**
     * Render HTML into a raw (unprotected) PDF binary via headless Chrome.
     */
    public function renderPdf(string $html): string
    {
        // Page size is set explicitly here (297x210mm = A4 landscape, the exact
        // dimensions the template is designed at: html/body are 297mm x 210mm).
        // We DON'T use preferCSSPageSize() because the template's
        // `@page { size: 297mm 210mm landscape }` is invalid CSS — two explicit
        // lengths can't be combined with an orientation keyword — so Chromium
        // ignores it and falls back to its default page (US Letter portrait),
        // leaving the landscape design stranded in the top-left. Margins are 0
        // so Chromium adds no white padding around the full-bleed artwork.
        // Fonts are inlined as data URIs, but decoding them is still async —
        // wait until the browser reports every @font-face ready, otherwise
        // text can print blank (seen with Nix Chromium on Railway). The short
        // delay is a safety margin for layout after the fonts swap in.
        $browsershot = Browsershot::html($html)
            ->showBackground()
            ->paperSize(297, 210, 'mm')
            ->margins(0, 0, 0, 0)
            ->waitUntilNetworkIdle()
            ->waitForFunction('document.fonts.status === "loaded"', timeout: 10000)
            ->setDelay(300)
            ->noSandbox();

        if ($nodeBinary = config('browsershot.node_binary')) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        if ($npmBinary = config('browsershot.npm_binary')) {
            $browsershot->setNpmBinary($npmBinary);
        }

        if ($chromePath = config('browsershot.chrome_path')) {
            $browsershot->setChromePath($chromePath);
        }

        return $browsershot->pdf();
    }

    /**
     * Re-open a PDF with FPDI (importing every page into a fresh TCPDF
     * document) and apply a user password. Browsershot/Chrome has no native
     * PDF-encryption option, so protection is a separate pass.
     *
     * Currently unused: keputusan produk menghentikan password protection
     * pada sertifikat (lihat CertificateIssuingService::issueCertificate()).
     * Method + package setasign/fpdi-tcpdf sengaja belum dihapus — dibersihkan
     * terpisah kalau memang tidak akan dipakai lagi.
     */
    public function protectWithPassword(string $pdfBinary, string $password): string
    {
        $tempInput = tempnam(sys_get_temp_dir(), 'cert_src_');
        file_put_contents($tempInput, $pdfBinary);

        try {
            $pdf = new Fpdi;
            $pageCount = $pdf->setSourceFile($tempInput);

            for ($page = 1; $page <= $pageCount; $page++) {
                $templateId = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }

            $pdf->SetProtection(['print'], $password, null, 0, null);

            return $pdf->Output('', 'S');
        } finally {
            @unlink($tempInput);
        }
    }

    /**
     * Deterministic, filesystem-safe filename derived from a certificate
     * number (e.g. "001/LOGISTAX/INTERN/V/2026" -> "001-LOGISTAX-INTERN-V-2026.pdf").
     * Re-derivable from the stored certificate_number, so no extra "path"
     * column is needed on the certificates table.
     */
    public function filenameFor(string $certificateNumber): string
    {
        $safe = preg_replace('/[^A-Za-z0-9\-_.]/', '-', $certificateNumber);

        return $safe.'.pdf';
    }
}
