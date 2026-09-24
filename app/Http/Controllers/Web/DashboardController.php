<?php

namespace App\Http\Controllers\Web;

use App\Models\AdminUser;
use App\Models\Attendance;
use App\Models\Intern;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');
        $isSpv = $admin->role === 'spv_mentor';

        $internScope = fn () => Intern::query()->when($isSpv, fn ($q) => $q->where('mentor_id', $admin->id));

        $statActive = $internScope()->whereIn('status', ['active', 'extended'])->count();

        $attendanceScope = fn () => Attendance::query()->when(
            $isSpv,
            fn ($q) => $q->whereHas('intern', fn ($iq) => $iq->where('mentor_id', $admin->id))
        );

        $statPresentToday = $attendanceScope()->whereDate('date', Carbon::today())->where('status', 'hadir')->count();
        $statPresentPct = $statActive > 0 ? (int) round($statPresentToday / $statActive * 100) : 0;

        $statEndingSoon = $internScope()
            ->whereIn('status', ['active', 'extended'])
            ->whereYear('end_date', Carbon::now()->year)
            ->whereMonth('end_date', Carbon::now()->month)
            ->count();

        $statPendingApproval = $internScope()->where('status', 'pending')->count();

        $attendanceTrend = $this->attendanceTrend($internScope, $attendanceScope);
        $needAttention = $this->needAttention($admin, $isSpv);

        return view('dashboard.index', [
            'isSpv' => $isSpv,
            'scopeNote' => $isSpv ? 'Intern bimbingan Anda' : 'Seluruh divisi',
            'statActive' => $statActive,
            'statPresentToday' => $statPresentToday,
            'statPresentPct' => $statPresentPct,
            'statEndingSoon' => $statEndingSoon,
            'statPendingApproval' => $statPendingApproval,
            'attendanceTrend' => $attendanceTrend,
            'needAttention' => $needAttention,
        ]);
    }

    /**
     * Kehadiran 7 hari terakhir (termasuk hari ini): persentase = hadir /
     * total intern aktif dalam scope, dibulatkan. Label hari pakai singkatan
     * Indonesia berdasar ISO weekday (1=Senin..7=Minggu).
     */
    private function attendanceTrend(\Closure $internScope, \Closure $attendanceScope): array
    {
        $dayLabels = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
        $totalActive = $internScope()->whereIn('status', ['active', 'extended'])->count();

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $hadir = $attendanceScope()->whereDate('date', $date)->where('status', 'hadir')->count();
            $pct = $totalActive > 0 ? min(100, (int) round($hadir / $totalActive * 100)) : 0;

            $trend[] = [
                'label' => $dayLabels[$date->isoWeekday()],
                'date' => $date->format('d M'),
                'heightPct' => $pct,
                'count' => $hadir,
            ];
        }

        return $trend;
    }

    /**
     * "Intern Perlu Perhatian" = gabungan 3 kriteria:
     *  1. Evaluasi belum diisi, status active/extended, end_date <= 7 hari lagi
     *     (kriteria sama persis dengan Api\EvaluationController::pending()).
     *  2. Ada attendance dengan approval_status=pending (izin/sakit menunggu approve).
     *  3. Registrasi masih 'pending' dan SUDAH LEBIH DARI 3 HARI sejak diajukan
     *     (created_at) — SLA "terlambat" yang dipakai di sini: admin diharapkan
     *     memproses (approve/reject) pendaftaran baru dalam 3 hari kerja;
     *     lewat dari itu dianggap "telat approve pendaftaran". Kriteria ini
     *     hanya relevan untuk admin_magang, karena hanya admin_magang yang
     *     berwenang approve/reject registrasi (spv_mentor tidak punya aksi ini
     *     di sistem), jadi tidak ditampilkan untuk role spv_mentor.
     *
     * Digabung, diurutkan berdasar tanggal isu terbaru, dibatasi 10 baris.
     */
    private function needAttention(AdminUser $admin, bool $isSpv): array
    {
        $rows = [];

        $evalNeeded = Intern::query()
            ->with('division')
            ->when($isSpv, fn ($q) => $q->where('mentor_id', $admin->id))
            ->whereIn('status', ['active', 'extended'])
            ->whereDoesntHave('evaluation')
            ->where('end_date', '<=', Carbon::today()->addDays(7))
            ->get();

        foreach ($evalNeeded as $intern) {
            $rows[] = [
                'name' => $intern->full_name,
                'division' => $intern->division?->name ?? '—',
                'issue' => 'Evaluasi belum diisi',
                'issueColor' => 'text-amber-700',
                'issueBg' => 'bg-amber-100',
                'date' => $intern->end_date,
                'dateLabel' => $intern->end_date->format('d M Y'),
            ];
        }

        $attendancePending = Attendance::query()
            ->with(['intern.division'])
            ->where('approval_status', 'pending')
            ->when($isSpv, fn ($q) => $q->whereHas('intern', fn ($iq) => $iq->where('mentor_id', $admin->id)))
            ->get();

        foreach ($attendancePending as $attendance) {
            if (! $attendance->intern) {
                continue;
            }

            $rows[] = [
                'name' => $attendance->intern->full_name,
                'division' => $attendance->intern->division?->name ?? '—',
                'issue' => 'Absensi pending approval',
                'issueColor' => 'text-blue-700',
                'issueBg' => 'bg-blue-100',
                'date' => $attendance->created_at,
                'dateLabel' => $attendance->created_at->format('d M Y'),
            ];
        }

        if (! $isSpv) {
            $latePending = Intern::query()
                ->with('division')
                ->where('status', 'pending')
                ->where('created_at', '<=', Carbon::now()->subDays(3))
                ->get();

            foreach ($latePending as $intern) {
                $rows[] = [
                    'name' => $intern->full_name,
                    'division' => $intern->division?->name ?? '—',
                    'issue' => 'Telat approve pendaftaran',
                    'issueColor' => 'text-red-700',
                    'issueBg' => 'bg-red-100',
                    'date' => $intern->created_at,
                    'dateLabel' => $intern->created_at->format('d M Y'),
                ];
            }
        }

        usort($rows, fn ($a, $b) => $b['date'] <=> $a['date']);

        return array_slice($rows, 0, 10);
    }
}
