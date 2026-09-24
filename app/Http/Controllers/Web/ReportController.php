<?php

namespace App\Http\Controllers\Web;

use App\Models\AdminUser;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fase 5 minimal export: plain CSV via native PHP (fputcsv), no extra
 * package installed. Covers both the "Export CSV" button on the Absensi
 * rekap tab and the "Export Laporan" form on the Sertifikat & Laporan page
 * (type=attendance|nilai|sertifikat). A PDF/Excel-formatted export can be
 * layered on later (e.g. maatwebsite/excel) without changing this contract.
 */
class ReportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');
        $isSpv = $admin->role === 'spv_mentor';
        $type = $request->query('type', 'attendance');
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        [$filename, $header, $rows] = match ($type) {
            'nilai' => $this->evaluationReport($admin, $isSpv),
            'sertifikat' => $this->certificateReport($admin, $isSpv),
            default => $this->attendanceReport($admin, $isSpv, $month, $year, $request),
        };

        $callback = function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function attendanceReport(AdminUser $admin, bool $isSpv, int $month, int $year, Request $request): array
    {
        $query = Attendance::query()
            ->with(['intern.division'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->when($isSpv, fn ($q) => $q->whereHas('intern', fn ($iq) => $iq->where('mentor_id', $admin->id)))
            ->when($request->query('division_id'), fn ($q, $div) => $q->whereHas('intern', fn ($iq) => $iq->where('division_id', $div)))
            ->when($request->query('intern_id'), fn ($q, $id) => $q->where('intern_id', $id));

        $rows = $query->orderBy('date')->get()->map(fn (Attendance $a) => [
            $a->intern?->full_name ?? '—',
            $a->intern?->division?->name ?? '—',
            $a->date->format('Y-m-d'),
            ucfirst($a->status),
            $a->approval_status ? ucfirst($a->approval_status) : '—',
        ]);

        return [
            "laporan-absensi-{$year}-{$month}.csv",
            ['Nama', 'Divisi', 'Tanggal', 'Status', 'Status Approval'],
            $rows,
        ];
    }

    private function evaluationReport(AdminUser $admin, bool $isSpv): array
    {
        $rows = Evaluation::query()
            ->with(['intern.division'])
            ->when($isSpv, fn ($q) => $q->whereHas('intern', fn ($iq) => $iq->where('mentor_id', $admin->id)))
            ->get()
            ->map(fn (Evaluation $e) => [
                $e->intern?->full_name ?? '—',
                $e->intern?->division?->name ?? '—',
                $e->discipline_score,
                $e->performance_score,
                $e->attitude_score,
                $e->communication_score,
                $e->total_score,
                $e->grade,
            ]);

        return [
            'laporan-nilai.csv',
            ['Nama', 'Divisi', 'Kedisiplinan', 'Kinerja', 'Sikap', 'Komunikasi', 'Total', 'Grade'],
            $rows,
        ];
    }

    private function certificateReport(AdminUser $admin, bool $isSpv): array
    {
        $rows = Certificate::query()
            ->with(['intern.division'])
            ->when($isSpv, fn ($q) => $q->whereHas('intern', fn ($iq) => $iq->where('mentor_id', $admin->id)))
            ->orderByDesc('issued_date')
            ->get()
            ->map(fn (Certificate $c) => [
                $c->intern?->full_name ?? '—',
                $c->intern?->division?->name ?? '—',
                $c->certificate_number,
                $c->issued_date->format('Y-m-d'),
                $c->download_count,
            ]);

        return [
            'laporan-sertifikat.csv',
            ['Nama', 'Divisi', 'No. Sertifikat', 'Tanggal Terbit', 'Jumlah Download'],
            $rows,
        ];
    }
}
