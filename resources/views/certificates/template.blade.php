<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Sertifikat {{ $intern->full_name }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Tangerine:wght@400;700&display=swap" rel="stylesheet">
<style>
    @page {
        margin: 0;
        size: 297mm 210mm landscape;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html, body {
        width: 297mm;
        height: 210mm;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: #ffffff;
        color: #22225c;
        position: relative;
        overflow: hidden;
    }

    /* ===== Decorative corner artwork =====
       These are the real shapes cropped straight out of the design reference
       (public/images/certificate-shape-*.png, white made transparent), not
       CSS polygons — three attempts at rebuilding them with clip-path never
       matched the original geometry. Each crop is tight to the artwork's own
       bounding box in the reference, and in the reference that box sits flush
       against the page corner, so pinning the image to the same corner here
       reproduces the bleed exactly.

       Widths are the crop's true size at the reference's scale
       (2000px = 297mm, i.e. 6.734px/mm), and height is left to `auto` so the
       bitmap can never be stretched out of proportion:
         top-right    500x259px -> 74.25mm wide, scaled down ~12% to 65.35mm
                       (top:0/right:0 anchors keep the bleed intact either way)
         bottom-left  616x219px -> 91.48mm wide */
    .corner-shape {
        position: absolute;
        height: auto;
        display: block;
    }

    .corner-shape.top-right {
        top: 0;
        right: 0;
        width: 65.35mm;
    }

    .corner-shape.bottom-left {
        bottom: 0;
        left: 0;
        width: 91.48mm;
    }

    /* ===== Logo (real image asset, not re-created with text/CSS) ===== */
    .logo {
        position: absolute;
        top: 11mm;
        left: 9mm;
        z-index: 5;
        width: 78mm;
    }

    .logo img {
        display: block;
        width: 100%;
        height: auto;
    }

    /* ===== Main content ===== */
    .content {
        position: absolute;
        inset: 0;
        z-index: 2;
    }

    .title-block {
        position: absolute;
        top: 37mm;
        left: 12mm;
    }

    .title-block h1 {
        font-family: 'Poppins', sans-serif;
        font-weight: 800;
        font-size: 58pt;
        line-height: 1;
        letter-spacing: 0;
        color: #22225c;
        white-space: nowrap;
    }

    .title-block .cert-no {
        font-size: 11pt;
        font-weight: 500;
        letter-spacing: 2px;
        color: #22225c;
        margin-top: 1.5mm;
    }

    /* ribbon — flush to the left page edge (bleeds off), navy body with a
       lighter cyan chevron tail */
    .ribbon {
        position: absolute;
        top: 68mm;
        left: 0;
        display: flex;
        align-items: stretch;
        height: 14mm;
    }

    .ribbon-body {
        background: #22225c;
        color: #ffffff;
        font-weight: 700;
        font-size: 15pt;
        letter-spacing: 3px;
        display: flex;
        align-items: center;
        padding: 0 10mm 0 14mm;
        clip-path: polygon(0 0, 100% 0, 88% 100%, 0% 100%);
    }

    .ribbon-tail {
        width: 22mm;
        background: linear-gradient(90deg, #22225c 0%, #38b6e6 100%);
        clip-path: polygon(0 0, 70% 0, 40% 100%, 0 100%);
        margin-left: -6mm;
    }

    /* Right-hand info column. All three right-aligned lines (kepada / NIM /
       tanggal) share one right margin so their right edges line up exactly.
       19mm is measured off the reference, where those lines end at ~278mm of
       the 297mm page — the previous 80mm left them stranded mid-page, which
       read as "centered" rather than right-aligned. */
    .given-to {
        position: absolute;
        top: 85mm;
        left: 62mm;
        right: 19mm;
        text-align: right;
        font-size: 13pt;
        font-style: italic;
    }

    .participant-name {
        position: absolute;
        top: 90mm;
        left: 62mm;
        right: 20mm;
        font-family: 'Tangerine', cursive;
        font-weight: 700;
        font-size: 80pt;
        line-height: 1;
        color: #22225c;
    }

    .participant-nim {
        position: absolute;
        top: 116mm;
        left: 62mm;
        right: 19mm;
        text-align: right;
        font-size: 13pt;
        font-weight: 600;
    }

    .description {
        position: absolute;
        top: 126mm;
        left: 62mm;
        right: 24mm;
        font-size: 17pt;
        line-height: 1.55;
        text-align: left;
    }

    .issue-line {
        position: absolute;
        top: 148.5mm;
        left: 62mm;
        right: 19mm;
        text-align: right;
        font-size: 13pt;
    }

    /* signatures */
    .signatures {
        position: absolute;
        top: 174mm;
        left: 62mm;
        right: 24mm;
        display: flex;
        justify-content: space-between;
        z-index: 3;
    }

    .signature {
        width: 62mm;
        text-align: center;
    }

    .signature.right {
        margin-left: auto;
    }

    .signature .sig-line {
        border-top: 1.5px solid #22225c;
        margin-bottom: 3mm;
    }

    .signature .sig-name {
        font-weight: 700;
        font-size: 13pt;
        color: #22225c;
    }

    .signature .sig-role {
        font-size: 9.5pt;
        letter-spacing: 2px;
        color: #22225c;
        text-transform: uppercase;
        margin-top: 1mm;
    }
</style>
</head>
<body>
    {{-- Base64-inlined for the same reason as the logo: Browsershot renders a
         raw HTML string with no base URL, so relative asset paths never resolve. --}}
    <img class="corner-shape top-right"
         src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/certificate-shape-top-right.png'))) }}"
         alt="">
    <img class="corner-shape bottom-left"
         src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/certificate-shape-bottom-left.png'))) }}"
         alt="">

    <div class="logo">
        <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logistax">
    </div>

    <div class="content">
        <div class="title-block">
            <h1>SERTIFIKAT</h1>
            <div class="cert-no">NO : {{ $certificateNumber }}</div>
        </div>

        <div class="ribbon">
            <div class="ribbon-body">PENGHARGAAN</div>
            <div class="ribbon-tail"></div>
        </div>

        <div class="given-to">Di berikan kepada:</div>

        <div class="participant-name">{{ $intern->full_name }}</div>

        <div class="participant-nim">NIM : {{ $intern->nim }}</div>

        <div class="description">
            To complete the internship program from {{ $intern->start_date->format('F d') }}
            to {{ $intern->end_date->format('F d') }}, {{ $intern->end_date->format('Y') }}
            at PT Logistax Mitratama Solusi (a tax consulting company), achieving an
            &ldquo;{{ $evaluation->grade }}&rdquo; grade.
        </div>

        <div class="issue-line">{{ $issuedCity }}, {{ $issuedDate->format('F d, Y') }}</div>

        <div class="signatures">
            <div class="signature left">
                <div class="sig-line"></div>
                <div class="sig-name">Hardi, S.E., BKP</div>
                <div class="sig-role">Director</div>
            </div>
            <div class="signature right">
                <div class="sig-line"></div>
                <div class="sig-name">
                    {{ $intern->mentor?->name }}{{ $intern->mentor?->title ? ', '.$intern->mentor->title : '' }}
                </div>
                <div class="sig-role">Supervisor</div>
            </div>
        </div>
    </div>
</body>
</html>
