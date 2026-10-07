<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdminUser;
use App\Models\Division;
use App\Models\Intern;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyStateVariantTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private AdminUser $spv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::factory()->create(['role' => 'admin_magang']);
        $this->spv = AdminUser::factory()->create(['role' => 'spv_mentor']);
    }

    private function variant(string $url, ?AdminUser $as = null)
    {
        return $this->actingAs($as ?? $this->admin, 'web')->get($url)->assertOk();
    }

    public function test_interns_truly_empty_shows_friendly_message_with_add_button_and_no_reset(): void
    {
        $this->variant('/interns')
            ->assertSee('data-empty-variant="empty"', false)
            ->assertSee('Tambahkan intern pertama')
            ->assertDontSee('data-empty-variant="filtered"', false)
            ->assertDontSee('Reset Filter');
    }

    public function test_interns_filter_with_no_data_at_all_is_still_the_empty_variant(): void
    {
        $this->variant('/interns?search=zzz')
            ->assertSee('data-empty-variant="empty"', false)
            ->assertDontSee('Reset Filter');
    }

    public function test_interns_filter_without_match_but_with_data_is_the_filter_variant(): void
    {
        Intern::factory()->create(['full_name' => 'Budi Santoso', 'status' => 'active']);

        $this->variant('/interns?search=zzz')
            ->assertSee('data-empty-variant="filtered"', false)
            ->assertSee('Tidak ada hasil untuk filter ini')
            ->assertSee('Reset Filter')
            ->assertDontSee('Tambahkan intern pertama');
    }

    public function test_interns_empty_for_spv_has_no_add_action(): void
    {
        $this->variant('/interns', $this->spv)
            ->assertSee('data-empty-variant="empty"', false)
            ->assertSee('Belum ada intern yang Anda bimbing')
            ->assertDontSee('Tambahkan intern pertama');
    }

    public function test_interns_total_is_scoped_for_spv(): void
    {
        Intern::factory()->create(['status' => 'active']);

        $this->variant('/interns?search=zzz', $this->spv)
            ->assertSee('data-empty-variant="empty"', false);
    }

    public function test_pending_tab_has_no_filter_variant_or_reset(): void
    {
        $this->variant('/interns?tab=pending&search=zzz')
            ->assertSee('Tidak ada registrasi menunggu')
            ->assertDontSee('Reset Filter');
    }

    public function test_attendance_rekap_variants(): void
    {
        $this->variant('/attendance?tab=rekap')
            ->assertSee('data-empty-variant="empty"', false)
            ->assertDontSee('Reset Filter');

        $intern = Intern::factory()->create(['status' => 'active', 'division_id' => Division::factory()->create()->id]);
        $other = Division::factory()->create();

        $this->variant('/attendance?tab=rekap&division_id='.$other->id)
            ->assertSee('data-empty-variant="filtered"', false)
            ->assertSee('Reset Filter');

        $this->assertNotNull($intern);
    }

    public function test_pages_without_filters_never_show_reset_filter(): void
    {
        foreach (['/attendance?tab=approval', '/evaluations', '/certificates?tab=cert'] as $url) {
            $this->variant($url)
                ->assertSee('data-empty-variant="empty"', false)
                ->assertDontSee('Reset Filter');
        }
    }

    public function test_activity_log_variants(): void
    {
        $this->variant('/activity-logs')
            ->assertSee('data-empty-variant="empty"', false)
            ->assertDontSee('Reset Filter');

        $this->variant('/activity-logs?action=division.deleted')
            ->assertSee('data-empty-variant="empty"', false);

        ActivityLog::create([
            'actor_id' => $this->admin->id, 'actor_name' => $this->admin->name, 'action' => 'division.created',
            'subject_type' => 'Division', 'subject_id' => null, 'subject_label' => 'X', 'metadata' => null,
        ]);

        $this->variant('/activity-logs?action=division.deleted')
            ->assertSee('data-empty-variant="filtered"', false)
            ->assertSee('Reset Filter');
    }

    public function test_settings_pages_show_add_button_in_empty_variant(): void
    {
        $this->variant('/settings/divisions')->assertSee('+ Tambah Divisi')->assertSee('data-empty-variant="empty"', false);
        $this->variant('/settings/mentors')->assertDontSee('data-empty-variant="filtered"', false);
        $this->variant('/settings/office-locations')->assertSee('+ Tambah Lokasi')->assertSee('data-empty-variant="empty"', false);
    }
}
