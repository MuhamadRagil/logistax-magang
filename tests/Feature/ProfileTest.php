<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::factory()->create([
            'role' => 'admin_magang',
            'name' => 'Original Name',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_profile_page_loads(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get('/profile')
            ->assertOk()
            ->assertSee('Original Name');
    }

    public function test_name_can_be_updated(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch('/profile/name', ['name' => 'New Name'])
            ->assertRedirect();

        $this->assertSame('New Name', $this->admin->fresh()->name);
    }

    public function test_name_update_logs_activity(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch('/profile/name', ['name' => 'New Name']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'profile.updated',
            'actor_id' => $this->admin->id,
        ]);

        $log = ActivityLog::where('action', 'profile.updated')->first();
        $this->assertSame('name', $log->metadata['field']);
        $this->assertSame('Original Name', $log->metadata['old_name']);
    }

    public function test_password_change_success(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch('/profile/password', [
                'current_password' => 'password123',
                'password' => 'newsecure88',
                'password_confirmation' => 'newsecure88',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('newsecure88', $this->admin->fresh()->password));
    }

    public function test_password_change_wrong_current(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch('/profile/password', [
                'current_password' => 'wrongpassword',
                'password' => 'newsecure88',
                'password_confirmation' => 'newsecure88',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('current_password');
    }

    public function test_password_change_same_as_old(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch('/profile/password', [
                'current_password' => 'password123',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('password');
    }

    public function test_password_too_short(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch('/profile/password', [
                'current_password' => 'password123',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('password');
    }

    public function test_password_confirmation_mismatch(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch('/profile/password', [
                'current_password' => 'password123',
                'password' => 'newsecure88',
                'password_confirmation' => 'different99',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('password');
    }

    public function test_cannot_change_email_or_role(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch('/profile/name', [
                'name' => 'Name Only',
                'email' => 'hacked@evil.com',
                'role' => 'admin_magang',
            ])
            ->assertRedirect();

        $fresh = $this->admin->fresh();
        $this->assertNotSame('hacked@evil.com', $fresh->email);
        $this->assertSame('Name Only', $fresh->name);
    }

    public function test_password_change_logs_activity_without_password(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch('/profile/password', [
                'current_password' => 'password123',
                'password' => 'newsecure88',
                'password_confirmation' => 'newsecure88',
            ]);

        $log = ActivityLog::where('action', 'profile.password_changed')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->metadata);
    }
}
