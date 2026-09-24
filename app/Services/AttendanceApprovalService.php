<?php

namespace App\Services;

use App\Exceptions\DomainActionException;
use App\Models\AdminUser;
use App\Models\Attendance;

/**
 * Approve/reject for attendance leave-requests. Extracted out of
 * Api\AttendanceController so the web dashboard's approval screen can call
 * the exact same mentor-scoping + state-transition rules.
 */
class AttendanceApprovalService
{
    public function approve(Attendance $attendance, AdminUser $admin): Attendance
    {
        if ($admin->role === 'spv_mentor' && $attendance->intern->mentor_id !== $admin->id) {
            throw new DomainActionException('Anda bukan mentor dari intern ini.', 403);
        }

        if ($attendance->approval_status !== 'pending') {
            throw new DomainActionException('Hanya pengajuan dengan status pending yang bisa disetujui.', 400);
        }

        $attendance->update([
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        return $attendance->fresh();
    }

    public function reject(Attendance $attendance, AdminUser $admin, string $reason): Attendance
    {
        if ($admin->role === 'spv_mentor' && $attendance->intern->mentor_id !== $admin->id) {
            throw new DomainActionException('Anda bukan mentor dari intern ini.', 403);
        }

        if ($attendance->approval_status !== 'pending') {
            throw new DomainActionException('Hanya pengajuan dengan status pending yang bisa ditolak.', 400);
        }

        $combinedNotes = $attendance->notes
            ? $attendance->notes."\n[Ditolak] ".$reason
            : '[Ditolak] '.$reason;

        $attendance->update([
            'approval_status' => 'rejected',
            'approved_by' => $admin->id,
            'notes' => $combinedNotes,
        ]);

        return $attendance->fresh();
    }
}
