<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Evaluation;
use App\Models\ExtensionLog;
use App\Models\InternAccount;
use App\Services\CertificateIssuingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsAttendanceFixtures;
use Tests\TestCase;

class InternBulkDeleteTest extends TestCase
{
    use BuildsAttendanceFixtures;
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = AdminUser::create([
            'name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret123',
            'role' => 'admin_magang', 'is_active' => true,
        ]);
    }

    public function test_deletes_intern_with_no_related_data(): void
    {
        $this->actingAs($this->admin, 'web');
        $intern = $this->makeIntern('Solo Intern');

        $response = $this->post('/interns/bulk-delete', ['intern_ids' => [$intern->id]]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('interns', ['id' => $intern->id]);
        $this->assertSame('1 intern berhasil dihapus permanen beserta seluruh data terkait.', session('status'));
    }

    public function test_deletes_intern_with_full_related_data_and_files(): void
    {
        $this->actingAs($this->admin, 'web');

        $account = InternAccount::create(['email' => 'full@test.local', 'password' => 'secret123', 'is_verified' => true]);
        $intern = $this->makeIntern('Full Intern', 'completed', ['intern_account_id' => $account->id]);

        // Attendance with a real proof file on the fake disk.
        $proofPath = 'attendance-proofs/'.uniqid().'.jpg';
        Storage::disk('public')->put($proofPath, 'fake-image-bytes');
        $proofUrl = Storage::disk('public')->url($proofPath);
        $attendance = Attendance::create([
            'intern_id' => $intern->id, 'date' => '2026-09-01', 'status' => 'sakit',
            'proof_file_url' => $proofUrl, 'approval_status' => 'approved',
        ]);
        $this->attend($intern, '2026-09-02', '2026-09-02 08:00', '2026-09-02 17:00');

        $evaluation = Evaluation::create([
            'intern_id' => $intern->id, 'evaluated_by' => $this->admin->id,
            'discipline_score' => 90, 'performance_score' => 88, 'attitude_score' => 92,
            'communication_score' => 85, 'total_score' => 88.75, 'grade' => 'A',
        ]);

        $certNumber = '001/LOGISTAX/INTERN/IX/2026';
        $certPath = app(CertificateIssuingService::class)->storagePathFor($certNumber);
        Storage::disk('public')->put($certPath, '%PDF-1.4 fake certificate bytes');
        $certificate = Certificate::create([
            'intern_id' => $intern->id, 'certificate_number' => $certNumber,
            'issued_date' => '2026-09-15', 'issued_city' => 'Tangerang',
            'pdf_url' => Storage::disk('public')->url($certPath), 'generated_by' => $this->admin->id,
        ]);

        $extensionLog = ExtensionLog::create([
            'intern_id' => $intern->id, 'old_end_date' => '2026-06-01', 'new_end_date' => '2026-09-01',
            'reason' => 'Perpanjangan uji', 'extended_by' => $this->admin->id,
        ]);

        Storage::disk('public')->assertExists($proofPath);
        Storage::disk('public')->assertExists($certPath);

        $response = $this->post('/interns/bulk-delete', ['intern_ids' => [$intern->id]]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('interns', ['id' => $intern->id]);
        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
        $this->assertDatabaseMissing('evaluations', ['id' => $evaluation->id]);
        $this->assertDatabaseMissing('certificates', ['id' => $certificate->id]);
        $this->assertDatabaseMissing('extension_logs', ['id' => $extensionLog->id]);
        $this->assertDatabaseMissing('intern_accounts', ['id' => $account->id]);
        Storage::disk('public')->assertMissing($proofPath);
        Storage::disk('public')->assertMissing($certPath);
    }

    public function test_deletes_a_pending_self_registered_intern(): void
    {
        $this->actingAs($this->admin, 'web');

        $account = InternAccount::create(['email' => 'pending@test.local', 'password' => 'secret123', 'is_verified' => true]);
        $intern = $this->makeIntern('Pending Intern', 'pending', [
            'intern_account_id' => $account->id, 'registered_via' => 'self', 'division_id' => null, 'mentor_id' => null,
        ]);

        $response = $this->post('/interns/bulk-delete', ['intern_ids' => [$intern->id]]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('interns', ['id' => $intern->id]);
        $this->assertDatabaseMissing('intern_accounts', ['id' => $account->id]);
    }

    public function test_deletes_multiple_interns_at_once(): void
    {
        $this->actingAs($this->admin, 'web');
        $a = $this->makeIntern('Bulk A');
        $b = $this->makeIntern('Bulk B');
        $c = $this->makeIntern('Bulk C');

        $response = $this->post('/interns/bulk-delete', ['intern_ids' => [$a->id, $b->id]]);

        $response->assertRedirect();
        $this->assertSame('2 intern berhasil dihapus permanen beserta seluruh data terkait.', session('status'));
        $this->assertDatabaseMissing('interns', ['id' => $a->id]);
        $this->assertDatabaseMissing('interns', ['id' => $b->id]);
        $this->assertDatabaseHas('interns', ['id' => $c->id]);
    }

    public function test_rejects_unknown_id_and_deletes_nothing(): void
    {
        $this->actingAs($this->admin, 'web');
        $intern = $this->makeIntern('Keep Me');

        $response = $this->post('/interns/bulk-delete', ['intern_ids' => [$intern->id, '01a0e746-0000-7000-8000-000000000000']]);

        $response->assertSessionHasErrors('intern_ids.1');
        $this->assertDatabaseHas('interns', ['id' => $intern->id]);
    }

    public function test_requires_at_least_one_id(): void
    {
        $this->actingAs($this->admin, 'web');

        $this->post('/interns/bulk-delete', ['intern_ids' => []])
            ->assertSessionHasErrors('intern_ids');
    }

    public function test_spv_mentor_gets_403(): void
    {
        $spv = AdminUser::create([
            'name' => 'SPV', 'email' => 'spv@test.local', 'password' => 'secret123',
            'role' => 'spv_mentor', 'is_active' => true,
        ]);
        $this->actingAs($spv, 'web');
        $intern = $this->makeIntern('Protected Intern', 'active', ['mentor_id' => $spv->id]);

        $response = $this->post('/interns/bulk-delete', ['intern_ids' => [$intern->id]]);

        $response->assertForbidden();
        $this->assertDatabaseHas('interns', ['id' => $intern->id]);
    }

    /**
     * Forces a failure while deleting the SECOND intern's file and asserts
     * the FIRST intern — already processed earlier in the same transaction —
     * is not deleted either: the whole batch is one transaction.
     */
    public function test_a_failure_partway_through_rolls_back_the_whole_batch(): void
    {
        $this->actingAs($this->admin, 'web');

        $survivor = $this->makeIntern('Should Survive');
        $doomed = $this->makeIntern('Triggers Failure', 'completed');
        $certNumber = '002/LOGISTAX/INTERN/IX/2026';
        Certificate::create([
            'intern_id' => $doomed->id, 'certificate_number' => $certNumber,
            'issued_date' => '2026-09-15', 'issued_city' => 'Tangerang',
            'pdf_url' => 'http://localhost/storage/certificates/002.pdf', 'generated_by' => $this->admin->id,
        ]);

        $this->partialMock(CertificateIssuingService::class, function ($mock) use ($certNumber) {
            $mock->shouldReceive('storagePathFor')
                ->with($certNumber)
                ->andThrow(new \RuntimeException('simulated storage failure'));
        });

        $this->withoutExceptionHandling();
        try {
            $this->post('/interns/bulk-delete', ['intern_ids' => [$survivor->id, $doomed->id]]);
            $this->fail('Expected the simulated exception to propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated storage failure', $e->getMessage());
        }

        $this->assertDatabaseHas('interns', ['id' => $survivor->id]);
        $this->assertDatabaseHas('interns', ['id' => $doomed->id]);
        $this->assertDatabaseHas('certificates', ['certificate_number' => $certNumber]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $intern = $this->makeIntern('Untouched Intern');

        $response = $this->post('/interns/bulk-delete', ['intern_ids' => [$intern->id]]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('interns', ['id' => $intern->id]);
    }
}
