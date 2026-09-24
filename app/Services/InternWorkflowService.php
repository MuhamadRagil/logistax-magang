<?php

namespace App\Services;

use App\Exceptions\DomainActionException;
use App\Models\AdminUser;
use App\Models\ExtensionLog;
use App\Models\Intern;
use App\Models\InternAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Intern lifecycle actions (create-by-admin, approve, reject, extend,
 * mark-failed, mark-completed). Extracted out of Api\InternController so the
 * web dashboard controllers can trigger the exact same validated logic
 * instead of re-implementing it — the API controller's behavior/response
 * shape is unchanged, it just delegates here now.
 */
class InternWorkflowService
{
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

        return $intern->fresh();
    }

    public function markCompleted(Intern $intern): Intern
    {
        if (! in_array($intern->status, ['active', 'extended'], true)) {
            throw new DomainActionException('Hanya intern dengan status active/extended yang bisa ditandai selesai.', 400);
        }

        $intern->update(['status' => 'completed']);

        return $intern->fresh();
    }
}
