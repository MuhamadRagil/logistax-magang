<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DomainActionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Intern\ApproveInternRequest;
use App\Http\Requests\Intern\ExtendInternRequest;
use App\Http\Requests\Intern\MarkFailedInternRequest;
use App\Http\Requests\Intern\RejectInternRequest;
use App\Http\Requests\Intern\StoreInternRequest;
use App\Http\Requests\Intern\UpdateInternRequest;
use App\Models\AdminUser;
use App\Models\Intern;
use App\Services\InternWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternController extends Controller
{
    public function __construct(private readonly InternWorkflowService $workflow) {}

    public function index(Request $request): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

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

        $interns = $query->orderByDesc('created_at')->paginate(
            $request->integer('per_page', 15)
        );

        return $this->success($interns);
    }

    public function store(StoreInternRequest $request): JsonResponse
    {
        $result = $this->workflow->createByAdmin($request->validated());

        return $this->success([
            'intern' => $result['intern'],
            'generated_password' => $result['generated_password'],
        ], 'Intern berhasil dibuat. Simpan password ini, tidak akan ditampilkan lagi.', 201);
    }

    public function show(Request $request, Intern $intern): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        if ($admin->role === 'spv_mentor' && $intern->mentor_id !== $admin->id) {
            return $this->error('Anda bukan mentor dari intern ini.', 403);
        }

        return $this->success($intern->load(['division', 'mentor', 'account']));
    }

    public function update(UpdateInternRequest $request, Intern $intern): JsonResponse
    {
        $intern->update($request->validated());

        return $this->success($intern->fresh(['division', 'mentor']));
    }

    public function approve(ApproveInternRequest $request, Intern $intern): JsonResponse
    {
        try {
            $intern = $this->workflow->approve($intern, $request->validated());
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return $this->success($intern, 'Intern berhasil disetujui.');
    }

    public function reject(RejectInternRequest $request, Intern $intern): JsonResponse
    {
        try {
            $intern = $this->workflow->reject($intern, $request->validated('reason'));
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return $this->success($intern, 'Intern berhasil ditolak.');
    }

    public function extend(ExtendInternRequest $request, Intern $intern): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        $intern = $this->workflow->extend($intern, $admin, $request->validated());

        return $this->success($intern, 'Masa magang berhasil diperpanjang.');
    }

    public function markFailed(MarkFailedInternRequest $request, Intern $intern): JsonResponse
    {
        try {
            $intern = $this->workflow->markFailed($intern, $request->validated('reason'));
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return $this->success($intern, 'Intern ditandai gagal.');
    }

    public function markCompleted(Request $request, Intern $intern): JsonResponse
    {
        try {
            $intern = $this->workflow->markCompleted($intern);
        } catch (DomainActionException $e) {
            return $this->error($e->getMessage(), $e->status);
        }

        return $this->success($intern, 'Intern ditandai selesai.');
    }
}
