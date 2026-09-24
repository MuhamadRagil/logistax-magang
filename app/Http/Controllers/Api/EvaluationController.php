<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DomainActionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\PendingEvaluationsRequest;
use App\Http\Requests\Evaluation\StoreEvaluationRequest;
use App\Http\Requests\Evaluation\UpdateEvaluationRequest;
use App\Models\AdminUser;
use App\Models\Evaluation;
use App\Models\Intern;
use App\Models\InternAccount;
use App\Services\EvaluationWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EvaluationController extends Controller
{
    public function __construct(
        private readonly EvaluationWorkflowService $workflow,
    ) {}

    public function pending(PendingEvaluationsRequest $request): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();
        $withinDays = $request->integer('within_days', 7);

        $query = Intern::query()
            ->with(['mentor'])
            ->whereIn('status', ['active', 'extended'])
            ->whereDoesntHave('evaluation')
            ->where('end_date', '<=', Carbon::today()->addDays($withinDays));

        if ($admin->role === 'spv_mentor') {
            $query->where('mentor_id', $admin->id);
        }

        $interns = $query->orderBy('end_date')->get();

        $data = $interns->map(fn (Intern $intern) => [
            'intern_id' => $intern->id,
            'full_name' => $intern->full_name,
            'nim' => $intern->nim,
            'start_date' => $intern->start_date,
            'end_date' => $intern->end_date,
            'mentor' => $intern->mentor ? [
                'id' => $intern->mentor->id,
                'name' => $intern->mentor->name,
            ] : null,
            'evaluation_status' => 'belum diisi',
        ]);

        return $this->success($data);
    }

    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();
        $data = $request->validated();

        $intern = Intern::findOrFail($data['intern_id']);

        try {
            $evaluation = $this->workflow->create($intern, $admin, $data);
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return $this->success($evaluation, 'Evaluasi berhasil dibuat.', 201);
    }

    /**
     * Aturan izin edit (lihat README untuk penjelasan lengkap):
     * - admin_magang: selalu boleh edit. edited_by_admin=true, last_edited_by=auth id.
     *   Kalau intern sudah punya certificate, tandai needs_certificate_regeneration=true
     *   (certificate lama TIDAK dihapus di sini — itu tanggung jawab fase certificate).
     * - spv_mentor yang merupakan evaluated_by (penginput pertama) DAN belum ada
     *   certificate: boleh edit bebas, edited_by_admin tetap false.
     * - spv_mentor yang BUKAN evaluated_by: 403.
     * - siapa pun selain admin_magang, kalau certificate sudah terbit: 403 dengan
     *   pesan bahwa sertifikat perlu digenerate ulang.
     */
    public function update(UpdateEvaluationRequest $request, Evaluation $evaluation): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        try {
            $evaluation = $this->workflow->update($evaluation, $admin, $request->validated());
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return $this->success($evaluation, 'Evaluasi berhasil diperbarui.');
    }

    public function showForIntern(Request $request, string $internId): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        $intern = Intern::findOrFail($internId);

        if ($admin->role === 'spv_mentor' && $intern->mentor_id !== $admin->id) {
            return $this->error('Anda bukan mentor dari intern ini.', 403);
        }

        $evaluation = Evaluation::where('intern_id', $intern->id)->first();

        if (! $evaluation) {
            return $this->success(null, 'Evaluasi belum tersedia.');
        }

        return $this->success($evaluation);
    }

    public function myEvaluation(Request $request): JsonResponse
    {
        /** @var InternAccount $account */
        $account = $request->user();
        $intern = $account->intern;

        if (! $intern) {
            return $this->error('Data intern tidak ditemukan untuk akun ini.', 404);
        }

        $evaluation = Evaluation::where('intern_id', $intern->id)->first();

        if (! $evaluation) {
            return $this->success(null, 'Evaluasi belum tersedia.');
        }

        return $this->success($evaluation);
    }
}
