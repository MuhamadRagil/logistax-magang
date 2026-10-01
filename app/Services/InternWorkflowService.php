<?php

namespace App\Services;

use App\Exceptions\DomainActionException;
use App\Models\AdminUser;
use App\Models\ExtensionLog;
use App\Models\Intern;
use App\Models\InternAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ActivityLogger;

/**
 * Intern lifecycle actions (create-by-admin, approve, reject, extend,
 * mark-failed, mark-completed, hard-delete). Extracted out of
 * Api\InternController so the web dashboard controllers can trigger the
 * exact same validated logic instead of re-implementing it — the API
 * controller's behavior/response shape is unchanged, it just delegates here
 * now.
 */
class InternWorkflowService
{
    private const GENERATED_PASSWORD_LENGTH = 8;

    // Excludes 0/O/o and 1/l/I — the pairs easiest to mis-type from a
    // handwritten note or mis-read out loud when handing this to an intern.
    private const PASSWORD_UPPERCASE = 'ABCDEFGHJKMNPQRSTUVWXYZ';

    private const PASSWORD_LOWERCASE = 'abcdefghjkmnpqrstuvwxyz';

    private const PASSWORD_DIGITS = '23456789';

    public function __construct(
        // Only used by deleteInterns() to derive a certificate's on-disk PDF
        // path from its certificate_number via storagePathFor() — a pure
        // string builder, no rendering/side effects triggered by using it here.
        private readonly CertificateIssuingService $certificates,
    ) {}

