<?php

namespace App\Jobs;

use App\Models\Intern;
use App\Services\FonnteService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Personal WhatsApp message to the intern after their certificate is
 * generated for the FIRST time (see CertificateIssuingService::generate()).
 * Same after-response, no-queue-worker approach as SendAttendanceNotification.
 */
class SendCertificateReadyNotification
{
    use Dispatchable;

    public function __construct(public readonly string $internId) {}

    public static function message(string $name): string
    {
        return "Halo {$name}, sertifikat magang kamu sudah tersedia. Buka aplikasi menu Sertifikat untuk mengunduhnya.";
    }

    public function handle(FonnteService $fonnte): void
    {
        if (! $fonnte->isEnabled()) {
            return;
        }

        try {
            $intern = Intern::find($this->internId);

            // No phone on file: nothing to send, not an error.
            if (! $intern || blank($intern->phone)) {
                return;
            }

            $fonnte->sendToPhone($intern->phone, self::message($intern->full_name));
        } catch (Throwable $e) {
            Log::warning('Notifikasi sertifikat gagal dikirim.', [
                'intern_id' => $this->internId,
                'error' => $e::class.': '.$e->getMessage(),
            ]);
        }
    }
}
