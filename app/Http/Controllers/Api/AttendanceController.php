<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DomainActionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Http\Requests\Attendance\LeaveRequestRequest;
use App\Http\Requests\Attendance\MonthlyReportRequest;
use App\Http\Requests\Attendance\MyHistoryRequest;
use App\Http\Requests\Attendance\RejectAttendanceRequest;
use App\Models\AdminUser;
use App\Models\Attendance;
use App\Models\Intern;
use App\Models\InternAccount;
use App\Services\AttendanceApprovalService;
use App\Services\AttendanceLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceLocationService $locationService,
        private readonly AttendanceApprovalService $approvalService,
    ) {}

    /**
     * Resolve the Intern record tied to the currently authenticated InternAccount.
     */
    private function currentIntern(Request $request): ?Intern
    {
        /** @var InternAccount $account */
        $account = $request->user();

        return $account->intern;
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $intern = $this->currentIntern($request);

        if (! $intern) {
            return $this->error('Data intern tidak ditemukan untuk akun ini.', 404);
        }

        if (! in_array($intern->status, ['active', 'extended'], true)) {
            return $this->error('Anda tidak sedang dalam status magang aktif.', 403);
        }

        $data = $request->validated();

        if (! $this->locationService->isWithinAnyOfficeRadius((float) $data['latitude'], (float) $data['longitude'])) {
            return $this->error('Lokasi Anda di luar radius kantor yang terdaftar.', 422);
        }

        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('intern_id', $intern->id)->where('date', $today)->first();

        if ($attendance && $attendance->check_in_time) {
            return $this->error('Anda sudah check-in hari ini.', 400);
        }

        if ($attendance && in_array($attendance->status, ['izin', 'sakit'], true)) {
            return $this->error('Anda sudah mengajukan izin/sakit untuk hari ini, tidak bisa check-in.', 400);
        }

        if ($attendance) {
            $attendance->update([
                'check_in_time' => now(),
                'check_in_lat' => $data['latitude'],
                'check_in_lng' => $data['longitude'],
                'status' => 'hadir',
            ]);
        } else {
            $attendance = Attendance::create([
                'intern_id' => $intern->id,
                'date' => $today,
                'check_in_time' => now(),
                'check_in_lat' => $data['latitude'],
                'check_in_lng' => $data['longitude'],
                'status' => 'hadir',
            ]);
        }

        return $this->success($attendance->fresh(), 'Check-in berhasil.', 201);
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $intern = $this->currentIntern($request);

        if (! $intern) {
            return $this->error('Data intern tidak ditemukan untuk akun ini.', 404);
        }

        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('intern_id', $intern->id)->where('date', $today)->first();

        if (! $attendance || ! $attendance->check_in_time) {
            return $this->error('Anda belum check-in hari ini.', 400);
        }

        if ($attendance->check_out_time) {
            return $this->error('Anda sudah check-out hari ini.', 400);
        }

        $data = $request->validated();

        if (! $this->locationService->isWithinAnyOfficeRadius((float) $data['latitude'], (float) $data['longitude'])) {
            return $this->error('Lokasi Anda di luar radius kantor yang terdaftar.', 422);
        }

        $attendance->update([
            'check_out_time' => now(),
            'check_out_lat' => $data['latitude'],
            'check_out_lng' => $data['longitude'],
        ]);

        return $this->success($attendance->fresh(), 'Check-out berhasil.');
    }

    /**
     * Keputusan validasi: proof_file WAJIB untuk status 'sakit', OPSIONAL untuk
     * 'izin' (lihat App\Http\Requests\Attendance\LeaveRequestRequest & README).
     */
    public function leaveRequest(LeaveRequestRequest $request): JsonResponse
    {
        $intern = $this->currentIntern($request);

        if (! $intern) {
            return $this->error('Data intern tidak ditemukan untuk akun ini.', 404);
        }

        $data = $request->validated();
        $date = $data['date'];

        if ($date < $intern->start_date->toDateString()) {
            return $this->error('Tanggal di luar periode magang Anda.', 422);
        }

        if ($date > $intern->end_date->toDateString()) {
            return $this->error('Tidak bisa mengajukan izin/sakit untuk tanggal setelah masa magang berakhir.', 422);
        }

        $attendance = Attendance::where('intern_id', $intern->id)->where('date', $date)->first();

        if ($attendance) {
            if ($attendance->status === 'hadir') {
                return $this->error('Anda sudah check-in (hadir) pada tanggal tersebut, tidak bisa mengajukan izin/sakit.', 400);
            }

            if ($attendance->approval_status === 'pending') {
                return $this->error('Sudah ada pengajuan izin/sakit untuk tanggal ini yang masih menunggu persetujuan.', 400);
            }

            if ($attendance->approval_status === 'approved') {
                return $this->error('Sudah ada absensi yang disetujui untuk tanggal ini.', 400);
            }
        }

        $proofUrl = $attendance->proof_file_url ?? null;

        if ($request->hasFile('proof_file')) {
            $path = $request->file('proof_file')->store('attendance-proofs', 'public');
            $proofUrl = Storage::disk('public')->url($path);
        }

        $attributes = [
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'proof_file_url' => $proofUrl,
            'approval_status' => 'pending',
        ];

        if ($attendance) {
            $attendance->update($attributes);
        } else {
            $attendance = Attendance::create(array_merge($attributes, [
                'intern_id' => $intern->id,
                'date' => $date,
            ]));
        }

        return $this->success($attendance->fresh(), 'Pengajuan izin/sakit berhasil dikirim, menunggu persetujuan.', 201);
    }

    public function myHistory(MyHistoryRequest $request): JsonResponse
    {
        $intern = $this->currentIntern($request);

        if (! $intern) {
            return $this->error('Data intern tidak ditemukan untuk akun ini.', 404);
        }

        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);

        $history = Attendance::where('intern_id', $intern->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        return $this->success($history);
    }

    public function monthlyReport(MonthlyReportRequest $request): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();
        $data = $request->validated();

        $internQuery = Intern::query();

        if ($admin->role === 'spv_mentor') {
            $internQuery->where('mentor_id', $admin->id);
        }

        if (! empty($data['division_id'])) {
            $internQuery->where('division_id', $data['division_id']);
        }

        if (! empty($data['intern_id'])) {
            $internQuery->where('id', $data['intern_id']);
        }

        $interns = $internQuery->get(['id', 'full_name', 'nim']);

        if ($interns->isEmpty()) {
            return $this->success([]);
        }

        $counts = Attendance::whereIn('intern_id', $interns->pluck('id'))
            ->whereYear('date', $data['year'])
            ->whereMonth('date', $data['month'])
            ->selectRaw('intern_id, status, count(*) as total')
            ->groupBy('intern_id', 'status')
            ->get()
            ->groupBy('intern_id');

        $report = $interns->map(function (Intern $intern) use ($counts) {
            $internCounts = $counts->get($intern->id, collect());

            $summary = [
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'absen' => 0,
            ];

            foreach ($internCounts as $row) {
                $summary[$row->status] = (int) $row->total;
            }

            return [
                'intern_id' => $intern->id,
                'full_name' => $intern->full_name,
                'nim' => $intern->nim,
                'summary' => $summary,
            ];
        })->values();

        return $this->success($report);
    }

    public function pendingApprovals(Request $request): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        $query = Attendance::with(['intern'])->where('approval_status', 'pending');

        if ($admin->role === 'spv_mentor') {
            $query->whereHas('intern', fn ($q) => $q->where('mentor_id', $admin->id));
        }

        $attendances = $query->orderBy('date')->get();

        return $this->success($attendances);
    }

    public function approve(Request $request, Attendance $attendance): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        try {
            $attendance = $this->approvalService->approve($attendance, $admin);
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return $this->success($attendance, 'Pengajuan berhasil disetujui.');
    }

    public function reject(RejectAttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        try {
            $attendance = $this->approvalService->reject($attendance, $admin, $request->validated('reason'));
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return $this->success($attendance, 'Pengajuan berhasil ditolak.');
    }
}