    /**
     * @return array{intern: Intern, generated_password: string}
     */
    public function createByAdmin(array $data): array
    {
        $plainPassword = Str::password(12);

        $intern = DB::transaction(function () use ($data, $plainPassword) {
            $account = InternAccount::create([
                'email' => $data['email'],
                'password' => Hash::make($plainPassword),
                'is_verified' => true,
            ]);

            return Intern::create([
                'intern_account_id' => $account->id,
                'full_name' => $data['full_name'],
                'nim' => $data['nim'],
                'institution' => $data['institution'],
                'major' => $data['major'],
                'phone' => $data['phone'] ?? null,
                'division_id' => $data['division_id'],
                'mentor_id' => $data['mentor_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'active',
                'registered_via' => 'admin',
            ]);
        });

        return ['intern' => $intern->load(['division', 'mentor']), 'generated_password' => $plainPassword];
    }

    public function approve(Intern $intern, array $data): Intern
    {
        if ($intern->status !== 'pending') {
            throw new DomainActionException('Hanya intern dengan status pending yang bisa di-approve.', 400);
        }

        $intern->update([
            'division_id' => $data['division_id'] ?? $intern->division_id,
            'mentor_id' => $data['mentor_id'] ?? $intern->mentor_id,
            'status' => 'active',
        ]);

        ActivityLogger::log('intern.approved', $intern, "{$intern->full_name} ({$intern->nim})");

        return $intern->fresh(['division', 'mentor']);
    }

    /**
     * Keputusan desain: status enum ditambah 'rejected' (lihat migration interns)
     * agar intern yang ditolak punya status eksplisit, otomatis tidak muncul lagi
     * di listing dengan filter status=pending, dan tetap punya jejak melalui
     * kolom rejection_reason. Alternatif (flag is_rejected terpisah) ditolak
     * karena akan membuat status intern ambigu (mis. status tetap 'pending'
     * padahal sudah ditolak).
     */
    public function reject(Intern $intern, string $reason): Intern
    {
        if ($intern->status !== 'pending') {
            throw new DomainActionException('Hanya intern dengan status pending yang bisa ditolak.', 400);
        }

        $intern->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        ActivityLogger::log('intern.rejected', $intern, "{$intern->full_name} ({$intern->nim})", ['reason' => $reason]);

        return $intern->fresh();
    }

    public function extend(Intern $intern, AdminUser $admin, array $data): Intern
    {
        DB::transaction(function () use ($intern, $data, $admin) {
            $oldEndDate = $intern->end_date;

            $intern->update([
                'original_end_date' => $intern->original_end_date ?? $intern->end_date,
                'end_date' => $data['new_end_date'],
                'status' => 'extended',
            ]);

            ExtensionLog::create([
                'intern_id' => $intern->id,
                'old_end_date' => $oldEndDate,
                'new_end_date' => $data['new_end_date'],
                'reason' => $data['reason'],
                'extended_by' => $admin->id,
            ]);

            ActivityLogger::log('intern.extended', $intern, "{$intern->full_name} ({$intern->nim})", [
                'old_end_date' => $oldEndDate->toDateString(),
                'new_end_date' => $data['new_end_date'],
                'reason' => $data['reason'],
            ]);
        });

        return $intern->fresh();
    }

    public function markFailed(Intern $intern, string $reason): Intern
    {
        if (! in_array($intern->status, ['active', 'extended'], true)) {
            throw new DomainActionException('Hanya intern dengan status active/extended yang bisa ditandai gagal.', 400);
        }

        $intern->update([
            'status' => 'failed',
            'failed_reason' => $reason,
        ]);

        ActivityLogger::log('intern.marked_failed', $intern, "{$intern->full_name} ({$intern->nim})", ['reason' => $reason]);

        return $intern->fresh();
    }

    public function markCompleted(Intern $intern): Intern
    {
        if (! in_array($intern->status, ['active', 'extended'], true)) {
            throw new DomainActionException('Hanya intern dengan status active/extended yang bisa ditandai selesai.', 400);
        }

        $intern->update(['status' => 'completed']);

        ActivityLogger::log('intern.marked_completed', $intern, "{$intern->full_name} ({$intern->nim})");

        return $intern->fresh();
    }

    /**
     * HARD delete one or more interns and everything that belongs to them —
     * unlike divisions/office locations, there is no "nonaktifkan" here, the
     * rows and files are gone for good. Any status (including 'pending' from
     * self-registration) can be deleted; there is no status guard.
     *
     * Whole-or-nothing across ALL given interns: wrapped in one transaction,
     * so a failure partway through rolls every row back, never leaving some
     * interns deleted and others not. Storage deletes happen inside the
     * transaction too, right before the DB row that references each file is
     * removed — if the transaction later rolls back, the DB rows survive, but
     * a file already unlinked from disk cannot be restored. In practice this
     * only matters if something after the file delete throws, which nothing
     * here does (Storage::delete() returns false rather than throwing).
     *
     * @param  list<string>  $internIds
     * @return int number of interns deleted
     */
    public function deleteInterns(array $internIds): int
    {
        return DB::transaction(function () use ($internIds) {
            $interns = Intern::query()
                ->whereIn('id', $internIds)
                ->with(['attendances', 'certificate'])
                ->get();

            foreach ($interns as $intern) {
                $this->deleteInternWithRelatedData($intern);
            }

            return $interns->count();
        });
    }

    private function deleteInternWithRelatedData(Intern $intern): void
    {
        ActivityLogger::log('intern.bulk_deleted', null, "{$intern->full_name} ({$intern->nim})", [
            'full_name' => $intern->full_name,
            'nim' => $intern->nim,
        ]);

        // Files first, while the DB rows that record their paths still exist.
        if ($intern->certificate) {
            Storage::disk('public')->delete($this->certificates->storagePathFor($intern->certificate->certificate_number));
        }

        foreach ($intern->attendances as $attendance) {
            if ($attendance->proof_file_url) {
                Storage::disk('public')->delete($this->attendanceProofPath($attendance->proof_file_url));
            }
        }

        $intern->attendances()->delete();
        $intern->evaluation()->delete();
        $intern->certificate()->delete();
        $intern->extensionLogs()->delete();

        // Not cascaded at the DB level: the FK runs the other way (interns
        // references intern_accounts, not the reverse), so deleting the
        // intern never touches its account on its own.
        if ($intern->intern_account_id) {
            InternAccount::destroy($intern->intern_account_id);
        }

        $intern->delete();
    }

    /**
     * Admin-triggered password reset for an intern's login account (replaces
     * doing this by hand in Tinker). $newPassword null = generate a random
     * one; otherwise it's the admin-chosen password, already validated
     * (min:8) by ResetInternPasswordRequest.
     *
     * Returns the new password in PLAINTEXT — the only place it ever exists
     * outside the admin's own screen. Callers must show it to the admin
     * exactly once (nothing here persists or logs it) and never echo it back
     * from any other endpoint.
     */
    public function resetInternPassword(Intern $intern, ?string $newPassword = null): string
    {
        if (! $intern->intern_account_id) {
            throw new DomainActionException('Intern ini belum memiliki akun login.', 400);
        }

        $plainPassword = $newPassword ?? $this->generateReadablePassword();

        // Instance update (not a query-builder ::whereKey()->update()), so
        // InternAccount's `'password' => 'hashed'` cast actually runs —
        // a query-builder update writes the raw value as-is, which would
        // store this plaintext straight into the database.
        $intern->account->update(['password' => $plainPassword]);

        ActivityLogger::log('intern.password_reset', $intern, "{$intern->full_name} ({$intern->nim})", [
            'mode' => $newPassword ? 'custom' : 'random',
        ]);

        return $plainPassword;
    }

    /**
     * 8 characters drawn from PASSWORD_UPPERCASE/LOWERCASE/DIGITS, with at
     * least one of each guaranteed (the rest filled and shuffled randomly) —
     * "gampang dibaca manusia" without being a single, guessable pattern.
     */
    private function generateReadablePassword(): string
    {
        $pools = [self::PASSWORD_UPPERCASE, self::PASSWORD_LOWERCASE, self::PASSWORD_DIGITS];
        $pickFrom = fn (string $pool) => $pool[random_int(0, strlen($pool) - 1)];

        $chars = array_map($pickFrom, $pools);
        $allChars = implode('', $pools);

        while (count($chars) < self::GENERATED_PASSWORD_LENGTH) {
            $chars[] = $pickFrom($allChars);
        }

        shuffle($chars);

        return implode('', $chars);
    }

    /**
     * attendances.proof_file_url is stored as a full URL, built at upload
     * time with Storage::disk('public')->url($path) (see
     * Api\AttendanceController::leaveRequest()). Every URL from that disk is
     * "<APP_URL>/storage/<path>" (config/filesystems.php), so the part after
     * "/storage/" is the disk-relative path Storage::delete() needs.
     */
    private function attendanceProofPath(string $url): string
    {
        return Str::after($url, '/storage/');
    }
}
