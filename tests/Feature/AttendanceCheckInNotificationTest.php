<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Intern;
use App\Models\InternAccount;
use App\Models\OfficeLocation;
use App\Services\FonnteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsAttendanceFixtures;
use Tests\TestCase;

/**
 * Through the real API endpoints, so the after-response job actually runs
 * (the test client terminates the kernel like a real request does).
 */
class AttendanceCheckInNotificationTest extends TestCase
{
    use BuildsAttendanceFixtures;
    use RefreshDatabase;

    private const GROUP = '120363000000000000@g.us';

    private const IN_RADIUS = ['latitude' => -6.1774, 'longitude' => 106.6319];

    private Intern $andi;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fonnte.enabled' => true,
            'services.fonnte.token' => 'TEST-TOKEN',
            'services.fonnte.group_id' => self::GROUP,
        ]);

        OfficeLocation::create([
            'name' => 'Kantor Pusat', 'latitude' => -6.1783, 'longitude' => 106.6319,
            'radius_meters' => 150, 'is_active' => true,
        ]);

        $account = InternAccount::create(['email' => 'andi@test.local', 'password' => 'secret123', 'is_verified' => true]);
        $this->andi = $this->makeIntern('Andi Pratama', 'active', ['intern_account_id' => $account->id]);
        Sanctum::actingAs($account);

        // Budi already checked in earlier today; one more active intern who hasn't.
        $this->attend($this->makeIntern('Budi Santoso'), '2026-09-28', '2026-09-28 07:58');
        $this->makeIntern('Citra Ayu');

        $this->travelTo($this->wib('2026-09-28 08:05'));
    }

    public function test_check_in_sends_exact_group_message(): void
    {
        Http::fake([FonnteService::ENDPOINT => Http::response(['status' => true])]);

        $response = $this->postJson('/api/attendance/check-in', self::IN_RADIUS);

        $response->assertStatus(201)
            ->assertExactJsonStructure(['success', 'data', 'message'])
            ->assertJson(['success' => true, 'message' => 'Check-in berhasil.']);
        $this->assertSame((string) strlen($response->getContent()), $response->headers->get('Content-Length'));

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r['target'] === self::GROUP && $r['message'] ===
            "✅ Andi Pratama masuk 08:05 WIB\n"
            ."\n"
            ."👥 Hadir hari ini (2/3):\n"
            ."1. Budi Santoso — 07:58\n"
            .'2. Andi Pratama — 08:05');
    }

    public function test_check_out_sends_exact_group_message(): void
    {
        // Check-in as a fixture, not a second request: the test app instance
        // is shared across requests and Application::terminate() never clears
        // its callbacks, so a previous request's after-response job would run
        // again here. (In production every request is a fresh PHP execution.)
        $this->attend($this->andi, '2026-09-28', '2026-09-28 08:05');
        $this->storeDatesLikeMysql();
        Http::fake([FonnteService::ENDPOINT => Http::response(['status' => true])]);

        $this->travelTo($this->wib('2026-09-28 16:45'));
        $response = $this->postJson('/api/attendance/check-out', self::IN_RADIUS);

        $response->assertOk()
            ->assertExactJsonStructure(['success', 'data', 'message'])
            ->assertJson(['success' => true, 'message' => 'Check-out berhasil.']);
        $this->assertSame((string) strlen($response->getContent()), $response->headers->get('Content-Length'));
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r['message'] ===
            "🏁 Andi Pratama keluar 16:45 WIB\n"
            ."\n"
            ."👥 Sudah checkout (1/3):\n"
            .'1. Andi Pratama — 16:45');
    }

    public function test_disabled_sends_nothing_and_check_in_still_works(): void
    {
        Http::fake();
        config(['services.fonnte.enabled' => false]);

        $this->postJson('/api/attendance/check-in', self::IN_RADIUS)->assertStatus(201);

        Http::assertNothingSent();
    }

    public function test_fonnte_500_does_not_fail_check_in(): void
    {
        Http::fake([FonnteService::ENDPOINT => Http::response('Internal Server Error', 500)]);

        $this->postJson('/api/attendance/check-in', self::IN_RADIUS)
            ->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Check-in berhasil.']);

        $this->assertTrue(Attendance::where('intern_id', $this->andi->id)->whereNotNull('check_in_time')->exists());
        Http::assertSentCount(1);
    }

    public function test_fonnte_timeout_does_not_fail_check_in_or_check_out(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 8001 milliseconds'));

        $this->postJson('/api/attendance/check-in', self::IN_RADIUS)->assertStatus(201);
        $this->storeDatesLikeMysql();
        $this->travelTo($this->wib('2026-09-28 16:45'));
        $this->postJson('/api/attendance/check-out', self::IN_RADIUS)->assertOk();

        $this->assertNotNull(Attendance::where('intern_id', $this->andi->id)->first()->check_out_time);
    }

    /**
     * SQLite-only shim. The `date` cast writes "2026-09-28 00:00:00" as text
     * on SQLite, and AttendanceController::checkOut() looks the record up
     * with where('date', 'Y-m-d') — which matches on MySQL (DATE column,
     * production) but not here. Rewrite the value the way MySQL stores it
     * rather than touch attendance logic (out of scope).
     */
    private function storeDatesLikeMysql(): void
    {
        foreach (DB::table('attendances')->get(['id', 'date']) as $row) {
            DB::table('attendances')->where('id', $row->id)->update(['date' => substr($row->date, 0, 10)]);
        }
    }

    public function test_rejected_check_in_sends_nothing(): void
    {
        Http::fake();

        $this->postJson('/api/attendance/check-in', ['latitude' => -6.1873, 'longitude' => 106.6319])
            ->assertStatus(422);

        Http::assertNothingSent();
    }
}
