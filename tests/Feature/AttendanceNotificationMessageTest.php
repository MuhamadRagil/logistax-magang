<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Services\AttendanceNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsAttendanceFixtures;
use Tests\TestCase;

/**
 * Exact message text only — nothing is sent here.
 */
class AttendanceNotificationMessageTest extends TestCase
{
    use BuildsAttendanceFixtures;
    use RefreshDatabase;

    private Attendance $andi;

    private Attendance $eko;

    protected function setUp(): void
    {
        parent::setUp();

        $andi = $this->makeIntern('Andi Pratama');
        $budi = $this->makeIntern('Budi Santoso');
        $citra = $this->makeIntern('Citra Ayu');
        $dewi = $this->makeIntern('Dewi Lestari', 'extended');
        $eko = $this->makeIntern('Eko Saputra');
        // Not counted in total: only active + extended are.
        $this->makeIntern('Fajar Nugroho', 'completed');
        $this->makeIntern('Gita Ramadhani', 'pending');

        // 28 Sep 2026. Budi's 06:58 WIB is 23:58 UTC on the 27th — still
        // belongs to the 28th's roster (record date) and must print as 06:58.
        $this->andi = $this->attend($andi, '2026-09-28', '2026-09-28 08:05', '2026-09-28 16:45');
        $this->attend($budi, '2026-09-28', '2026-09-28 06:58', '2026-09-28 17:02');
        $this->attend($dewi, '2026-09-28', '2026-09-28 08:30');
        $this->attend($citra, '2026-09-28', null, null, 'izin'); // izin: not listed

        // Another day: never mixed into the 28th.
        $this->eko = $this->attend($eko, '2026-09-27', '2026-09-27 07:40', '2026-09-27 17:10');
    }

    public function test_check_in_message_matches_format_exactly(): void
    {
        $expected = "✅ Andi Pratama masuk 08:05 WIB\n"
            ."\n"
            ."👥 Hadir hari ini (3/5):\n"
            ."1. Budi Santoso — 06:58\n"
            ."2. Andi Pratama — 08:05\n"
            .'3. Dewi Lestari — 08:30';

        $this->assertSame($expected, app(AttendanceNotificationService::class)->checkInMessage($this->andi));
    }

    public function test_check_out_message_matches_format_exactly(): void
    {
        $expected = "🏁 Andi Pratama keluar 16:45 WIB\n"
            ."\n"
            ."👥 Sudah checkout (2/5):\n"
            ."1. Andi Pratama — 16:45\n"
            .'2. Budi Santoso — 17:02';

        $this->assertSame($expected, app(AttendanceNotificationService::class)->checkOutMessage($this->andi));
    }

    public function test_roster_uses_the_record_date_not_now(): void
    {
        $this->travelTo($this->wib('2026-10-02 09:00'));
        $service = app(AttendanceNotificationService::class);

        $this->assertStringContainsString('👥 Hadir hari ini (3/5):', $service->checkInMessage($this->andi->fresh()));

        $this->assertSame(
            "✅ Eko Saputra masuk 07:40 WIB\n\n👥 Hadir hari ini (1/5):\n1. Eko Saputra — 07:40",
            $service->checkInMessage($this->eko->fresh()),
        );
    }

    public function test_building_messages_sends_nothing(): void
    {
        Http::fake();

        $service = app(AttendanceNotificationService::class);
        $service->checkInMessage($this->andi);
        $service->checkOutMessage($this->andi);

        Http::assertNothingSent();
    }
}
