<?php

namespace Tests\Feature;

use App\Jobs\SendCertificateReadyNotification;
use App\Models\AdminUser;
use App\Models\Certificate;
use App\Models\Evaluation;
use App\Models\Intern;
use App\Services\CertificatePdfService;
use App\Services\FonnteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsAttendanceFixtures;
use Tests\TestCase;

/**
 * Each test makes exactly one request (see AttendanceCheckInNotificationTest
 * on why after-response callbacks can't be spread over several requests).
 */
class CertificateReadyNotificationTest extends TestCase
{
    use BuildsAttendanceFixtures;
    use RefreshDatabase;

    private AdminUser $admin;

    private Intern $intern;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fonnte.enabled' => true,
            'services.fonnte.token' => 'TEST-TOKEN',
            'services.fonnte.group_id' => '120363000000000000@g.us',
        ]);

        Storage::fake('public');
        // Browsershot is out of scope here: stub rendering, keep filenameFor() real.
        $this->partialMock(CertificatePdfService::class, function ($mock) {
            $mock->shouldReceive('renderHtml')->andReturn('<html></html>');
            $mock->shouldReceive('renderPdf')->andReturn('%PDF-1.4 stub');
        });

        $this->admin = AdminUser::create([
            'name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret123',
            'role' => 'admin_magang', 'is_active' => true,
        ]);
        Sanctum::actingAs($this->admin);

        $this->intern = $this->makeIntern('Andi Pratama', 'completed', ['phone' => '081234567890']);
        Evaluation::create([
            'intern_id' => $this->intern->id, 'evaluated_by' => $this->admin->id,
            'discipline_score' => 90, 'performance_score' => 88, 'attitude_score' => 92,
            'communication_score' => 85, 'total_score' => 88.75, 'grade' => 'A',
        ]);
    }

    public function test_first_generate_sends_personal_message(): void
    {
        Http::fake([FonnteService::ENDPOINT => Http::response(['status' => true])]);

        $this->postJson("/api/certificates/generate/{$this->intern->id}")->assertStatus(201);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r['target'] === '081234567890'
            && $r['countryCode'] === '62'
            && $r['message'] === 'Halo Andi Pratama, sertifikat magang kamu sudah tersedia. Buka aplikasi menu Sertifikat untuk mengunduhnya.');
        $this->assertSame(SendCertificateReadyNotification::message('Andi Pratama'), Http::recorded()[0][0]['message']);
    }

    public function test_regenerate_sends_nothing(): void
    {
        $this->existingCertificate();
        Http::fake();

        $this->postJson("/api/certificates/regenerate/{$this->intern->id}")->assertOk();

        Http::assertNothingSent();
    }

    public function test_generate_that_reissues_an_existing_certificate_sends_nothing(): void
    {
        $this->existingCertificate();
        $this->intern->evaluation->update(['needs_certificate_regeneration' => true]);
        Http::fake();

        $this->postJson("/api/certificates/generate/{$this->intern->id}")->assertOk();

        Http::assertNothingSent();
    }

    public function test_preview_sends_nothing(): void
    {
        Http::fake();

        $this->get("/api/certificates/preview/{$this->intern->id}")->assertOk();

        Http::assertNothingSent();
    }

    public function test_intern_without_phone_is_skipped_without_error(): void
    {
        $this->intern->update(['phone' => null]);
        Http::fake();

        $this->postJson("/api/certificates/generate/{$this->intern->id}")->assertStatus(201);

        Http::assertNothingSent();
    }

    public function test_fonnte_failure_does_not_fail_generate(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->postJson("/api/certificates/generate/{$this->intern->id}")
            ->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Sertifikat berhasil digenerate.']);

        $this->assertTrue(Certificate::where('intern_id', $this->intern->id)->exists());
    }

    private function existingCertificate(): void
    {
        Certificate::create([
            'intern_id' => $this->intern->id,
            'certificate_number' => '001/LOGISTAX/INTERN/IX/2026',
            'issued_date' => '2026-09-01',
            'issued_city' => 'Tangerang',
            'pdf_url' => 'http://localhost/storage/certificates/001-LOGISTAX-INTERN-IX-2026.pdf',
            'generated_by' => $this->admin->id,
        ]);
    }
}
