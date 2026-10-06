<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Division;
use App\Models\Intern;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DivisionWebTest extends TestCase
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

    public function test_admin_can_access_divisions_page(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('settings.divisions.index'))
            ->assertOk();
    }

    public function test_spv_mentor_gets_403_on_divisions(): void
    {
        $this->actingAs($this->spv, 'web')
            ->get(route('settings.divisions.index'))
            ->assertForbidden();
    }

    public function test_spv_mentor_gets_403_on_division_store(): void
    {
        $this->actingAs($this->spv, 'web')
            ->post(route('settings.divisions.store'), ['name' => 'Test Division'])
            ->assertForbidden();
    }

    public function test_spv_mentor_gets_403_on_division_delete(): void
    {
        $division = Division::factory()->create();

        $this->actingAs($this->spv, 'web')
            ->delete(route('settings.divisions.destroy', $division))
            ->assertForbidden();
    }

    public function test_delete_division_with_interns_is_rejected(): void
    {
        $division = Division::factory()->create();
        Intern::factory()->create(['division_id' => $division->id, 'status' => 'active']);

        $this->actingAs($this->admin, 'web')
            ->delete(route('settings.divisions.destroy', $division))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($division->fresh()->is_active);
    }

    public function test_delete_division_with_mentors_is_rejected(): void
    {
        $division = Division::factory()->create();
        AdminUser::factory()->create(['role' => 'spv_mentor', 'division_id' => $division->id]);

        $this->actingAs($this->admin, 'web')
            ->delete(route('settings.divisions.destroy', $division))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($division->fresh()->is_active);
    }

    public function test_delete_empty_division_succeeds(): void
    {
        $division = Division::factory()->create();

        $this->actingAs($this->admin, 'web')
            ->delete(route('settings.divisions.destroy', $division))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertFalse($division->fresh()->is_active);
    }
}
