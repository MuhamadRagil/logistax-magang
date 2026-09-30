<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\InternAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\BuildsAttendanceFixtures;
use Tests\TestCase;

class InternResetPasswordTest extends TestCase
{
    use BuildsAttendanceFixtures;
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret123',
            'role' => 'admin_magang', 'is_active' => true,
        ]);
    }

    public function test_random_mode_returns_a_new_readable_password_and_stores_its_hash(): void
    {
        $this->actingAs($this->admin, 'web');
        $account = InternAccount::create(['email' => 'a@test.local', 'password' => 'old-password-123', 'is_verified' => true]);
        $intern = $this->makeIntern('Random Reset', 'active', ['intern_account_id' => $account->id]);

        $response = $this->postJson("/interns/{$intern->id}/reset-password", ['mode' => 'random']);

        $response->assertOk()->assertJson(['success' => true, 'message' => 'Password berhasil direset.']);
        $password = $response->json('data.password');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{8}$/', $password);
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
        foreach (['0', 'O', 'o', '1', 'l', 'I'] as $ambiguous) {
            $this->assertStringNotContainsString($ambiguous, $password, "Password contains ambiguous character '{$ambiguous}'");
        }

        $this->assertTrue(Hash::check($password, $account->fresh()->password));
        $this->assertFalse(Hash::check('old-password-123', $account->fresh()->password));
    }

    public function test_random_passwords_are_not_all_identical(): void
    {
        $this->actingAs($this->admin, 'web');
        $account = InternAccount::create(['email' => 'b@test.local', 'password' => 'x', 'is_verified' => true]);
        $intern = $this->makeIntern('Repeat Reset', 'active', ['intern_account_id' => $account->id]);

        $passwords = collect(range(1, 5))->map(function () use ($intern) {
            return $this->postJson("/interns/{$intern->id}/reset-password", ['mode' => 'random'])->json('data.password');
        });

        $this->assertGreaterThan(1, $passwords->unique()->count());
    }

    public function test_custom_mode_stores_the_exact_password_admin_typed(): void
    {
        $this->actingAs($this->admin, 'web');
        $account = InternAccount::create(['email' => 'c@test.local', 'password' => 'old-password-123', 'is_verified' => true]);
        $intern = $this->makeIntern('Custom Reset', 'active', ['intern_account_id' => $account->id]);

        $response = $this->postJson("/interns/{$intern->id}/reset-password", [
            'mode' => 'custom', 'password' => 'MyN3wSecret',
        ]);

        $response->assertOk()->assertJson(['success' => true, 'data' => ['password' => 'MyN3wSecret']]);
        $this->assertTrue(Hash::check('MyN3wSecret', $account->fresh()->password));
    }

    public function test_custom_mode_rejects_password_shorter_than_8_chars(): void
    {
        $this->actingAs($this->admin, 'web');
        $account = InternAccount::create(['email' => 'd@test.local', 'password' => 'old-password-123', 'is_verified' => true]);
        $intern = $this->makeIntern('Short Reset', 'active', ['intern_account_id' => $account->id]);

        $response = $this->postJson("/interns/{$intern->id}/reset-password", [
            'mode' => 'custom', 'password' => 'short',
        ]);

        // Custom exception handler (bootstrap/app.php) puts field errors
        // under "data", not Laravel's default "errors" key.
        $response->assertStatus(422)->assertJsonStructure(['success', 'data' => ['password'], 'message']);
        $this->assertTrue(Hash::check('old-password-123', $account->fresh()->password));
    }

    public function test_custom_mode_requires_a_password(): void
    {
        $this->actingAs($this->admin, 'web');
        $account = InternAccount::create(['email' => 'e@test.local', 'password' => 'old-password-123', 'is_verified' => true]);
        $intern = $this->makeIntern('Missing Password', 'active', ['intern_account_id' => $account->id]);

        $this->postJson("/interns/{$intern->id}/reset-password", ['mode' => 'custom'])
            ->assertStatus(422)->assertJsonStructure(['data' => ['password']]);
    }

    public function test_random_mode_does_not_require_a_password_field(): void
    {
        $this->actingAs($this->admin, 'web');
        $account = InternAccount::create(['email' => 'f@test.local', 'password' => 'x', 'is_verified' => true]);
        $intern = $this->makeIntern('No Password Field', 'active', ['intern_account_id' => $account->id]);

        $this->postJson("/interns/{$intern->id}/reset-password", ['mode' => 'random'])
            ->assertOk()->assertJson(['success' => true]);
    }

    public function test_invalid_mode_is_rejected(): void
    {
        $this->actingAs($this->admin, 'web');
        $account = InternAccount::create(['email' => 'g@test.local', 'password' => 'x', 'is_verified' => true]);
        $intern = $this->makeIntern('Bad Mode', 'active', ['intern_account_id' => $account->id]);

        $this->postJson("/interns/{$intern->id}/reset-password", ['mode' => 'not-a-mode'])
            ->assertStatus(422)->assertJsonStructure(['data' => ['mode']]);
    }

    public function test_intern_without_a_login_account_is_rejected(): void
    {
        $this->actingAs($this->admin, 'web');
        $intern = $this->makeIntern('No Account', 'active', ['intern_account_id' => null]);

        $response = $this->postJson("/interns/{$intern->id}/reset-password", ['mode' => 'random']);

        $response->assertStatus(400)->assertJson([
            'success' => false,
            'message' => 'Intern ini belum memiliki akun login.',
        ]);
    }

    public function test_login_with_old_password_fails_and_new_password_works_after_reset(): void
    {
        $account = InternAccount::create(['email' => 'login-test@test.local', 'password' => 'OldPassw0rd', 'is_verified' => true]);
        $intern = $this->makeIntern('Login Flow', 'active', ['intern_account_id' => $account->id]);

        $this->postJson('/api/auth/intern/login', ['email' => 'login-test@test.local', 'password' => 'OldPassw0rd'])
            ->assertOk()->assertJson(['success' => true]);

        $this->actingAs($this->admin, 'web');
        $newPassword = $this->postJson("/interns/{$intern->id}/reset-password", [
            'mode' => 'custom', 'password' => 'BrandNewPass1',
        ])->json('data.password');
        $this->assertSame('BrandNewPass1', $newPassword);

        // Old credentials must now be rejected via the real, unmodified login endpoint.
        $this->postJson('/api/auth/intern/login', ['email' => 'login-test@test.local', 'password' => 'OldPassw0rd'])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Email atau password salah.']);

        // New password logs in successfully.
        $this->postJson('/api/auth/intern/login', ['email' => 'login-test@test.local', 'password' => 'BrandNewPass1'])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token', 'intern']]);
    }

    public function test_spv_mentor_gets_403(): void
    {
        $spv = AdminUser::create([
            'name' => 'SPV', 'email' => 'spv@test.local', 'password' => 'secret123',
            'role' => 'spv_mentor', 'is_active' => true,
        ]);
        $account = InternAccount::create(['email' => 'h@test.local', 'password' => 'old-password-123', 'is_verified' => true]);
        $intern = $this->makeIntern('SPV Blocked', 'active', ['intern_account_id' => $account->id, 'mentor_id' => $spv->id]);

        $this->actingAs($spv, 'web');
        $response = $this->postJson("/interns/{$intern->id}/reset-password", ['mode' => 'random']);

        $response->assertForbidden();
        $this->assertTrue(Hash::check('old-password-123', $account->fresh()->password));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $account = InternAccount::create(['email' => 'i@test.local', 'password' => 'old-password-123', 'is_verified' => true]);
        $intern = $this->makeIntern('Guest Blocked', 'active', ['intern_account_id' => $account->id]);

        $this->post("/interns/{$intern->id}/reset-password", ['mode' => 'random'])
            ->assertRedirect('/login');
        $this->assertTrue(Hash::check('old-password-123', $account->fresh()->password));
    }

    public function test_plaintext_password_never_appears_in_other_responses(): void
    {
        $this->actingAs($this->admin, 'web');
        $account = InternAccount::create(['email' => 'j@test.local', 'password' => 'old-password-123', 'is_verified' => true]);
        $intern = $this->makeIntern('No Leak', 'active', ['intern_account_id' => $account->id]);

        $password = $this->postJson("/interns/{$intern->id}/reset-password", ['mode' => 'random'])->json('data.password');

        // detail endpoint (used by the "Lihat" modal) must not leak it either.
        $detail = $this->get("/interns/{$intern->id}/detail");
        $detail->assertOk();
        $this->assertStringNotContainsString($password, $detail->getContent());

        $index = $this->get('/interns');
        $index->assertOk();
        $this->assertStringNotContainsString($password, $index->getContent());
    }

    public function test_plaintext_password_never_written_to_the_log(): void
    {
        $this->actingAs($this->admin, 'web');
        $account = InternAccount::create(['email' => 'k@test.local', 'password' => 'old-password-123', 'is_verified' => true]);
        $intern = $this->makeIntern('No Log Leak', 'active', ['intern_account_id' => $account->id]);

        $logged = [];
        Log::listen(function ($event) use (&$logged) {
            $logged[] = $event->message.' '.json_encode($event->context);
        });

        $password = $this->postJson("/interns/{$intern->id}/reset-password", [
            'mode' => 'custom', 'password' => 'VerySpecific9',
        ])->json('data.password');

        $this->assertSame('VerySpecific9', $password);
        foreach ($logged as $line) {
            $this->assertStringNotContainsString('VerySpecific9', $line);
        }
    }
}
