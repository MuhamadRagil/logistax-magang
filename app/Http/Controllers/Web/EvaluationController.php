<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\DomainActionException;
use App\Http\Requests\Evaluation\StoreEvaluationRequest;
use App\Http\Requests\Evaluation\UpdateEvaluationRequest;
use App\Models\AdminUser;
use App\Models\Certificate;
use App\Models\Evaluation;
use App\Models\Intern;
use App\Services\EvaluationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function __construct(private readonly EvaluationWorkflowService $workflow) {}

    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');
        $isSpv = $admin->role === 'spv_mentor';

        if ($internId = $request->query('intern')) {
            return $this->formView($admin, $isSpv, $internId);
        }

        $interns = Intern::query()
            ->with(['division', 'evaluation'])
            ->when($isSpv, fn ($q) => $q->where('mentor_id', $admin->id))
            ->whereIn('status', ['active', 'extended', 'completed'])
            ->orderByDesc('end_date')
            ->get();

        return view('evaluations.index', [
            'view' => 'list',
            'interns' => $interns,
        ]);
    }

    private function formView(AdminUser $admin, bool $isSpv, string $internId): View
    {
        $intern = Intern::with(['division', 'evaluation'])
            ->when($isSpv, fn ($q) => $q->where('mentor_id', $admin->id))
            ->findOrFail($internId);

        $hasCertificate = Certificate::where('intern_id', $intern->id)->exists();
        $evaluation = $intern->evaluation;

        $isOriginalMentor = $evaluation && $isSpv && $evaluation->evaluated_by === $admin->id;

        $readOnlyReason = null;
        if ($evaluation && $isSpv && ! $isOriginalMentor) {
            $readOnlyReason = 'Anda bukan mentor yang menginput evaluasi ini.';
        } elseif ($evaluation && $isSpv && $hasCertificate) {
            $readOnlyReason = 'Sertifikat sudah terbit untuk intern ini — hanya admin_magang yang bisa mengubah nilai.';
        }

        $canEdit = $readOnlyReason === null;

        return view('evaluations.index', [
            'view' => 'form',
            'intern' => $intern,
            'evaluation' => $evaluation,
            'isAdmin' => ! $isSpv,
            'hasCertificate' => $hasCertificate,
            'canEdit' => $canEdit,
            'readOnlyReason' => $readOnlyReason,
            'weights' => ['discipline' => 25, 'performance' => 35, 'attitude' => 25, 'communication' => 15],
            'labels' => ['discipline' => 'Kedisiplinan', 'performance' => 'Kinerja', 'attitude' => 'Sikap', 'communication' => 'Komunikasi'],
        ]);
    }

    public function store(StoreEvaluationRequest $request): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');
        $data = $request->validated();
        $intern = Intern::findOrFail($data['intern_id']);

        try {
            $this->workflow->create($intern, $admin, $data);
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('evaluations.index', ['intern' => $intern->id])->with('status', 'Evaluasi berhasil disimpan.');
    }

    public function update(UpdateEvaluationRequest $request, Evaluation $evaluation): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user('web');

        try {
            $this->workflow->update($evaluation, $admin, $request->validated());
        } catch (DomainActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('evaluations.index', ['intern' => $evaluation->intern_id])->with('status', 'Evaluasi berhasil diperbarui.');
    }
}
