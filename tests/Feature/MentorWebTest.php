<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Division;
use App\Models\Intern;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorWebTest extends TestCase
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

    public function test_admin_can_access_mentors_page(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('settings.mentors.index'))
            ->assertOk();
    }

    public function test_spv_mentor_gets_403_on_mentors(): void
    {
        $this->actingAs($this->spv, 'web')
            ->get(route('settings.mentors.index'))
            ->assertForbidden();
    }

    public function test_spv_mentor_gets_403_on_mentor_store(): void
    {
        $this->actingAs($this->spv, 'web')
            ->post(route('settings.mentors.store'), [
                'name' => 'New Mentor',
                'email' => 'new@test.com',
            ])
            ->assertForbidden();
    }

    public function test_spv_mentor_gets_403_on_mentor_delete(): void
    {
        $mentor = AdminUser::factory()->create(['role' => 'spv_mentor']);

        $this->actingAs($this->spv, 'web')
            ->delete(route('settings.mentors.destroy', $mentor))
            ->assertForbidden();
    }

    public function test_delete_mentor_with_active_interns_is_rejected(): void
    {
        $mentor = AdminUser::factory()->create(['role' => 'spv_mentor']);
        $division = Division::factory()->create();
        Intern::factory()->create([
            'mentor_id' => $mentor->id,
            'division_id' => $division->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin, 'web')
            ->delete(route('settings.mentors.destroy', $mentor))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($mentor->fresh()->is_active);
    }

    public function test_delete_rejection_message_contains_active_intern_count(): void
    {
        $mentor = AdminUser::factory()->create(['role' => 'spv_mentor', 'name' => 'Pak Budi']);
        $division = Division::factory()->create();
        Intern::factory()->count(2)->create(['mentor_id' => $mentor->id, 'division_id' => $division->id, 'status' => 'active']);
        Intern::factory()->create(['mentor_id' => $mentor->id, 'division_id' => $division->id, 'status' => 'extended']);

        $this->actingAs($this->admin, 'web')
            ->delete(route('settings.mentors.destroy', $mentor))
            ->assertSessionHas('error', 'Mentor "Pak Budi" tidak bisa dihapus karena masih membimbing 3 intern aktif/extended.');

        $this->assertNotSoftDeleted($mentor);
    }

    public function test_delete_mentor_with_only_historical_interns_soft_deletes_and_keeps_name_on_intern(): void
    {
        $mentor = AdminUser::factory()->create(['role' => 'spv_mentor', 'name' => 'Bu Sari']);
        $division = Division::factory()->create();
        $intern = Intern::factory()->create(['mentor_id' => $mentor->id, 'division_id' => $division->id, 'status' => 'completed']);
        Intern::factory()->create(['mentor_id' => $mentor->id, 'division_id' => $division->id, 'status' => 'failed']);

        $this->actingAs($this->admin, 'web')
            ->delete(route('settings.mentors.destroy', $mentor))
            ->assertSessionHas('status');

        $this->assertSoftDeleted($mentor);
        $this->assertSame('Bu Sari', $intern->fresh()->mentor?->name);
        $this->assertSame($mentor->id, $intern->fresh()->mentor_id);
    }

    public function test_delete_mentor_without_interns_soft_deletes(): void
    {
        $mentor = AdminUser::factory()->create(['role' => 'spv_mentor']);

        $this->actingAs($this->admin, 'web')
            ->delete(route('settings.mentors.destroy', $mentor))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSoftDeleted($mentor);
    }

    public function test_trashed_mentor_is_hidden_from_mentor_list_and_intern_dropdown(): void
    {
        $gone = AdminUser::factory()->create(['role' => 'spv_mentor', 'name' => 'Mentor Terhapus']);
        AdminUser::factory()->create(['role' => 'spv_mentor', 'name' => 'Mentor Aktif']);
        $gone->delete();

        foreach ([route('settings.mentors.index'), route('interns.index')] as $url) {
            $this->actingAs($this->admin, 'web')->get($url)
                ->assertOk()
                ->assertSee('Mentor Aktif')
                ->assertDontSee('Mentor Terhapus');
        }
    }

    public function test_trashed_mentor_cannot_login_via_web_or_api(): void
    {
        $mentor = AdminUser::factory()->create(['role' => 'spv_mentor', 'email' => 'gone@test.local', 'password' => 'secret123']);
        $mentor->delete();

        $this->post(route('login'), ['email' => 'gone@test.local', 'password' => 'secret123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('web');

        $this->postJson('/api/auth/admin/login', ['email' => 'gone@test.local', 'password' => 'secret123'])
            ->assertStatus(401);
    }

    public function test_spv_mentor_cannot_write_to_divisions_or_mentors(): void
    {
        $division = Division::factory()->create(['name' => 'Asli']);
        $mentor = AdminUser::factory()->create(['role' => 'spv_mentor', 'name' => 'Asli Mentor']);

        $this->actingAs($this->spv, 'web');

        $this->post(route('settings.divisions.store'), ['name' => 'Baru'])->assertForbidden();
        $this->put(route('settings.divisions.update', $division), ['name' => 'Diubah'])->assertForbidden();
        $this->delete(route('settings.divisions.destroy', $division))->assertForbidden();
        $this->post(route('settings.mentors.store'), ['name' => 'Baru', 'email' => 'b@test.local'])->assertForbidden();
        $this->put(route('settings.mentors.update', $mentor), ['name' => 'Diubah'])->assertForbidden();
        $this->delete(route('settings.mentors.destroy', $mentor))->assertForbidden();

        $this->assertSame('Asli', $division->fresh()->name);
        $this->assertTrue($division->fresh()->is_active);
        $this->assertSame('Asli Mentor', $mentor->fresh()->name);
        $this->assertNotSoftDeleted($mentor);
        $this->assertDatabaseMissing('divisions', ['name' => 'Baru']);
        $this->assertDatabaseMissing('admin_users', ['email' => 'b@test.local']);
    }
}
