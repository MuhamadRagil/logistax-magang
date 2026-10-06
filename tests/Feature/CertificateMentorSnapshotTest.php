<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Division;
use App\Models\Evaluation;
use App\Models\Intern;
use App\Services\CertificateIssuingService;
use App\Services\CertificatePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateMentorSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private string $renderedHtml = '';

    private AdminUser $admin;

    private AdminUser $mentor;

    private Intern $intern;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Storage::fake('public');
        $this->partialMock(CertificatePdfService::class, function ($mock) {
            $mock->shouldReceive('renderPdf')->andReturnUsing(function (string $html) {
                $this->renderedHtml = $html;

                return '%PDF-1.4 stub';
            });
        });

        $this->admin = AdminUser::factory()->create(['role' => 'admin_magang']);
        $this->mentor = AdminUser::factory()->create(['role' => 'spv_mentor', 'name' => 'Nama Lama']);
        $this->intern = Intern::factory()->create([
            'division_id' => Division::factory()->create()->id,
            'mentor_id' => $this->mentor->id,
            'status' => 'completed',
        ]);
        Evaluation::create([
            'intern_id' => $this->intern->id, 'evaluated_by' => $this->admin->id,
            'discipline_score' => 90, 'performance_score' => 88, 'attitude_score' => 92,
            'communication_score' => 85, 'total_score' => 88.75, 'grade' => 'A',
        ]);
    }

    private function service(): CertificateIssuingService
    {
        return app(CertificateIssuingService::class);
    }

    private function freshIntern(): Intern
    {
        return Intern::with(['mentor', 'evaluation'])->find($this->intern->id);
    }

    public function test_first_issue_stores_mentor_name_snapshot(): void
    {
        $certificate = $this->service()->generate($this->freshIntern(), $this->admin);

        $this->assertSame('Nama Lama', $certificate->mentor_name);
        $this->assertStringContainsString('Nama Lama', $this->renderedHtml);
    }

    public function test_regenerate_after_mentor_rename_keeps_old_name(): void
    {
        $this->service()->generate($this->freshIntern(), $this->admin);

        $this->mentor->update(['name' => 'Nama Baru']);
        $certificate = $this->service()->regenerate($this->freshIntern(), $this->admin);

        $this->assertSame('Nama Lama', $certificate->mentor_name);
        $this->assertStringContainsString('Nama Lama', $this->renderedHtml);
        $this->assertStringNotContainsString('Nama Baru', $this->renderedHtml);
    }

    public function test_regenerate_after_mentor_soft_deleted_still_shows_mentor_name(): void
    {
        $this->service()->generate($this->freshIntern(), $this->admin);

        $this->mentor->delete();
        $this->service()->regenerate($this->freshIntern(), $this->admin);

        $this->assertStringContainsString('Nama Lama', $this->renderedHtml);
    }

    public function test_template_falls_back_to_trashed_relation_when_snapshot_empty(): void
    {
        $this->service()->generate($this->freshIntern(), $this->admin);
        $this->intern->certificate()->update(['mentor_name' => null]);

        $this->mentor->delete();
        $certificate = $this->service()->regenerate($this->freshIntern(), $this->admin);

        $this->assertStringContainsString('Nama Lama', $this->renderedHtml);
        $this->assertSame('Nama Lama', $certificate->mentor_name);
    }
}
