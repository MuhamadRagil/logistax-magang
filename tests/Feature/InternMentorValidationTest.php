<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Division;
use App\Models\Intern;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InternMentorValidationTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private AdminUser $activeMentor;

    private AdminUser $trashedMentor;

    private Division $division;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::factory()->create(['role' => 'admin_magang']);
        $this->activeMentor = AdminUser::factory()->create(['role' => 'spv_mentor']);
        $this->trashedMentor = AdminUser::factory()->create(['role' => 'spv_mentor']);
        $this->trashedMentor->delete();
        $this->division = Division::factory()->create();
    }

    private function payload(string $mentorId, string $suffix = '1'): array
    {
        return [
            'email' => "intern{$suffix}@test.local",
            'full_name' => "Intern {$suffix}",
            'nim' => "NIM-{$suffix}",
            'institution' => 'Univ Test',
            'major' => 'TI',
            'division_id' => $this->division->id,
            'mentor_id' => $mentorId,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ];
    }

    private function pendingIntern(array $attrs = []): Intern
    {
        return Intern::factory()->create(array_merge(['status' => 'pending', 'division_id' => null, 'mentor_id' => null], $attrs));
    }

    public function test_web_store_rejects_soft_deleted_mentor_and_accepts_active(): void
    {
        $this->actingAs($this->admin, 'web');

        $this->postJson('/interns', $this->payload($this->trashedMentor->id))
            ->assertStatus(422)->assertJsonStructure(['data' => ['mentor_id']]);

        $this->postJson('/interns', $this->payload($this->activeMentor->id, '2'))
            ->assertRedirect();
        $this->assertDatabaseHas('interns', ['nim' => 'NIM-2', 'mentor_id' => $this->activeMentor->id]);
    }

    public function test_api_store_rejects_soft_deleted_mentor_and_accepts_active(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/interns', $this->payload($this->trashedMentor->id))
            ->assertStatus(422)->assertJsonStructure(['data' => ['mentor_id']]);

        $this->postJson('/api/interns', $this->payload($this->activeMentor->id, '2'))
            ->assertSuccessful();
    }

    public function test_api_update_rejects_soft_deleted_mentor_and_accepts_active(): void
    {
        Sanctum::actingAs($this->admin);
        $intern = $this->pendingIntern(['status' => 'active', 'division_id' => $this->division->id, 'mentor_id' => $this->activeMentor->id]);

        $this->patchJson("/api/interns/{$intern->id}", ['mentor_id' => $this->trashedMentor->id])
            ->assertStatus(422)->assertJsonStructure(['data' => ['mentor_id']]);
        $this->assertSame($this->activeMentor->id, $intern->fresh()->mentor_id);

        $other = AdminUser::factory()->create(['role' => 'spv_mentor']);
        $this->patchJson("/api/interns/{$intern->id}", ['mentor_id' => $other->id])->assertOk();
        $this->assertSame($other->id, $intern->fresh()->mentor_id);
    }

    public function test_web_approve_rejects_soft_deleted_mentor_and_accepts_active(): void
    {
        $this->actingAs($this->admin, 'web');
        $intern = $this->pendingIntern();

        $this->postJson("/interns/{$intern->id}/approve", ['division_id' => $this->division->id, 'mentor_id' => $this->trashedMentor->id])
            ->assertStatus(422)->assertJsonStructure(['data' => ['mentor_id']]);

        $this->postJson("/interns/{$intern->id}/approve", ['division_id' => $this->division->id, 'mentor_id' => $this->activeMentor->id])
            ->assertRedirect();
        $this->assertSame($this->activeMentor->id, $intern->fresh()->mentor_id);
    }

    public function test_api_approve_rejects_soft_deleted_mentor_and_accepts_active(): void
    {
        Sanctum::actingAs($this->admin);
        $intern = $this->pendingIntern();

        $this->postJson("/api/interns/{$intern->id}/approve", ['division_id' => $this->division->id, 'mentor_id' => $this->trashedMentor->id])
            ->assertStatus(422)->assertJsonStructure(['data' => ['mentor_id']]);

        $this->postJson("/api/interns/{$intern->id}/approve", ['division_id' => $this->division->id, 'mentor_id' => $this->activeMentor->id])
            ->assertSuccessful();
    }
}
