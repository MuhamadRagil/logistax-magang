<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\Intern;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationCountTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private AdminUser $spv;

    private Division $division;

    protected function setUp(): void
    {
        parent::setUp();

        $this->division = Division::factory()->create();
        $this->admin = AdminUser::factory()->create(['role' => 'admin_magang']);
        $this->spv = AdminUser::factory()->create(['role' => 'spv_mentor', 'division_id' => $this->division->id]);
    }

    public function test_guest_is_redirected(): void
    {
        $this->getJson('/notifications/counts')->assertUnauthorized();
    }

    public function test_admin_sees_all_categories(): void
    {
        Intern::factory()->create(['status' => 'pending', 'division_id' => $this->division->id, 'mentor_id' => $this->spv->id]);
        Intern::factory()->create(['status' => 'pending', 'division_id' => $this->division->id, 'mentor_id' => $this->spv->id]);

        $activeIntern = Intern::factory()->create([
            'status' => 'active', 'division_id' => $this->division->id, 'mentor_id' => $this->spv->id,
        ]);
        Attendance::create([
            'intern_id' => $activeIntern->id, 'date' => Carbon::today(),
            'status' => 'izin', 'approval_status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'web')
            ->getJson('/notifications/counts')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonCount(2, 'items');
    }

    public function test_spv_sees_only_own_attendance(): void
    {
        Intern::factory()->create(['status' => 'pending', 'division_id' => $this->division->id, 'mentor_id' => $this->spv->id]);

        $ownIntern = Intern::factory()->create([
            'status' => 'active', 'division_id' => $this->division->id, 'mentor_id' => $this->spv->id,
        ]);
        Attendance::create([
            'intern_id' => $ownIntern->id, 'date' => Carbon::today(),
            'status' => 'izin', 'approval_status' => 'pending',
        ]);

        $otherSpv = AdminUser::factory()->create(['role' => 'spv_mentor']);
        $otherIntern = Intern::factory()->create([
            'status' => 'active', 'division_id' => $this->division->id, 'mentor_id' => $otherSpv->id,
        ]);
        Attendance::create([
            'intern_id' => $otherIntern->id, 'date' => Carbon::today(),
            'status' => 'sakit', 'approval_status' => 'pending',
        ]);

        $this->actingAs($this->spv, 'web')
            ->getJson('/notifications/counts')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.key', 'pending_attendance');
    }

    public function test_zero_returns_empty_items(): void
    {
        $this->actingAs($this->admin, 'web')
            ->getJson('/notifications/counts')
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonCount(0, 'items');
    }

    public function test_labels_do_not_repeat_the_count(): void
    {
        Intern::factory()->create(['status' => 'pending', 'division_id' => $this->division->id, 'mentor_id' => $this->spv->id]);

        $items = $this->actingAs($this->admin, 'web')->getJson('/notifications/counts')->assertOk()->json('items');

        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertDoesNotMatchRegularExpression('/\d/', $item['label']);
            $this->assertGreaterThan(0, $item['count']);
        }
    }
}
