<?php

namespace App\Console\Commands;

use App\Services\FonnteService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Configuration check for Railway Console: `php artisan fonnte:test`.
 * Prints only whether each variable is set — never the token or group id.
 */
class FonnteTestCommand extends Command
{
    public const MESSAGE = 'Tes notifikasi sistem magang';

    protected $signature = 'fonnte:test';

    protected $description = 'Kirim "Tes notifikasi sistem magang" ke grup WhatsApp Fonnte untuk verifikasi konfigurasi';

    public function handle(FonnteService $fonnte): int
    {
        $enabled = (bool) config('services.fonnte.enabled');
        $token = trim((string) config('services.fonnte.token'));
        $groupId = trim((string) config('services.fonnte.group_id'));

        $this->line('FONNTE_ENABLED  : '.($enabled ? 'true' : 'false'));
        $this->line('FONNTE_TOKEN    : '.($token !== '' ? 'terisi' : 'KOSONG'));
        $this->line('FONNTE_GROUP_ID : '.match (true) {
            $groupId === '' => 'KOSONG',
            Str::endsWith($groupId, '@g.us') => 'terisi',
            default => 'terisi, tapi formatnya bukan ...@g.us — periksa lagi',
        });

        if (! $enabled || $token === '' || $groupId === '') {
            $this->error('Konfigurasi belum lengkap, pesan tidak dikirim.');

            return self::FAILURE;
        }

        if ($fonnte->sendToGroup(self::MESSAGE)) {
            $this->info('Terkirim. Cek grup WhatsApp.');

            return self::SUCCESS;
        }

        $this->error('Gagal mengirim. Detail penyebab ada di log (Railway → Deploy Logs, "Fonnte: ...").');

        return self::FAILURE;
    }
}
