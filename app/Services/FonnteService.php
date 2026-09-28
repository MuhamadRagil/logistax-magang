<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * WhatsApp sender via Fonnte (https://docs.fonnte.com): POST /send with the
 * device token in the Authorization header and form fields target/message.
 *
 * Notifications are best-effort: every method returns a boolean and never
 * throws, so a Fonnte outage can't fail a check-in or a certificate. Failures
 * are logged without the token (headers are never logged, and the token is
 * redacted from any error text as a second line of defence).
 */
class FonnteService
{
    public const ENDPOINT = 'https://api.fonnte.com/send';

    private const TIMEOUT_SECONDS = 8;

    /** Indonesian numbers: lets Fonnte turn a local 08xx number into 628xx. */
    private const COUNTRY_CODE = '62';

    public function isEnabled(): bool
    {
        return (bool) config('services.fonnte.enabled') && $this->token() !== '';
    }

    public function sendToGroup(string $message): bool
    {
        return $this->send((string) config('services.fonnte.group_id'), $message, 'group');
    }

    public function sendToPhone(?string $phone, string $message): bool
    {
        return $this->send((string) $phone, $message, 'phone', ['countryCode' => self::COUNTRY_CODE]);
    }

    private function send(string $target, string $message, string $kind, array $extra = []): bool
    {
        $target = trim($target);

        if (! $this->isEnabled() || $target === '' || trim($message) === '') {
            return false;
        }

        $token = $this->token();

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout(self::TIMEOUT_SECONDS)
                ->post(self::ENDPOINT, ['target' => $target, 'message' => $message] + $extra);

            // Fonnte answers HTTP 200 with {"status": false, "reason": ...} for
            // most failures (invalid token, disconnected device, bad target).
            if ($response->successful() && $response->json('status') === true) {
                return true;
            }

            Log::warning('Fonnte: pesan tidak terkirim.', [
                'kind' => $kind,
                'target' => $this->maskTarget($target),
                'http_status' => $response->status(),
                'reason' => $this->redact((string) ($response->json('reason') ?? Str::limit($response->body(), 200)), $token),
            ]);
        } catch (Throwable $e) {
            Log::warning('Fonnte: gagal menghubungi API.', [
                'kind' => $kind,
                'target' => $this->maskTarget($target),
                'error' => $e::class.': '.$this->redact(Str::limit($e->getMessage(), 300), $token),
            ]);
        }

        return false;
    }

    private function token(): string
    {
        return trim((string) config('services.fonnte.token'));
    }

    private function redact(string $text, string $token): string
    {
        return $token === '' ? $text : str_replace($token, '[token]', $text);
    }

    /** Enough to tell targets apart in logs without writing out a phone number or group id. */
    private function maskTarget(string $target): string
    {
        return '***'.substr($target, -4);
    }
}
