<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\DomainActionException;
use App\Http\Requests\Intern\ApproveInternRequest;
use App\Http\Requests\Intern\RejectInternRequest;
use App\Http\Requests\Intern\StoreInternRequest;
use App\Models\AdminUser;
use App\Models\Division;
use App\Models\Intern;
use App\Services\InternWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class InternController extends Controller
{
    public function __construct(private readonly InternWorkflowService $workflow) {}

    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');

        $query = Intern::query()->with(['division', 'mentor']);

        if ($admin->role === 'spv_mentor') {
            $query->where('mentor_id', $admin->id);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($divisionId = $request->query('division_id')) {
            $query->where('division_id', $divisionId);
        }

        if ($institution = $request->query('institution')) {
            $query->where('institution', 'like', "%{$institution}%");
        }

        $interns = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $pendingQuery = Intern::query()->where('status', 'pending');
        if ($admin->role === 'spv_mentor') {
            $pendingQuery->where('mentor_id', $admin->id);
        }
        $pending = (clone $pendingQuery)->with(['division'])->orderBy('created_at')->get();

        return view('interns.index', [
            'interns' => $interns,
            'pending' => $pending,
            'pendingCount' => $pending->count(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'mentors' => AdminUser::where('role', 'spv_mentor')->where('is_active', true)->orderBy('name')->get(),
            'institutions' => Intern::query()->distinct()->orderBy('institution')->pluck('institution'),
            'activeTab' => $request->query('tab', 'list'),
            'openAdd' => $request->query('action') === 'add',
        ]);
    }

    /**
     * AJAX detail payload for the "Lihat" modal (Absensi/Nilai/Sertifikat
     * sub-tabs) — fetched on demand instead of precomputing per-row summaries
     * for the whole paginated list.
     */
    public function detail(Request $request, Intern $intern): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');

        if ($admin->role === 'spv_mentor' && $intern->mentor_id !== $admin->id) {
            abort(403);
        }

        $intern->load(['division', 'mentor', 'evaluation', 'certificate']);

        $totalDays = $intern->attendances()->count();
        $hadir = $intern->attendances()->where('status', 'hadir')->count();
        $izin = $intern->attendances()->where('status', 'izin')->count();
        $sakit = $intern->attendances()->where('status', 'sakit')->count();
        $attendancePct = $totalDays > 0 ? round($hadir / $totalDays * 100) : 0;

        return response()->json([
            'id' => $intern->id,
            'name' => $intern->full_name,
            'nim' => $intern->nim,
            'institution' => $intern->institution,
            'major' => $intern->major,
            'division' => $intern->division?->name ?? '—',
            'mentor' => $intern->mentor?->name ?? '—',
            'period' => $intern->start_date->format('d M Y').' – '.$intern->end_date->format('d M Y'),
            'status' => $intern->status,
            'hasExtension' => $intern->original_end_date !== null,
            'attendanceSummary' => $totalDays > 0
                ? "Rekap kehadiran: {$attendancePct}% hadir, {$izin} izin, {$sakit} sakit dari {$totalDays} hari tercatat."
                : 'Belum ada data absensi.',
            'evaluationSummary' => $intern->evaluation
                ? "Skor evaluasi akhir: {$intern->evaluation->total_score} (Grade {$intern->evaluation->grade}) — Kedisiplinan {$intern->evaluation->discipline_score}, Kinerja {$intern->evaluation->performance_score}, Sikap {$intern->evaluation->attitude_score}, Komunikasi {$intern->evaluation->communication_score}."
                : 'Evaluasi belum diisi.',
            'certificateSummary' => $intern->certificate
                ? "Sertifikat No. {$intern->certificate->certificate_number} — sudah digenerate."
                : 'Sertifikat belum digenerate.',
        ]);
    }

    public function store(StoreInternRequest $request): RedirectResponse
    {
        $result = $this->workflow->createByAdmin($request->validated());

        return redirect()->route('interns.index')->with(
            'status',
            "Intern {$result['intern']->full_name} berhasil dibuat. Password sementara: {$result['generated_password']} (simpan sekarang, tidak akan ditampilkan lagi)."
        );
    }

    public function approve(ApproveInternRequest $request, Intern $intern): RedirectResponse
    {
        try {
            $this->workflow->approve($intern, $request->validated());
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Intern berhasil disetujui.');
    }

    public function reject(RejectInternRequest $request, Intern $intern): RedirectResponse
    {
        try {
            $this->workflow->reject($intern, $request->validated('reason'));
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Intern berhasil ditolak.');
    }
}
