<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\DomainActionException;
use App\Http\Requests\Attendance\RejectAttendanceRequest;
use App\Models\AdminUser;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\Intern;
use App\Services\AttendanceApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceApprovalService $approvalService) {}

    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');
        $isSpv = $admin->role === 'spv_mentor';
        $tab = $request->query('tab', 'rekap');

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $internQuery = Intern::query()->when($isSpv, fn ($q) => $q->where('mentor_id', $admin->id));

        if ($divisionId = $request->query('division_id')) {
            $internQuery->where('division_id', $divisionId);
        }

        if ($internId = $request->query('intern_id')) {
            $internQuery->where('id', $internId);
        }

        $interns = $internQuery->orderBy('full_name')->get();

        $rekapRows = $tab === 'rekap' ? $this->buildRekap($interns, $month, $year) : [];

        $approvalQuery = Attendance::query()
            ->with(['intern.division'])
            ->where('approval_status', 'pending')
            ->when($isSpv, fn ($q) => $q->whereHas('intern', fn ($iq) => $iq->where('mentor_id', $admin->id)));

        $approvals = $tab === 'approval' ? $approvalQuery->orderBy('date')->get() : collect();
        $approvalCount = (clone $approvalQuery)->count();

        return view('attendance.index', [
            'activeTab' => $tab,
            'month' => $month,
            'year' => $year,
            'daysInMonth' => Carbon::create($year, $month, 1)->daysInMonth,
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'internOptions' => Intern::query()->when($isSpv, fn ($q) => $q->where('mentor_id', $admin->id))->orderBy('full_name')->get(),
            'rekapRows' => $rekapRows,
            'approvals' => $approvals,
            'approvalCount' => $approvalCount,
        ]);
    }

    public function approve(Request $request, Attendance $attendance): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');

        try {
            $this->approvalService->approve($attendance, $admin);
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Pengajuan berhasil disetujui.');
    }

    public function reject(RejectAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');

        try {
            $this->approvalService->reject($attendance, $admin, $request->validated('reason'));
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Pengajuan berhasil ditolak.');
    }

    private function buildRekap($interns, int $month, int $year): array
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        $records = Attendance::query()
            ->whereIn('intern_id', $interns->pluck('id'))
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy(fn ($a) => $a->intern_id.'-'.$a->date->day);

        $colors = [
            'hadir' => 'bg-green-100 border border-green-300',
            'izin' => 'bg-blue-100 border border-blue-300',
            'sakit' => 'bg-amber-100 border border-amber-300',
            'absen' => 'bg-red-100 border border-red-300',
            'weekend' => 'bg-dash-bg',
            'none' => 'bg-white border border-dash-border',
        ];

        return $interns->map(function (Intern $intern) use ($daysInMonth, $month, $year, $records, $colors) {
            $days = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = Carbon::create($year, $month, $d);
                $record = $records->get($intern->id.'-'.$d)?->first();
                $status = $record?->status ?? ($date->isWeekend() ? 'weekend' : 'none');

                $days[] = ['class' => $colors[$status] ?? $colors['none']];
            }

            return ['name' => $intern->full_name, 'days' => $days];
        })->all();
    }
}
