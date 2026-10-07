<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\Attendance;
use App\Models\Intern;
use Illuminate\Support\Facades\DB;

class NotificationCountService
{
    public function counts(AdminUser $admin): array
    {
        $isSpv = $admin->role === 'spv_mentor';
        $items = [];

        if (! $isSpv) {
            $pending = Intern::where('status', 'pending')->count();
            if ($pending > 0) {
                $items[] = [
                    'key' => 'pending_intern',
                    'count' => $pending,
                    'label' => 'registrasi menunggu approval',
                    'url' => route('interns.index', ['tab' => 'pending']),
                ];
            }
        }

        $approvalCount = Attendance::where('approval_status', 'pending')
            ->when($isSpv, fn ($q) => $q->whereHas('intern', fn ($iq) => $iq->where('mentor_id', $admin->id)))
            ->count();

        if ($approvalCount > 0) {
            $items[] = [
                'key' => 'pending_attendance',
                'count' => $approvalCount,
                'label' => 'absensi menunggu approval',
                'url' => route('attendance.index', ['tab' => 'approval']),
            ];
        }

        if (! $isSpv) {
            $readyCount = Intern::where('status', 'completed')
                ->whereHas('evaluation', fn ($q) => $q
                    ->whereNotNull('discipline_score')
                    ->whereNotNull('performance_score')
                    ->whereNotNull('attitude_score')
                    ->whereNotNull('communication_score'))
                ->whereDoesntHave('certificate')
                ->count();

            if ($readyCount > 0) {
                $items[] = [
                    'key' => 'ready_certificate',
                    'count' => $readyCount,
                    'label' => 'sertifikat siap diterbitkan',
                    'url' => route('certificates.index', ['tab' => 'cert']),
                ];
            }
        }

        $total = array_sum(array_column($items, 'count'));

        return ['total' => $total, 'items' => $items];
    }
}
